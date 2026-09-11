<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();

        $activeProductsCount = Product::where('is_active', true)->count();
        $activeWarehousesCount = Warehouse::where('is_active', true)->count();

        // Calcular productos con bajo stock y agotados (existencias <= stock mínimo o punto de reorden)
        $productsWithStock = Product::where('is_active', true)
            ->withSum('inventories', 'quantity')
            ->get();

        $outOfStockCount = 0;
        $lowStockCount = 0;
        $lowStockProducts = [];

        foreach ($productsWithStock as $product) {
            $stock = (float) ($product->inventories_sum_quantity ?? 0);
            $threshold = max((float) $product->min_stock, (float) $product->reorder_point);

            if ($stock <= 0) {
                $outOfStockCount++;
                if (count($lowStockProducts) < 6) {
                    $lowStockProducts[] = [
                        'id' => $product->id,
                        'name' => $product->name,
                        'sku' => $product->sku,
                        'stock' => $stock,
                        'min_stock' => (float) $product->min_stock,
                        'status' => 'out_of_stock',
                    ];
                }
            } elseif ($threshold > 0 && $stock <= $threshold) {
                $lowStockCount++;
                if (count($lowStockProducts) < 6) {
                    $lowStockProducts[] = [
                        'id' => $product->id,
                        'name' => $product->name,
                        'sku' => $product->sku,
                        'stock' => $stock,
                        'min_stock' => (float) $product->min_stock,
                        'status' => 'low_stock',
                    ];
                }
            }
        }

        // Métricas de movimientos del día y mes actual
        $movementsTodayCount = InventoryMovement::whereDate('created_at', $today)->count();

        $monthEntriesCount = InventoryMovement::where('type', 'entry')
            ->where('created_at', '>=', $startOfMonth)
            ->count();

        $monthExitsCount = InventoryMovement::where('type', 'exit')
            ->where('created_at', '>=', $startOfMonth)
            ->count();

        // Datos para la gráfica de movimientos de los últimos 7 días
        $chartDates = [];
        $entriesSeries = [];
        $exitsSeries = [];

        for ($i = 6; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);
            $dayFormatted = $day->format('d/m');
            $dateStr = $day->toDateString();

            $chartDates[] = $dayFormatted;

            $entriesSeries[] = (int) InventoryMovement::where('type', 'entry')
                ->whereDate('created_at', $dateStr)
                ->sum('quantity');

            $exitsSeries[] = (int) InventoryMovement::where('type', 'exit')
                ->whereDate('created_at', $dateStr)
                ->sum('quantity');
        }

        // Los 5 productos con mayor volumen de movimientos
        $topProducts = InventoryMovement::select('product_id', DB::raw('SUM(quantity) as total_quantity'), DB::raw('COUNT(*) as total_movements'))
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->with('product:id,name,sku')
            ->take(5)
            ->get()
            ->map(fn ($item) => [
                'id' => $item->product_id,
                'name' => $item->product->name ?? 'N/A',
                'sku' => $item->product->sku ?? 'N/A',
                'quantity' => (float) $item->total_quantity,
                'movements' => $item->total_movements,
            ]);

        // Movimientos recientes en el inventario
        $recentMovements = InventoryMovement::with(['product:id,name,sku', 'warehouse:id,name', 'user:id,name'])
            ->latest('id')
            ->take(6)
            ->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'folio' => $m->folio,
                'type' => $m->type,
                'type_label' => $m->type_label,
                'product_name' => $m->product?->name ?? 'N/A',
                'product_sku' => $m->product?->sku ?? 'N/A',
                'warehouse_name' => $m->warehouse?->name ?? 'N/A',
                'quantity' => (float) $m->quantity,
                'user_name' => $m->user?->name ?? 'N/A',
                'created_at' => $m->created_at->format('d/m/Y H:i'),
            ]);

        return Inertia::render('Dashboard', [
            'metrics' => [
                'activeProducts' => $activeProductsCount,
                'activeWarehouses' => $activeWarehousesCount,
                'lowStock' => $lowStockCount,
                'outOfStock' => $outOfStockCount,
                'movementsToday' => $movementsTodayCount,
                'monthEntries' => $monthEntriesCount,
                'monthExits' => $monthExitsCount,
            ],
            'chartData' => [
                'labels' => $chartDates,
                'entries' => $entriesSeries,
                'exits' => $exitsSeries,
            ],
            'topProducts' => $topProducts,
            'recentMovements' => $recentMovements,
            'lowStockProducts' => $lowStockProducts,
        ]);
    }
}
