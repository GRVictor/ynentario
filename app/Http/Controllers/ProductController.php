<?php

namespace App\Http\Controllers;

use App\Actions\Products\ImportProductsCsvAction;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\ActivityLogger;
use App\Services\CsvExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('products.view');

        $query = Product::with(['category:id,name', 'brand:id,name', 'unit:id,name,abbreviation'])
            ->withSum('inventories', 'quantity');

        // Búsqueda por término (nombre, SKU, código de barras o código interno)
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%")
                    ->orWhere('internal_code', 'like', "%{$search}%");
            });
        }

        // Filtro por categoría
        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        // Filtro por marca
        if ($brandId = $request->input('brand_id')) {
            $query->where('brand_id', $brandId);
        }

        // Filtro por estado activo/inactivo
        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        // Filtro por existencias en almacén específico
        if ($warehouseId = $request->input('warehouse_id')) {
            $query->whereHas('inventories', function ($q) use ($warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            });
        }

        // Ordenamiento de resultados
        $sortField = $request->input('sort', 'created_at');
        $sortDirection = $request->input('direction', 'desc');

        if (in_array($sortField, ['name', 'sku', 'cost_price', 'selling_price', 'created_at'])) {
            $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
        } else {
            $query->latest('id');
        }

        $products = $query->paginate(15)->withQueryString();

        // Filtro por estado de existencias (disponible, bajo stock, agotado)
        $stockStatusFilter = $request->input('stock_status');

        $items = $products->through(function ($product) {
            return [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'category_name' => $product->category?->name ?? 'Sin categoría',
                'brand_name' => $product->brand?->name ?? 'Sin marca',
                'unit_abbr' => $product->unit?->abbreviation ?? 'pza',
                'cost_price' => (float) $product->cost_price,
                'selling_price' => (float) $product->selling_price,
                'min_stock' => (float) $product->min_stock,
                'reorder_point' => (float) $product->reorder_point,
                'total_stock' => (float) ($product->inventories_sum_quantity ?? 0),
                'stock_status' => $product->stock_status,
                'is_active' => $product->is_active,
            ];
        });

        // Filtrado en colección si se solicita estado de existencias
        if ($stockStatusFilter) {
            $filteredData = $items->getCollection()->filter(fn ($item) => $item['stock_status'] === $stockStatusFilter)->values();
            $items->setCollection($filteredData);
        }

        return Inertia::render('Products/Index', [
            'products' => $items,
            'filters' => $request->only(['search', 'category_id', 'brand_id', 'stock_status', 'warehouse_id', 'is_active', 'sort', 'direction']),
            'categories' => Category::where('is_active', true)->select('id', 'name')->orderBy('name')->get(),
            'brands' => Brand::where('is_active', true)->select('id', 'name')->orderBy('name')->get(),
            'warehouses' => Warehouse::where('is_active', true)->select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('products.create');

        return Inertia::render('Products/Create', [
            'categories' => Category::where('is_active', true)->select('id', 'name')->orderBy('name')->get(),
            'brands' => Brand::where('is_active', true)->select('id', 'name')->orderBy('name')->get(),
            'units' => Unit::select('id', 'name', 'abbreviation')->orderBy('name')->get(),
            'suppliers' => Supplier::where('is_active', true)->select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('products.create');

        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:100', 'unique:products,sku'],
            'internal_code' => ['nullable', 'string', 'max:100'],
            'barcode' => ['nullable', 'string', 'max:100', 'unique:products,barcode'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'unit_id' => ['required', 'exists:units,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'min_stock' => ['required', 'numeric', 'min:0'],
            'max_stock' => ['nullable', 'numeric', 'min:0'],
            'reorder_point' => ['required', 'numeric', 'min:0'],
            'default_location' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $product = Product::create($validated);

        ActivityLogger::log('product_created', "Producto '{$product->name}' (SKU: {$product->sku}) creado.", $product);

        return redirect()->route('products.show', $product)->with('success', 'Producto creado correctamente.');
    }

    public function show(Product $product): Response
    {
        Gate::authorize('products.view');

        $product->load(['category', 'brand', 'unit', 'supplier']);
        $product->loadSum('inventories', 'quantity');

        // Inventories per warehouse
        $allWarehouses = Warehouse::where('is_active', true)->get();
        $productInventories = $product->inventories()->with('warehouse')->get()->keyBy('warehouse_id');

        $warehouseBalances = $allWarehouses->map(function ($wh) use ($productInventories, $product) {
            $inv = $productInventories->get($wh->id);
            $qty = $inv ? (float) $inv->quantity : 0.0;

            $status = 'in_stock';
            if ($qty <= 0) {
                $status = 'out_of_stock';
            } elseif ($qty <= max((float) $product->min_stock, (float) $product->reorder_point)) {
                $status = 'low_stock';
            }

            return [
                'warehouse_id' => $wh->id,
                'warehouse_name' => $wh->name,
                'warehouse_code' => $wh->code,
                'quantity' => $qty,
                'status' => $status,
                'updated_at' => $inv?->updated_at?->format('d/m/Y H:i') ?? 'Sin registros',
            ];
        });

        // Recent movements
        $movements = $product->movements()
            ->with(['warehouse:id,name', 'destinationWarehouse:id,name', 'user:id,name'])
            ->latest('id')
            ->take(15)
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'folio' => $m->folio,
                'type' => $m->type,
                'type_label' => $m->type_label,
                'warehouse_name' => $m->warehouse?->name ?? 'N/A',
                'destination_warehouse_name' => $m->destinationWarehouse?->name,
                'quantity' => (float) $m->quantity,
                'previous_quantity' => (float) $m->previous_quantity,
                'new_quantity' => (float) $m->new_quantity,
                'user_name' => $m->user?->name ?? 'N/A',
                'reason' => $m->reason,
                'reference' => $m->reference,
                'created_at' => $m->created_at->format('d/m/Y H:i'),
            ]);

        return Inertia::render('Products/Show', [
            'product' => [
                'id' => $product->id,
                'sku' => $product->sku,
                'internal_code' => $product->internal_code,
                'barcode' => $product->barcode,
                'name' => $product->name,
                'description' => $product->description,
                'category_name' => $product->category?->name ?? 'Sin categoría',
                'brand_name' => $product->brand?->name ?? 'Sin marca',
                'unit_name' => $product->unit?->name ?? 'Pieza',
                'unit_abbr' => $product->unit?->abbreviation ?? 'pza',
                'supplier_name' => $product->supplier?->name ?? 'No asignado',
                'cost_price' => (float) $product->cost_price,
                'selling_price' => (float) $product->selling_price,
                'min_stock' => (float) $product->min_stock,
                'max_stock' => $product->max_stock ? (float) $product->max_stock : null,
                'reorder_point' => (float) $product->reorder_point,
                'default_location' => $product->default_location,
                'notes' => $product->notes,
                'is_active' => $product->is_active,
                'total_stock' => (float) ($product->inventories_sum_quantity ?? 0),
                'stock_status' => $product->stock_status,
                'has_movements' => $product->hasMovements(),
                'created_at' => $product->created_at->format('d/m/Y H:i'),
                'updated_at' => $product->updated_at->format('d/m/Y H:i'),
            ],
            'warehouseBalances' => $warehouseBalances,
            'movements' => $movements,
        ]);
    }

    public function edit(Product $product): Response
    {
        Gate::authorize('products.update');

        return Inertia::render('Products/Edit', [
            'product' => $product,
            'categories' => Category::where('is_active', true)->select('id', 'name')->orderBy('name')->get(),
            'brands' => Brand::where('is_active', true)->select('id', 'name')->orderBy('name')->get(),
            'units' => Unit::select('id', 'name', 'abbreviation')->orderBy('name')->get(),
            'suppliers' => Supplier::where('is_active', true)->select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        Gate::authorize('products.update');

        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($product->id)],
            'internal_code' => ['nullable', 'string', 'max:100'],
            'barcode' => ['nullable', 'string', 'max:100', Rule::unique('products', 'barcode')->ignore($product->id)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'unit_id' => ['required', 'exists:units,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'min_stock' => ['required', 'numeric', 'min:0'],
            'max_stock' => ['nullable', 'numeric', 'min:0'],
            'reorder_point' => ['required', 'numeric', 'min:0'],
            'default_location' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $product->update($validated);

        ActivityLogger::log('product_updated', "Producto '{$product->name}' (SKU: {$product->sku}) actualizado.", $product);

        return redirect()->route('products.show', $product)->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        Gate::authorize('products.delete');

        // Requirement: Do not physically delete products that have inventory movements
        if ($product->hasMovements()) {
            $product->update(['is_active' => false]);

            ActivityLogger::log('product_deactivated', "Producto '{$product->name}' desactivado por poseer historial de movimientos.", $product);

            return redirect()->route('products.index')->with('info', 'El producto tiene movimientos registrados y se marcó como inactivo para conservar su historial.');
        }

        $name = $product->name;
        $product->delete();

        ActivityLogger::log('product_deleted', "Producto '{$name}' eliminado físicamente.");

        return redirect()->route('products.index')->with('success', 'Producto eliminado correctamente.');
    }

    /**
     * Download CSV template for imports.
     */
    public function downloadTemplate(CsvExportService $csvService): StreamedResponse
    {
        $headers = [
            'sku',
            'nombre',
            'categoria',
            'marca',
            'unidad',
            'precio_costo',
            'precio_venta',
            'stock_minimo',
            'stock_maximo',
            'punto_reorden',
            'codigo_barras',
            'codigo_interno',
            'proveedor',
            'descripcion',
        ];

        $sampleRows = [
            [
                'PROD-001',
                'Laptop Pro 15 pulgadas',
                'Electrónica',
                'TechBrand',
                'Pieza',
                '12000.00',
                '16500.00',
                '5',
                '50',
                '10',
                '7501234567890',
                'INT-LP-01',
                'Distribuidora Global',
                'Equipo portátil de alto rendimiento',
            ],
            [
                'PROD-002',
                'Mouse Inalámbrico Ergonómico',
                'Accesorios',
                'TechBrand',
                'Pieza',
                '250.00',
                '450.00',
                '15',
                '100',
                '25',
                '7501234567891',
                'INT-MS-02',
                'Distribuidora Global',
                'Mouse óptico bluetooth 2.4GHz',
            ],
        ];

        return $csvService->download('plantilla_productos_ynentario.csv', $headers, $sampleRows);
    }

    /**
     * Muestra la vista para la importación masiva de productos vía CSV.
     */
    public function importPage(): Response
    {
        Gate::authorize('imports.execute');

        return Inertia::render('Products/Import');
    }

    /**
     * Process CSV file upload.
     */
    public function processImport(Request $request, ImportProductsCsvAction $importAction): RedirectResponse
    {
        Gate::authorize('imports.execute');

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
            'update_existing' => ['boolean'],
        ]);

        $result = $importAction->execute(
            $request->file('file'),
            $request->boolean('update_existing'),
            $request->user()
        );

        return redirect()->route('products.import')->with('importResult', $result);
    }

    /**
     * Export products to CSV respecting active filters.
     */
    public function export(Request $request, CsvExportService $csvService): StreamedResponse
    {
        Gate::authorize('reports.export');

        $query = Product::with(['category', 'brand', 'unit', 'supplier'])
            ->withSum('inventories', 'quantity');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        if ($brandId = $request->input('brand_id')) {
            $query->where('brand_id', $brandId);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $headers = [
            'SKU',
            'Código de Barras',
            'Código Interno',
            'Nombre',
            'Categoría',
            'Marca',
            'Unidad',
            'Proveedor',
            'Precio Costo',
            'Precio Venta',
            'Stock Total',
            'Stock Mínimo',
            'Punto Reorden',
            'Ubicación',
            'Estado',
            'Fecha Registro',
        ];

        $rows = [];
        foreach ($query->cursor() as $product) {
            $rows[] = [
                $product->sku,
                $product->barcode ?? '',
                $product->internal_code ?? '',
                $product->name,
                $product->category?->name ?? 'Sin categoría',
                $product->brand?->name ?? 'Sin marca',
                $product->unit?->name ?? 'Pieza',
                $product->supplier?->name ?? '',
                number_format((float) $product->cost_price, 2, '.', ''),
                number_format((float) $product->selling_price, 2, '.', ''),
                (float) ($product->inventories_sum_quantity ?? 0),
                (float) $product->min_stock,
                (float) $product->reorder_point,
                $product->default_location ?? '',
                $product->is_active ? 'Activo' : 'Inactivo',
                $product->created_at->format('d/m/Y H:i'),
            ];
        }

        return $csvService->download('productos_'.date('Ymd_His').'.csv', $headers, $rows);
    }

    /**
     * API endpoint for quick product & stock search (omnibox).
     */
    public function quickSearch(Request $request): JsonResponse
    {
        Gate::authorize('products.view');

        $search = trim((string) $request->input('q', ''));
        if (strlen($search) < 1) {
            return response()->json([]);
        }

        $products = Product::where('is_active', true)
            ->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            })
            ->with(['category:id,name', 'unit:id,name,abbreviation', 'inventories.warehouse:id,name,code'])
            ->limit(8)
            ->get()
            ->map(function ($p) {
                $totalStock = (float) $p->inventories->sum('quantity');

                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'sku' => $p->sku,
                    'barcode' => $p->barcode,
                    'category' => $p->category?->name ?? 'General',
                    'unit' => $p->unit?->abbreviation ?? 'pza',
                    'selling_price' => (float) $p->selling_price,
                    'min_stock' => (float) $p->min_stock,
                    'total_stock' => $totalStock,
                    'warehouses' => $p->inventories->map(fn ($inv) => [
                        'id' => $inv->warehouse_id,
                        'name' => $inv->warehouse?->name ?? 'Almacén',
                        'code' => $inv->warehouse?->code ?? '',
                        'quantity' => (float) $inv->quantity,
                    ]),
                ];
            });

        return response()->json($products);
    }
}
