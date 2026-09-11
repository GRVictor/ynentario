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

class RegisterEntryAction
{
    public function __construct(
        protected InventoryMovementFolioService $folioService
    ) {}

    /**
     * Registra una entrada de mercancía al inventario.
     *
     * @param  array{
     *     product_id: int,
     *     warehouse_id: int,
     *     quantity: float|int,
     *     unit_cost?: float|int|null,
     *     update_selling_price?: bool|null,
     *     new_selling_price?: float|int|null,
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
                'quantity' => ['La cantidad de la entrada debe ser mayor a cero.'],
            ]);
        }

        return DB::transaction(function () use ($data, $user, $quantity) {
            $product = Product::findOrFail($data['product_id']);

            // Calcular las existencias totales en todos los almacenes antes de la entrada
            $currentTotalStock = (float) Inventory::where('product_id', $product->id)->sum('quantity');
            $currentCost = (float) $product->cost_price;

            $isCustomerReturn = str_contains(mb_strtolower($data['reason']), 'devolución')
                || str_contains(mb_strtolower($data['reason']), 'devolucion');

            if ($isCustomerReturn) {
                // Las devoluciones de cliente reingresan al costo contable actual del producto
                // sin alterar el precio de venta del catálogo ni el costo ponderado
                $unitCost = $currentCost;
            } else {
                $unitCost = isset($data['unit_cost']) && is_numeric($data['unit_cost'])
                    ? (float) $data['unit_cost']
                    : $currentCost;

                // Recalcular el Precio Medio Ponderado (PMP) si se proporciona costo unitario de entrada
                if ($unitCost > 0) {
                    if ($currentTotalStock > 0 && $currentCost > 0) {
                        $newTotalStock = $currentTotalStock + $quantity;
                        $newWeightedCost = (($currentTotalStock * $currentCost) + ($quantity * $unitCost)) / $newTotalStock;
                        $product->cost_price = round($newWeightedCost, 2);
                    } else {
                        $product->cost_price = round($unitCost, 2);
                    }
                }

                // Actualizar opcionalmente el precio de venta al público en catálogo
                if (! empty($data['update_selling_price']) && isset($data['new_selling_price']) && is_numeric($data['new_selling_price']) && (float) $data['new_selling_price'] > 0) {
                    $product->selling_price = round((float) $data['new_selling_price'], 2);
                }

                $product->save();
            }

            // Bloquear la fila de inventario con bloqueo pesimista o inicializarla si no existe
            $inventory = Inventory::where('product_id', $product->id)
                ->where('warehouse_id', $data['warehouse_id'])
                ->lockForUpdate()
                ->first();

            $previousQuantity = $inventory ? (float) $inventory->quantity : 0.0;
            $newQuantity = $previousQuantity + $quantity;

            if (! $inventory) {
                $inventory = new Inventory([
                    'product_id' => $product->id,
                    'warehouse_id' => $data['warehouse_id'],
                ]);
            }

            $inventory->quantity = $newQuantity;
            $inventory->save();

            $folio = $this->folioService->generate('entry');

            $movement = InventoryMovement::create([
                'folio' => $folio,
                'type' => 'entry',
                'product_id' => $product->id,
                'warehouse_id' => $data['warehouse_id'],
                'destination_warehouse_id' => null,
                'quantity' => $quantity,
                'previous_quantity' => $previousQuantity,
                'new_quantity' => $newQuantity,
                'unit_cost' => $unitCost > 0 ? $unitCost : null,
                'user_id' => $user->id,
                'reason' => $data['reason'],
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            ActivityLogger::log(
                'inventory_entry',
                "Entrada de {$quantity} unidades para '{$product->name}' (Folio: {$folio})",
                $movement,
                [
                    'quantity' => $quantity,
                    'warehouse_id' => $data['warehouse_id'],
                    'unit_cost' => $unitCost,
                    'new_cost_price' => (float) $product->cost_price,
                    'new_selling_price' => (float) $product->selling_price,
                ]
            );

            return $movement;
        });
    }
}
