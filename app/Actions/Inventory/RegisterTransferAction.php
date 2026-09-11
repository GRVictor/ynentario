<?php

namespace App\Actions\Inventory;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\ActivityLogger;
use App\Services\InventoryMovementFolioService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegisterTransferAction
{
    public function __construct(
        protected InventoryMovementFolioService $folioService
    ) {}

    /**
     * Registra un traslado de mercancía entre dos almacenes.
     *
     * @param  array{
     *     product_id: int,
     *     warehouse_id: int,
     *     destination_warehouse_id: int,
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
                'quantity' => ['La cantidad a transferir debe ser mayor a cero.'],
            ]);
        }

        if ((int) $data['warehouse_id'] === (int) $data['destination_warehouse_id']) {
            throw ValidationException::withMessages([
                'destination_warehouse_id' => ['El almacén de destino no puede ser el mismo que el almacén de origen.'],
            ]);
        }

        return DB::transaction(function () use ($data, $user, $quantity) {
            $product = Product::findOrFail($data['product_id']);
            $sourceWarehouse = Warehouse::findOrFail($data['warehouse_id']);
            $destinationWarehouse = Warehouse::findOrFail($data['destination_warehouse_id']);

            // Bloquear existencias en el almacén de origen
            $sourceInventory = Inventory::where('product_id', $product->id)
                ->where('warehouse_id', $sourceWarehouse->id)
                ->lockForUpdate()
                ->first();

            $sourcePrevQty = $sourceInventory ? (float) $sourceInventory->quantity : 0.0;

            if ($sourcePrevQty < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => ["No hay existencias suficientes en {$sourceWarehouse->name}. Actualmente hay {$sourcePrevQty} unidades disponibles y estás intentando transferir {$quantity}."],
                ]);
            }

            // Bloquear o inicializar existencias en el almacén de destino
            $destinationInventory = Inventory::where('product_id', $product->id)
                ->where('warehouse_id', $destinationWarehouse->id)
                ->lockForUpdate()
                ->first();

            $destinationPrevQty = $destinationInventory ? (float) $destinationInventory->quantity : 0.0;

            if (! $destinationInventory) {
                $destinationInventory = new Inventory([
                    'product_id' => $product->id,
                    'warehouse_id' => $destinationWarehouse->id,
                ]);
            }

            // Actualizar existencias en el origen
            $sourceNewQty = $sourcePrevQty - $quantity;
            $sourceInventory->quantity = $sourceNewQty;
            $sourceInventory->save();

            // Actualizar existencias en el destino
            $destinationNewQty = $destinationPrevQty + $quantity;
            $destinationInventory->quantity = $destinationNewQty;
            $destinationInventory->save();

            $folio = $this->folioService->generate('transfer');

            $movement = InventoryMovement::create([
                'folio' => $folio,
                'type' => 'transfer',
                'product_id' => $product->id,
                'warehouse_id' => $sourceWarehouse->id,
                'destination_warehouse_id' => $destinationWarehouse->id,
                'quantity' => $quantity,
                'previous_quantity' => $sourcePrevQty,
                'new_quantity' => $sourceNewQty,
                'user_id' => $user->id,
                'reason' => $data['reason'],
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            ActivityLogger::log(
                'inventory_transfer',
                "Transferencia de {$quantity} unidades de '{$product->name}' de '{$sourceWarehouse->name}' a '{$destinationWarehouse->name}' (Folio: {$folio})",
                $movement,
                [
                    'source_warehouse_id' => $sourceWarehouse->id,
                    'destination_warehouse_id' => $destinationWarehouse->id,
                    'quantity' => $quantity,
                ]
            );

            return $movement;
        });
    }
}
