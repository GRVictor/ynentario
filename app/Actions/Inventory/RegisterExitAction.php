<?php

namespace App\Actions\Inventory;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\InventoryMovementFolioService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegisterExitAction
{
    public function __construct(
        protected InventoryMovementFolioService $folioService
    ) {}

    /**
     * Registra una salida de mercancía del inventario.
     *
     * @param  array{
     *     product_id: int,
     *     warehouse_id: int,
     *     quantity: float|int,
     *     reason: string,
     *     reference?: string|null,
     *     notes?: string|null,
     * } $data
     */
    public function execute(array $data, User $user): InventoryMovement
    {
        $quantity = (float) $data['quantity'];

        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'quantity' => ['La cantidad de la salida debe ser mayor a cero.'],
            ]);
        }

        return DB::transaction(function () use ($data, $user, $quantity) {
            $product = Product::findOrFail($data['product_id']);

            $inventory = Inventory::where('product_id', $product->id)
                ->where('warehouse_id', $data['warehouse_id'])
                ->lockForUpdate()
                ->first();

            $previousQuantity = $inventory ? (float) $inventory->quantity : 0.0;

            if ($previousQuantity < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => ["No hay existencias suficientes. Actualmente hay {$previousQuantity} unidades disponibles y estás intentando retirar {$quantity}."],
                ]);
            }

            $newQuantity = $previousQuantity - $quantity;
            $inventory->quantity = $newQuantity;
            $inventory->save();

            $folio = $this->folioService->generate('exit');

            $movement = InventoryMovement::create([
                'folio' => $folio,
                'type' => 'exit',
                'product_id' => $product->id,
                'warehouse_id' => $data['warehouse_id'],
                'destination_warehouse_id' => null,
                'quantity' => $quantity,
                'previous_quantity' => $previousQuantity,
                'new_quantity' => $newQuantity,
                'user_id' => $user->id,
                'reason' => $data['reason'],
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            ActivityLogger::log(
                'inventory_exit',
                "Salida de {$quantity} unidades para '{$product->name}' (Folio: {$folio})",
                $movement,
                ['quantity' => $quantity, 'warehouse_id' => $data['warehouse_id']]
            );

            return $movement;
        });
    }
}
