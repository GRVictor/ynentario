<?php

use App\Http\Controllers\CatalogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryMovementController;
use App\Http\Controllers\KardexController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware(['auth'])->group(function () {
    // Panel de control
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Productos
    Route::get('/products/template/download', [ProductController::class, 'downloadTemplate'])->name('products.template');
    Route::get('/products/import', [ProductController::class, 'importPage'])->name('products.import');
    Route::post('/products/import', [ProductController::class, 'processImport'])->name('products.import.process');
    Route::get('/products/export/csv', [ProductController::class, 'export'])->name('products.export');
    Route::get('/products/api/quick-search', [ProductController::class, 'quickSearch'])->name('products.quick-search');
    Route::resource('products', ProductController::class);

    // Catálogos
    Route::get('/catalogs', [CatalogController::class, 'index'])->name('catalogs.index');
    Route::post('/catalogs/categories', [CatalogController::class, 'storeCategory'])->name('catalogs.categories.store');
    Route::put('/catalogs/categories/{category}', [CatalogController::class, 'updateCategory'])->name('catalogs.categories.update');
    Route::delete('/catalogs/categories/{category}', [CatalogController::class, 'destroyCategory'])->name('catalogs.categories.destroy');

    Route::post('/catalogs/brands', [CatalogController::class, 'storeBrand'])->name('catalogs.brands.store');
    Route::put('/catalogs/brands/{brand}', [CatalogController::class, 'updateBrand'])->name('catalogs.brands.update');
    Route::delete('/catalogs/brands/{brand}', [CatalogController::class, 'destroyBrand'])->name('catalogs.brands.destroy');

    Route::post('/catalogs/units', [CatalogController::class, 'storeUnit'])->name('catalogs.units.store');
    Route::put('/catalogs/units/{unit}', [CatalogController::class, 'updateUnit'])->name('catalogs.units.update');
    Route::delete('/catalogs/units/{unit}', [CatalogController::class, 'destroyUnit'])->name('catalogs.units.destroy');

    Route::post('/catalogs/suppliers', [CatalogController::class, 'storeSupplier'])->name('catalogs.suppliers.store');
    Route::put('/catalogs/suppliers/{supplier}', [CatalogController::class, 'updateSupplier'])->name('catalogs.suppliers.update');
    Route::delete('/catalogs/suppliers/{supplier}', [CatalogController::class, 'destroySupplier'])->name('catalogs.suppliers.destroy');

    // Almacenes
    Route::resource('warehouses', WarehouseController::class)->except(['create', 'edit']);

    // Movimientos de inventario
    Route::get('/movements', [InventoryMovementController::class, 'index'])->name('movements.index');
    Route::get('/movements/export/csv', [InventoryMovementController::class, 'export'])->name('movements.export');
    Route::get('/movements/api/stock', [InventoryMovementController::class, 'getStock'])->name('movements.stock');

    Route::get('/movements/entry/create', [InventoryMovementController::class, 'createEntry'])->name('movements.entry.create');
    Route::post('/movements/entry', [InventoryMovementController::class, 'storeEntry'])->name('movements.entry.store');

    Route::get('/movements/exit/create', [InventoryMovementController::class, 'createExit'])->name('movements.exit.create');
    Route::post('/movements/exit', [InventoryMovementController::class, 'storeExit'])->name('movements.exit.store');

    Route::get('/movements/adjustment/create', [InventoryMovementController::class, 'createAdjustment'])->name('movements.adjustment.create');
    Route::post('/movements/adjustment', [InventoryMovementController::class, 'storeAdjustment'])->name('movements.adjustment.store');

    Route::get('/movements/transfer/create', [InventoryMovementController::class, 'createTransfer'])->name('movements.transfer.create');
    Route::post('/movements/transfer', [InventoryMovementController::class, 'storeTransfer'])->name('movements.transfer.store');

    // Kardex
    Route::get('/kardex', [KardexController::class, 'index'])->name('kardex.index');
    Route::get('/kardex/export/csv', [KardexController::class, 'export'])->name('kardex.export');

    // Reportes
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/current-stock', [ReportController::class, 'currentStock'])->name('reports.current-stock');
    Route::get('/reports/current-stock/export', [ReportController::class, 'exportCurrentStock'])->name('reports.current-stock.export');
    Route::get('/reports/low-stock', [ReportController::class, 'lowStock'])->name('reports.low-stock');
    Route::get('/reports/low-stock/export', [ReportController::class, 'exportLowStock'])->name('reports.low-stock.export');

    // Usuarios
    Route::resource('users', UserController::class)->except(['create', 'edit', 'show']);

    // Perfil de usuario
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
