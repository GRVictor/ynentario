<?php

namespace App\Http\Controllers;

use App\Actions\Inventory\RegisterAdjustmentAction;
use App\Actions\Inventory\RegisterEntryAction;
use App\Actions\Inventory\RegisterExitAction;
use App\Actions\Inventory\RegisterTransferAction;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\CsvExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryMovementController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('inventory.view');

        $query = InventoryMovement::with([
            'product:id,name,sku',
            'warehouse:id,name,code',
            'destinationWarehouse:id,name,code',
            'user:id,name',
        ]);

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($warehouseId = $request->input('warehouse_id')) {
            $query->where(function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId)
                    ->orWhere('destination_warehouse_id', $warehouseId);
            });
        }

        if ($productId = $request->input('product_id')) {
            $query->where('product_id', $productId);
        }

        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($startDate = $request->input('start_date')) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate = $request->input('end_date')) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('folio', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"));
            });
        }

        $movements = $query->latest('id')
            ->paginate(15)
            ->withQueryString()
            ->through(function ($m) {
                return [
                    'id' => $m->id,
                    'folio' => $m->folio,
                    'type' => $m->type,
                    'type_label' => $m->type_label,
                    'product_id' => $m->product_id,
                    'product_name' => $m->product?->name ?? 'N/A',
                    'product_sku' => $m->product?->sku ?? 'N/A',
                    'warehouse_name' => $m->warehouse?->name ?? 'N/A',
                    'destination_warehouse_name' => $m->destinationWarehouse?->name,
                    'quantity' => (float) $m->quantity,
                    'previous_quantity' => (float) $m->previous_quantity,
                    'new_quantity' => (float) $m->new_quantity,
                    'unit_cost' => $m->unit_cost !== null ? (float) $m->unit_cost : null,
                    'user_name' => $m->user?->name ?? 'N/A',
                    'reason' => $m->reason,
                    'reference' => $m->reference,
                    'notes' => $m->notes,
                    'created_at' => $m->created_at->format('d/m/Y H:i'),
                ];
            });

        return Inertia::render('Movements/Index', [
            'movements' => $movements,
            'filters' => $request->only(['type', 'warehouse_id', 'product_id', 'user_id', 'start_date', 'end_date', 'search']),
            'warehouses' => Warehouse::where('is_active', true)->select('id', 'name', 'code')->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->select('id', 'name', 'sku')->orderBy('name')->get(),
            'users' => User::select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function createEntry(): Response
    {
        Gate::authorize('inventory.entry');

        $products = Product::where('is_active', true)
            ->withSum('inventories', 'quantity')
            ->select('id', 'name', 'sku', 'cost_price', 'selling_price')
            ->orderBy('name')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'cost_price' => (float) $p->cost_price,
                'selling_price' => (float) $p->selling_price,
                'total_stock' => (float) ($p->inventories_sum_quantity ?? 0),
            ]);

        return Inertia::render('Movements/CreateEntry', [
            'products' => $products,
            'warehouses' => Warehouse::where('is_active', true)->select('id', 'name', 'code')->orderBy('name')->get(),
        ]);
    }

    public function storeEntry(Request $request, RegisterEntryAction $action): RedirectResponse
    {
        Gate::authorize('inventory.entry');

        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_cost' => ['nullable', 'numeric', 'gte:0'],
            'update_selling_price' => ['nullable', 'boolean'],
            'new_selling_price' => ['nullable', 'numeric', 'gte:0'],
            'reason' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $movement = $action->execute($validated, $request->user());

        return redirect()->route('movements.index')->with('success', "Entrada registrada correctamente con folio {$movement->folio}.");
    }

    public function createExit(): Response
    {
        Gate::authorize('inventory.exit');

        return Inertia::render('Movements/CreateExit', [
            'products' => Product::where('is_active', true)->select('id', 'name', 'sku')->orderBy('name')->get(),
            'warehouses' => Warehouse::where('is_active', true)->select('id', 'name', 'code')->orderBy('name')->get(),
        ]);
    }

    public function storeExit(Request $request, RegisterExitAction $action): RedirectResponse
    {
        Gate::authorize('inventory.exit');

        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'reason' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $movement = $action->execute($validated, $request->user());

        return redirect()->route('movements.index')->with('success', "Salida registrada correctamente con folio {$movement->folio}.");
    }

    public function createAdjustment(): Response
    {
        Gate::authorize('inventory.adjustment');

        return Inertia::render('Movements/CreateAdjustment', [
            'products' => Product::where('is_active', true)->select('id', 'name', 'sku')->orderBy('name')->get(),
            'warehouses' => Warehouse::where('is_active', true)->select('id', 'name', 'code')->orderBy('name')->get(),
        ]);
    }

    public function storeAdjustment(Request $request, RegisterAdjustmentAction $action): RedirectResponse
    {
        Gate::authorize('inventory.adjustment');

        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'real_quantity' => ['required', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $movement = $action->execute($validated, $request->user());

        return redirect()->route('movements.index')->with('success', "Ajuste registrado correctamente con folio {$movement->folio}.");
    }

    public function createTransfer(): Response
    {
        Gate::authorize('inventory.transfer');

        return Inertia::render('Movements/CreateTransfer', [
            'products' => Product::where('is_active', true)->select('id', 'name', 'sku')->orderBy('name')->get(),
            'warehouses' => Warehouse::where('is_active', true)->select('id', 'name', 'code')->orderBy('name')->get(),
        ]);
    }

    public function storeTransfer(Request $request, RegisterTransferAction $action): RedirectResponse
    {
        Gate::authorize('inventory.transfer');

        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'destination_warehouse_id' => ['required', 'exists:warehouses,id', 'different:warehouse_id'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'reason' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $movement = $action->execute($validated, $request->user());

        return redirect()->route('movements.index')->with('success', "Transferencia realizada correctamente con folio {$movement->folio}.");
    }

    /**
     * API endpoint to query stock for product + warehouse.
     */
    public function getStock(Request $request): JsonResponse
    {
        $productId = $request->input('product_id');
        $warehouseId = $request->input('warehouse_id');

        if (! $productId || ! $warehouseId) {
            return response()->json(['quantity' => 0]);
        }

        $inv = Inventory::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        return response()->json([
            'quantity' => $inv ? (float) $inv->quantity : 0.0,
        ]);
    }

    /**
     * Export movements to CSV.
     */
    public function export(Request $request, CsvExportService $csvService): StreamedResponse
    {
        Gate::authorize('reports.export');

        $query = InventoryMovement::with([
            'product:id,name,sku',
            'warehouse:id,name,code',
            'destinationWarehouse:id,name,code',
            'user:id,name',
        ]);

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($warehouseId = $request->input('warehouse_id')) {
            $query->where(function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId)
                    ->orWhere('destination_warehouse_id', $warehouseId);
            });
        }

        if ($productId = $request->input('product_id')) {
            $query->where('product_id', $productId);
        }

        if ($startDate = $request->input('start_date')) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate = $request->input('end_date')) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $headers = [
            'Folio',
            'Fecha',
            'Tipo',
            'SKU',
            'Producto',
            'Almacén Origen',
            'Almacén Destino',
            'Cantidad',
            'Existencia Anterior',
            'Existencia Posterior',
            'Usuario',
            'Motivo',
            'Referencia',
        ];

        $rows = [];
        foreach ($query->latest('id')->cursor() as $m) {
            $rows[] = [
                $m->folio,
                $m->created_at->format('d/m/Y H:i'),
                $m->type_label,
                $m->product?->sku ?? '',
                $m->product?->name ?? '',
                $m->warehouse?->name ?? '',
                $m->destinationWarehouse?->name ?? '',
                (float) $m->quantity,
                (float) $m->previous_quantity,
                (float) $m->new_quantity,
                $m->user?->name ?? '',
                $m->reason,
                $m->reference ?? '',
            ];
        }

        return $csvService->download('movimientos_'.date('Ymd_His').'.csv', $headers, $rows);
    }
}
