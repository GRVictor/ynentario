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

class RegisterAdjustmentAction
{
    public function __construct(
        protected InventoryMovementFolioService $folioService
    ) {}

    /**
     * Registra un ajuste de inventario derivado de conteo físico o auditoría.
     *
     * @param  array{
     *     product_id: int,
     *     warehouse_id: int,
     *     real_quantity: float|int,
     *     reason: string,
     *     reference?: string|null,
     *     notes?: string|null,
     * } $data
     */
    public function execute(array $data, User $user): InventoryMovement
    {
        $realQuantity = (float) $data['real_quantity'];

        if ($realQuantity < 0) {
            throw ValidationException::withMessages([
                'real_quantity' => ['La existencia real no puede ser negativa.'],
            ]);
        }

        return DB::transaction(function () use ($data, $user, $realQuantity) {
            $product = Product::findOrFail($data['product_id']);

            $inventory = Inventory::where('product_id', $product->id)
                ->where('warehouse_id', $data['warehouse_id'])
                ->lockForUpdate()
                ->first();

            $previousQuantity = $inventory ? (float) $inventory->quantity : 0.0;

            if ($previousQuantity === $realQuantity) {
                throw ValidationException::withMessages([
                    'real_quantity' => ["La cantidad indicada es igual a la existencia actual ({$previousQuantity}). No hay diferencia que ajustar."],
                ]);
            }

            if (! $inventory) {
                $inventory = new Inventory([
                    'product_id' => $product->id,
                    'warehouse_id' => $data['warehouse_id'],
                ]);
            }

            $difference = $realQuantity - $previousQuantity;
            $inventory->quantity = $realQuantity;
            $inventory->save();

            $folio = $this->folioService->generate('adjustment');

            $sign = $difference > 0 ? "+{$difference}" : "{$difference}";
            $fullReason = "{$data['reason']} (Diferencia: {$sign})";

            $movement = InventoryMovement::create([
                'folio' => $folio,
                'type' => 'adjustment',
                'product_id' => $product->id,
                'warehouse_id' => $data['warehouse_id'],
                'destination_warehouse_id' => null,
                'quantity' => abs($difference),
                'previous_quantity' => $previousQuantity,
                'new_quantity' => $realQuantity,
                'user_id' => $user->id,
                'reason' => $fullReason,
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            ActivityLogger::log(
                'inventory_adjustment',
                "Ajuste de inventario para '{$product->name}' en almacén (Folio: {$folio}, Diferencia: {$sign})",
                $movement,
                [
                    'previous_quantity' => $previousQuantity,
                    'new_quantity' => $realQuantity,
                    'difference' => $difference,
                    'warehouse_id' => $data['warehouse_id'],
                ]
            );

            return $movement;
        });
    }
}
