<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\CsvExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('reports.view');

        return Inertia::render('Reports/Index');
    }

    /**
     * Current Stock Report.
     */
    public function currentStock(Request $request): Response
    {
        Gate::authorize('reports.view');

        $query = Inventory::with(['product.category:id,name', 'product.brand:id,name', 'product.unit:id,abbreviation', 'warehouse:id,name,code'])
            ->whereHas('product');

        if ($warehouseId = $request->input('warehouse_id')) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($categoryId = $request->input('category_id')) {
            $query->whereHas('product', fn ($q) => $q->where('category_id', $categoryId));
        }

        if ($brandId = $request->input('brand_id')) {
            $query->whereHas('product', fn ($q) => $q->where('brand_id', $brandId));
        }

        if ($search = $request->input('search')) {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $inventories = $query->paginate(20)
            ->withQueryString()
            ->through(function ($inv) {
                return [
                    'id' => $inv->id,
                    'sku' => $inv->product->sku,
                    'product_name' => $inv->product->name,
                    'warehouse_name' => $inv->warehouse->name,
                    'category_name' => $inv->product->category?->name ?? 'Sin categoría',
                    'brand_name' => $inv->product->brand?->name ?? 'Sin marca',
                    'unit_abbr' => $inv->product->unit?->abbreviation ?? 'pza',
                    'quantity' => (float) $inv->quantity,
                    'min_stock' => (float) $inv->product->min_stock,
                    'reorder_point' => (float) $inv->product->reorder_point,
                    'status' => $inv->stock_status,
                ];
            });

        return Inertia::render('Reports/CurrentStock', [
            'inventories' => $inventories,
            'filters' => $request->only(['warehouse_id', 'category_id', 'brand_id', 'search']),
            'warehouses' => Warehouse::where('is_active', true)->select('id', 'name')->orderBy('name')->get(),
            'categories' => Category::where('is_active', true)->select('id', 'name')->orderBy('name')->get(),
            'brands' => Brand::where('is_active', true)->select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    /**
     * Export Current Stock Report.
     */
    public function exportCurrentStock(Request $request, CsvExportService $csvService): StreamedResponse
    {
        Gate::authorize('reports.export');

        $query = Inventory::with(['product.category', 'product.brand', 'warehouse'])
            ->whereHas('product');

        if ($warehouseId = $request->input('warehouse_id')) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($categoryId = $request->input('category_id')) {
            $query->whereHas('product', fn ($q) => $q->where('category_id', $categoryId));
        }

        if ($brandId = $request->input('brand_id')) {
            $query->whereHas('product', fn ($q) => $q->where('brand_id', $brandId));
        }

        $headers = [
            'SKU',
            'Producto',
            'Almacén',
            'Categoría',
            'Marca',
            'Existencia',
            'Stock Mínimo',
            'Punto de Reorden',
            'Estado',
        ];

        $rows = [];
        foreach ($query->cursor() as $inv) {
            $rows[] = [
                $inv->product->sku,
                $inv->product->name,
                $inv->warehouse->name,
                $inv->product->category?->name ?? '',
                $inv->product->brand?->name ?? '',
                (float) $inv->quantity,
                (float) $inv->product->min_stock,
                (float) $inv->product->reorder_point,
                $inv->stock_status === 'out_of_stock' ? 'Sin existencias' : ($inv->stock_status === 'low_stock' ? 'Stock bajo' : 'Disponible'),
            ];
        }

        return $csvService->download('reporte_existencias_'.date('Ymd_His').'.csv', $headers, $rows);
    }

    /**
     * Low Stock Report.
     */
    public function lowStock(Request $request): Response
    {
        Gate::authorize('reports.view');

        $query = Product::where('is_active', true)
            ->with(['category:id,name', 'brand:id,name', 'unit:id,abbreviation'])
            ->withSum('inventories', 'quantity');

        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Obtener productos y filtrar únicamente aquellos con bajo stock o agotados
        $products = $query->get()->filter(function ($product) {
            $stock = (float) ($product->inventories_sum_quantity ?? 0);
            $threshold = max((float) $product->min_stock, (float) $product->reorder_point);

            return $stock <= 0 || ($threshold > 0 && $stock <= $threshold);
        })->values();

        return Inertia::render('Reports/LowStock', [
            'products' => $products->map(fn ($p) => [
                'id' => $p->id,
                'sku' => $p->sku,
                'name' => $p->name,
                'category_name' => $p->category?->name ?? 'Sin categoría',
                'brand_name' => $p->brand?->name ?? 'Sin marca',
                'unit_abbr' => $p->unit?->abbreviation ?? 'pza',
                'stock' => (float) ($p->inventories_sum_quantity ?? 0),
                'min_stock' => (float) $p->min_stock,
                'reorder_point' => (float) $p->reorder_point,
                'status' => (float) ($p->inventories_sum_quantity ?? 0) <= 0 ? 'out_of_stock' : 'low_stock',
            ]),
            'filters' => $request->only(['category_id', 'search']),
            'categories' => Category::where('is_active', true)->select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    /**
     * Export Low Stock Report.
     */
    public function exportLowStock(Request $request, CsvExportService $csvService): StreamedResponse
    {
        Gate::authorize('reports.export');

        $query = Product::where('is_active', true)
            ->with(['category', 'brand', 'unit'])
            ->withSum('inventories', 'quantity');

        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        $headers = [
            'SKU',
            'Producto',
            'Categoría',
            'Marca',
            'Stock Actual',
            'Stock Mínimo',
            'Punto de Reorden',
            'Estado de Alerta',
        ];

        $rows = [];
        foreach ($query->cursor() as $p) {
            $stock = (float) ($p->inventories_sum_quantity ?? 0);
            $threshold = max((float) $p->min_stock, (float) $p->reorder_point);

            if ($stock <= 0 || ($threshold > 0 && $stock <= $threshold)) {
                $rows[] = [
                    $p->sku,
                    $p->name,
                    $p->category?->name ?? '',
                    $p->brand?->name ?? '',
                    $stock,
                    (float) $p->min_stock,
                    (float) $p->reorder_point,
                    $stock <= 0 ? 'Sin existencias' : 'Stock bajo',
                ];
            }
        }

        return $csvService->download('reporte_stock_bajo_'.date('Ymd_His').'.csv', $headers, $rows);
    }
}
