<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\CsvExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KardexController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('inventory.view');

        $productId = $request->input('product_id');
        $warehouseId = $request->input('warehouse_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $kardexRows = [];
        $selectedProduct = null;
        $selectedWarehouse = null;
        $currentBalance = 0.0;

        if ($productId) {
            $selectedProduct = Product::with(['unit', 'category'])->find($productId);

            if ($selectedProduct) {
                $query = InventoryMovement::with(['warehouse:id,name', 'destinationWarehouse:id,name', 'user:id,name'])
                    ->where('product_id', $productId);

                if ($warehouseId) {
                    $selectedWarehouse = Warehouse::find($warehouseId);
                    $query->where(function ($q) use ($warehouseId) {
                        $q->where('warehouse_id', $warehouseId)
                            ->orWhere('destination_warehouse_id', $warehouseId);
                    });

                    $inv = Inventory::where('product_id', $productId)
                        ->where('warehouse_id', $warehouseId)
                        ->first();
                    $currentBalance = $inv ? (float) $inv->quantity : 0.0;
                } else {
                    $currentBalance = (float) $selectedProduct->total_stock;
                }

                if ($startDate) {
                    $query->whereDate('created_at', '>=', $startDate);
                }

                if ($endDate) {
                    $query->whereDate('created_at', '<=', $endDate);
                }

                // Orden cronológico ascendente para el cálculo continuo de existencias en Kardex
                $movements = $query->orderBy('created_at', 'asc')->orderBy('id', 'asc')->get();

                foreach ($movements as $m) {
                    $entryQty = 0.0;
                    $exitQty = 0.0;

                    if ($m->type === 'entry') {
                        $entryQty = (float) $m->quantity;
                    } elseif ($m->type === 'exit') {
                        $exitQty = (float) $m->quantity;
                    } elseif ($m->type === 'adjustment') {
                        if ($m->new_quantity >= $m->previous_quantity) {
                            $entryQty = (float) $m->quantity;
                        } else {
                            $exitQty = (float) $m->quantity;
                        }
                    } elseif ($m->type === 'transfer') {
                        if ($warehouseId && (int) $m->destination_warehouse_id === (int) $warehouseId) {
                            $entryQty = (float) $m->quantity;
                        } else {
                            $exitQty = (float) $m->quantity;
                        }
                    }

                    $kardexRows[] = [
                        'id' => $m->id,
                        'folio' => $m->folio,
                        'date' => $m->created_at->format('d/m/Y H:i'),
                        'type' => $m->type,
                        'type_label' => $m->type_label,
                        'warehouse_name' => $m->warehouse?->name ?? 'N/A',
                        'destination_warehouse_name' => $m->destinationWarehouse?->name,
                        'entry' => $entryQty > 0 ? $entryQty : null,
                        'exit' => $exitQty > 0 ? $exitQty : null,
                        'unit_cost' => $m->unit_cost !== null ? (float) $m->unit_cost : null,
                        'previous_quantity' => (float) $m->previous_quantity,
                        'new_quantity' => (float) $m->new_quantity,
                        'user_name' => $m->user?->name ?? 'N/A',
                        'reason' => $m->reason,
                        'reference' => $m->reference,
                    ];
                }
            }
        }

        return Inertia::render('Kardex/Index', [
            'products' => Product::where('is_active', true)->select('id', 'name', 'sku')->orderBy('name')->get(),
            'warehouses' => Warehouse::where('is_active', true)->select('id', 'name', 'code')->orderBy('name')->get(),
            'kardexRows' => $kardexRows,
            'selectedProduct' => $selectedProduct ? [
                'id' => $selectedProduct->id,
                'name' => $selectedProduct->name,
                'sku' => $selectedProduct->sku,
                'unit' => $selectedProduct->unit?->name ?? 'Pieza',
                'unit_abbr' => $selectedProduct->unit?->abbreviation ?? 'pza',
                'min_stock' => (float) $selectedProduct->min_stock,
                'reorder_point' => (float) $selectedProduct->reorder_point,
            ] : null,
            'selectedWarehouse' => $selectedWarehouse ? [
                'id' => $selectedWarehouse->id,
                'name' => $selectedWarehouse->name,
                'code' => $selectedWarehouse->code,
            ] : null,
            'currentBalance' => $currentBalance,
            'filters' => [
                'product_id' => $productId ? (int) $productId : null,
                'warehouse_id' => $warehouseId ? (int) $warehouseId : null,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    /**
     * Export Kardex to CSV.
     */
    public function export(Request $request, CsvExportService $csvService): StreamedResponse
    {
        Gate::authorize('reports.export');

        $productId = $request->input('product_id');
        $warehouseId = $request->input('warehouse_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $product = Product::findOrFail($productId);

        $query = InventoryMovement::with(['warehouse', 'destinationWarehouse', 'user'])
            ->where('product_id', $productId);

        if ($warehouseId) {
            $query->where(function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId)
                    ->orWhere('destination_warehouse_id', $warehouseId);
            });
        }

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $movements = $query->orderBy('created_at', 'asc')->orderBy('id', 'asc')->get();

        $headers = [
            'Fecha',
            'Folio',
            'Tipo',
            'Almacén Origen',
            'Almacén Destino',
            'Entrada',
            'Salida',
            'Costo Unitario',
            'Existencia Anterior',
            'Existencia Posterior',
            'Usuario',
            'Motivo',
            'Referencia',
        ];

        $rows = [];
        foreach ($movements as $m) {
            $entryQty = '';
            $exitQty = '';

            if ($m->type === 'entry') {
                $entryQty = (float) $m->quantity;
            } elseif ($m->type === 'exit') {
                $exitQty = (float) $m->quantity;
            } elseif ($m->type === 'adjustment') {
                if ($m->new_quantity >= $m->previous_quantity) {
                    $entryQty = (float) $m->quantity;
                } else {
                    $exitQty = (float) $m->quantity;
                }
            } elseif ($m->type === 'transfer') {
                if ($warehouseId && (int) $m->destination_warehouse_id === (int) $warehouseId) {
                    $entryQty = (float) $m->quantity;
                } else {
                    $exitQty = (float) $m->quantity;
                }
            }

            $rows[] = [
                $m->created_at->format('d/m/Y H:i'),
                $m->folio,
                $m->type_label,
                $m->warehouse?->name ?? '',
                $m->destinationWarehouse?->name ?? '',
                $entryQty,
                $exitQty,
                $m->unit_cost !== null ? (float) $m->unit_cost : '',
                (float) $m->previous_quantity,
                (float) $m->new_quantity,
                $m->user?->name ?? '',
                $m->reason,
                $m->reference ?? '',
            ];
        }

        return $csvService->download("kardex_{$product->sku}_".date('Ymd_His').'.csv', $headers, $rows);
    }
}
