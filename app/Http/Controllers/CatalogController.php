<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Unit;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CatalogController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('products.view');

        $activeTab = $request->input('tab', 'categories');

        return Inertia::render('Catalogs/Index', [
            'activeTab' => $activeTab,
            'categories' => Category::withCount('products')->orderBy('name')->get(),
            'brands' => Brand::withCount('products')->orderBy('name')->get(),
            'units' => Unit::withCount('products')->orderBy('name')->get(),
            'suppliers' => Supplier::withCount('products')->orderBy('name')->get(),
        ]);
    }

    // Categorías
    public function storeCategory(Request $request): RedirectResponse
    {
        Gate::authorize('products.create');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $cat = Category::create($validated);
        ActivityLogger::log('category_created', "Categoría '{$cat->name}' creada.", $cat);

        return redirect()->route('catalogs.index', ['tab' => 'categories'])->with('success', 'Categoría creada correctamente.');
    }

    public function updateCategory(Request $request, Category $category): RedirectResponse
    {
        Gate::authorize('products.update');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name,'.$category->id],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $category->update($validated);
        ActivityLogger::log('category_updated', "Categoría '{$category->name}' actualizada.", $category);

        return redirect()->route('catalogs.index', ['tab' => 'categories'])->with('success', 'Categoría actualizada correctamente.');
    }

    public function destroyCategory(Category $category): RedirectResponse
    {
        Gate::authorize('products.delete');

        if ($category->products()->exists()) {
            $category->update(['is_active' => false]);

            return redirect()->route('catalogs.index', ['tab' => 'categories'])->with('info', 'La categoría tiene productos asociados y se marcó como inactiva.');
        }

        $category->delete();

        return redirect()->route('catalogs.index', ['tab' => 'categories'])->with('success', 'Categoría eliminada.');
    }

    // Marcas
    public function storeBrand(Request $request): RedirectResponse
    {
        Gate::authorize('products.create');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:brands,name'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $brand = Brand::create($validated);
        ActivityLogger::log('brand_created', "Marca '{$brand->name}' creada.", $brand);

        return redirect()->route('catalogs.index', ['tab' => 'brands'])->with('success', 'Marca creada correctamente.');
    }

    public function updateBrand(Request $request, Brand $brand): RedirectResponse
    {
        Gate::authorize('products.update');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:brands,name,'.$brand->id],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $brand->update($validated);
        ActivityLogger::log('brand_updated', "Marca '{$brand->name}' actualizada.", $brand);

        return redirect()->route('catalogs.index', ['tab' => 'brands'])->with('success', 'Marca actualizada.');
    }

    public function destroyBrand(Brand $brand): RedirectResponse
    {
        Gate::authorize('products.delete');

        if ($brand->products()->exists()) {
            $brand->update(['is_active' => false]);

            return redirect()->route('catalogs.index', ['tab' => 'brands'])->with('info', 'La marca tiene productos asociados y se marcó como inactiva.');
        }

        $brand->delete();

        return redirect()->route('catalogs.index', ['tab' => 'brands'])->with('success', 'Marca eliminada.');
    }

    // Unidades de medida
    public function storeUnit(Request $request): RedirectResponse
    {
        Gate::authorize('products.create');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:units,name'],
            'abbreviation' => ['required', 'string', 'max:20'],
        ]);

        Unit::create($validated);

        return redirect()->route('catalogs.index', ['tab' => 'units'])->with('success', 'Unidad de medida creada correctamente.');
    }

    public function updateUnit(Request $request, Unit $unit): RedirectResponse
    {
        Gate::authorize('products.update');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:units,name,'.$unit->id],
            'abbreviation' => ['required', 'string', 'max:20'],
        ]);

        $unit->update($validated);

        return redirect()->route('catalogs.index', ['tab' => 'units'])->with('success', 'Unidad de medida actualizada.');
    }

    public function destroyUnit(Unit $unit): RedirectResponse
    {
        Gate::authorize('products.delete');

        if ($unit->products()->exists()) {
            return redirect()->route('catalogs.index', ['tab' => 'units'])->with('error', 'No se puede eliminar la unidad porque tiene productos asociados.');
        }

        $unit->delete();

        return redirect()->route('catalogs.index', ['tab' => 'units'])->with('success', 'Unidad eliminada.');
    }

    // Proveedores
    public function storeSupplier(Request $request): RedirectResponse
    {
        Gate::authorize('products.create');

        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $supplier = Supplier::create($validated);
        ActivityLogger::log('supplier_created', "Proveedor '{$supplier->name}' registrado.", $supplier);

        return redirect()->route('catalogs.index', ['tab' => 'suppliers'])->with('success', 'Proveedor registrado correctamente.');
    }

    public function updateSupplier(Request $request, Supplier $supplier): RedirectResponse
    {
        Gate::authorize('products.update');

        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $supplier->update($validated);
        ActivityLogger::log('supplier_updated', "Proveedor '{$supplier->name}' actualizado.", $supplier);

        return redirect()->route('catalogs.index', ['tab' => 'suppliers'])->with('success', 'Proveedor actualizado correctamente.');
    }

    public function destroySupplier(Supplier $supplier): RedirectResponse
    {
        Gate::authorize('products.delete');

        if ($supplier->products()->exists()) {
            $supplier->update(['is_active' => false]);

            return redirect()->route('catalogs.index', ['tab' => 'suppliers'])->with('info', 'El proveedor tiene productos asociados y se marcó como inactivo.');
        }

        $supplier->delete();

        return redirect()->route('catalogs.index', ['tab' => 'suppliers'])->with('success', 'Proveedor eliminado.');
    }
}
