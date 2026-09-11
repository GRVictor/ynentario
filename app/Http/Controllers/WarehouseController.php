<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Warehouse;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class WarehouseController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('warehouses.view');

        $warehouses = Warehouse::withCount('inventories')
            ->withSum('inventories', 'quantity')
            ->orderBy('name')
            ->get()
            ->map(fn ($wh) => [
                'id' => $wh->id,
                'code' => $wh->code,
                'name' => $wh->name,
                'description' => $wh->description,
                'address' => $wh->address,
                'manager_name' => $wh->manager_name,
                'phone' => $wh->phone,
                'email' => $wh->email,
                'is_active' => $wh->is_active,
                'products_count' => $wh->inventories_count,
                'total_quantity' => (float) ($wh->inventories_sum_quantity ?? 0),
            ]);

        return Inertia::render('Warehouses/Index', [
            'warehouses' => $warehouses,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('warehouses.create');

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:warehouses,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'manager_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $warehouse = Warehouse::create($validated);

        ActivityLogger::log('warehouse_created', "Almacén '{$warehouse->name}' ({$warehouse->code}) creado.", $warehouse);

        return redirect()->route('warehouses.index')->with('success', 'Almacén creado correctamente.');
    }

    public function show(Warehouse $warehouse, Request $request): Response
    {
        Gate::authorize('warehouses.view');

        $search = $request->input('search');

        $inventories = Inventory::where('warehouse_id', $warehouse->id)
            ->whereHas('product')
            ->with(['product.category:id,name', 'product.unit:id,abbreviation'])
            ->when($search, function ($q) use ($search) {
                $q->whereHas('product', function ($p) use ($search) {
                    $p->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->paginate(15)
            ->withQueryString()
            ->through(function ($inv) {
                return [
                    'id' => $inv->id,
                    'product_id' => $inv->product_id,
                    'product_sku' => $inv->product->sku,
                    'product_name' => $inv->product->name,
                    'category_name' => $inv->product->category?->name ?? 'Sin categoría',
                    'unit_abbr' => $inv->product->unit?->abbreviation ?? 'pza',
                    'quantity' => (float) $inv->quantity,
                    'min_stock' => (float) $inv->product->min_stock,
                    'status' => $inv->stock_status,
                    'updated_at' => $inv->updated_at->format('d/m/Y H:i'),
                ];
            });

        return Inertia::render('Warehouses/Show', [
            'warehouse' => [
                'id' => $warehouse->id,
                'code' => $warehouse->code,
                'name' => $warehouse->name,
                'description' => $warehouse->description,
                'address' => $warehouse->address,
                'manager_name' => $warehouse->manager_name,
                'phone' => $warehouse->phone,
                'email' => $warehouse->email,
                'is_active' => $warehouse->is_active,
                'total_products' => $warehouse->inventories()->count(),
                'total_stock' => (float) $warehouse->inventories()->sum('quantity'),
            ],
            'inventories' => $inventories,
            'filters' => $request->only(['search']),
        ]);
    }

    public function update(Request $request, Warehouse $warehouse): RedirectResponse
    {
        Gate::authorize('warehouses.update');

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('warehouses', 'code')->ignore($warehouse->id)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'manager_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $warehouse->update($validated);

        ActivityLogger::log('warehouse_updated', "Almacén '{$warehouse->name}' ({$warehouse->code}) actualizado.", $warehouse);

        return redirect()->route('warehouses.index')->with('success', 'Almacén actualizado correctamente.');
    }

    public function destroy(Warehouse $warehouse): RedirectResponse
    {
        Gate::authorize('warehouses.delete');

        // Verificar si el almacén posee existencias o historial de movimientos
        $hasStock = $warehouse->inventories()->where('quantity', '>', 0)->exists();
        $hasMovements = $warehouse->movements()->exists() || $warehouse->inboundTransfers()->exists();

        if ($hasStock || $hasMovements) {
            $warehouse->update(['is_active' => false]);

            ActivityLogger::log('warehouse_deactivated', "Almacén '{$warehouse->name}' desactivado.", $warehouse);

            return redirect()->route('warehouses.index')->with('info', 'El almacén tiene existencias o movimientos y se marcó como inactivo.');
        }

        $warehouse->delete();

        ActivityLogger::log('warehouse_deleted', "Almacén '{$warehouse->name}' eliminado.");

        return redirect()->route('warehouses.index')->with('success', 'Almacén eliminado correctamente.');
    }
}
