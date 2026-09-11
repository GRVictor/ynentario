<?php

namespace App\Actions\Products;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ImportProductsCsvAction
{
    /**
     * @return array{
     *     total: int,
     *     imported: int,
     *     updated: int,
     *     failed: int,
     *     errors: list<array{row: int, field: string, message: string}>
     * }
     */
    public function execute(UploadedFile $file, bool $updateExisting, User $user): array
    {
        $path = $file->getRealPath();
        $handle = fopen($path, 'r');

        if (! $handle) {
            return [
                'total' => 0,
                'imported' => 0,
                'updated' => 0,
                'failed' => 0,
                'errors' => [['row' => 0, 'field' => 'archivo', 'message' => 'No se pudo abrir el archivo CSV.']],
            ];
        }

        // Detectar y omitir marca BOM de UTF-8 si existe
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $header = fgetcsv($handle, 2048, ',');
        if (! $header) {
            fclose($handle);

            return [
                'total' => 0,
                'imported' => 0,
                'updated' => 0,
                'failed' => 0,
                'errors' => [['row' => 1, 'field' => 'cabecera', 'message' => 'El archivo CSV está vacío o no contiene encabezados válidos.']],
            ];
        }

        $normalizedHeader = array_map(function ($col) {
            return strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '', str_replace([' ', '-'], '_', $col))));
        }, $header);

        $rowNumber = 1;
        $importedCount = 0;
        $updatedCount = 0;
        $errors = [];

        // Precargar catálogos en memoria para resolución rápida
        $categories = Category::pluck('id', 'name')->mapWithKeys(fn ($id, $name) => [strtolower($name) => $id])->all();
        $brands = Brand::pluck('id', 'name')->mapWithKeys(fn ($id, $name) => [strtolower($name) => $id])->all();
        $units = Unit::pluck('id', 'name')->mapWithKeys(fn ($id, $name) => [strtolower($name) => $id])->all();
        $unitsByAbbr = Unit::pluck('id', 'abbreviation')->mapWithKeys(fn ($id, $abbr) => [strtolower($abbr) => $id])->all();
        $suppliers = Supplier::pluck('id', 'name')->mapWithKeys(fn ($id, $name) => [strtolower($name) => $id])->all();

        // Unidad predeterminada en caso de no especificarse
        $defaultUnitId = Unit::first()?->id ?? 1;

        while (($row = fgetcsv($handle, 2048, ',')) !== false) {
            $rowNumber++;

            // Omitir filas completamente vacías
            if (empty(array_filter($row, fn ($val) => trim($val) !== ''))) {
                continue;
            }

            if (count($row) !== count($normalizedHeader)) {
                $errors[] = [
                    'row' => $rowNumber,
                    'field' => 'columnas',
                    'message' => 'El número de columnas no coincide con el encabezado.',
                ];

                continue;
            }

            $data = array_combine($normalizedHeader, array_map('trim', $row));

            $sku = $data['sku'] ?? '';
            $name = $data['nombre'] ?? ($data['name'] ?? '');
            $costPrice = $data['precio_costo'] ?? ($data['cost_price'] ?? '0');
            $sellingPrice = $data['precio_venta'] ?? ($data['selling_price'] ?? '0');
            $minStock = $data['stock_minimo'] ?? ($data['min_stock'] ?? '0');
            $maxStock = $data['stock_maximo'] ?? ($data['max_stock'] ?? null);
            $reorderPoint = $data['punto_reorden'] ?? ($data['reorder_point'] ?? '0');
            $barcode = ! empty($data['codigo_barras'] ?? ($data['barcode'] ?? null)) ? ($data['codigo_barras'] ?? $data['barcode']) : null;
            $internalCode = $data['codigo_interno'] ?? ($data['internal_code'] ?? null);
            $categoryName = strtolower($data['categoria'] ?? ($data['category'] ?? ''));
            $brandName = strtolower($data['marca'] ?? ($data['brand'] ?? ''));
            $unitName = strtolower($data['unidad'] ?? ($data['unit'] ?? ''));
            $supplierName = strtolower($data['proveedor'] ?? ($data['supplier'] ?? ''));
            $description = $data['descripcion'] ?? ($data['description'] ?? null);

            $rules = [
                'sku' => ['required', 'string', 'max:100'],
                'name' => ['required', 'string', 'max:255'],
                'cost_price' => ['required', 'numeric', 'min:0'],
                'selling_price' => ['required', 'numeric', 'min:0'],
                'min_stock' => ['required', 'numeric', 'min:0'],
                'max_stock' => ['nullable', 'numeric', 'min:0'],
                'reorder_point' => ['required', 'numeric', 'min:0'],
            ];

            $validator = Validator::make([
                'sku' => $sku,
                'name' => $name,
                'cost_price' => $costPrice,
                'selling_price' => $sellingPrice,
                'min_stock' => $minStock,
                'max_stock' => $maxStock,
                'reorder_point' => $reorderPoint,
            ], $rules);

            if ($validator->fails()) {
                foreach ($validator->errors()->getMessages() as $field => $messages) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'field' => $field,
                        'message' => implode(' ', $messages),
                    ];
                }

                continue;
            }

            // Resolver o crear categoría
            $categoryId = null;
            if ($categoryName !== '') {
                if (! isset($categories[$categoryName])) {
                    $newCategory = Category::create(['name' => ucfirst($data['categoria'] ?? $data['category']), 'is_active' => true]);
                    $categories[$categoryName] = $newCategory->id;
                }
                $categoryId = $categories[$categoryName];
            }

            // Resolver o crear marca
            $brandId = null;
            if ($brandName !== '') {
                if (! isset($brands[$brandName])) {
                    $newBrand = Brand::create(['name' => ucfirst($data['marca'] ?? $data['brand']), 'is_active' => true]);
                    $brands[$brandName] = $newBrand->id;
                }
                $brandId = $brands[$brandName];
            }

            // Resolver o crear unidad de medida
            $unitId = $defaultUnitId;
            if ($unitName !== '') {
                if (isset($units[$unitName])) {
                    $unitId = $units[$unitName];
                } elseif (isset($unitsByAbbr[$unitName])) {
                    $unitId = $unitsByAbbr[$unitName];
                } else {
                    $newUnit = Unit::create(['name' => ucfirst($data['unidad'] ?? $data['unit']), 'abbreviation' => substr($unitName, 0, 10)]);
                    $units[$unitName] = $newUnit->id;
                    $unitId = $newUnit->id;
                }
            }

            // Resolver o crear proveedor
            $supplierId = null;
            if ($supplierName !== '') {
                if (! isset($suppliers[$supplierName])) {
                    $newSupplier = Supplier::create(['name' => ucfirst($data['proveedor'] ?? $data['supplier']), 'is_active' => true]);
                    $suppliers[$supplierName] = $newSupplier->id;
                }
                $supplierId = $suppliers[$supplierName];
            }

            // Verificar si el producto ya existe por su SKU
            $existingProduct = Product::where('sku', $sku)->first();

            if ($existingProduct && ! $updateExisting) {
                $errors[] = [
                    'row' => $rowNumber,
                    'field' => 'sku',
                    'message' => "El SKU '{$sku}' ya existe y no se activó la opción de actualizar existentes.",
                ];

                continue;
            }

            // Verificar unicidad del código de barras si se proporciona
            if ($barcode) {
                $barcodeConflict = Product::where('barcode', $barcode)
                    ->when($existingProduct, fn ($q) => $q->where('id', '!=', $existingProduct->id))
                    ->exists();

                if ($barcodeConflict) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'field' => 'barcode',
                        'message' => "El código de barras '{$barcode}' ya está registrado en otro producto.",
                    ];

                    continue;
                }
            }

            DB::transaction(function () use (
                $existingProduct,
                $sku,
                $name,
                $description,
                $internalCode,
                $barcode,
                $categoryId,
                $brandId,
                $unitId,
                $supplierId,
                $costPrice,
                $sellingPrice,
                $minStock,
                $maxStock,
                $reorderPoint,
                &$importedCount,
                &$updatedCount
            ) {
                $attributes = [
                    'name' => $name,
                    'description' => $description,
                    'internal_code' => $internalCode,
                    'barcode' => $barcode,
                    'category_id' => $categoryId,
                    'brand_id' => $brandId,
                    'unit_id' => $unitId,
                    'supplier_id' => $supplierId,
                    'cost_price' => (float) $costPrice,
                    'selling_price' => (float) $sellingPrice,
                    'min_stock' => (float) $minStock,
                    'max_stock' => $maxStock !== null && $maxStock !== '' ? (float) $maxStock : null,
                    'reorder_point' => (float) $reorderPoint,
                    'is_active' => true,
                ];

                if ($existingProduct) {
                    $existingProduct->update($attributes);
                    $updatedCount++;
                } else {
                    $attributes['sku'] = $sku;
                    Product::create($attributes);
                    $importedCount++;
                }
            });
        }

        fclose($handle);

        $totalProcessed = $importedCount + $updatedCount + count($errors);

        ActivityLogger::log(
            'csv_import',
            "Importación CSV completada: {$importedCount} creados, {$updatedCount} actualizados, ".count($errors).' errores.',
            null,
            [
                'imported' => $importedCount,
                'updated' => $updatedCount,
                'errors_count' => count($errors),
            ]
        );

        return [
            'total' => $totalProcessed,
            'imported' => $importedCount,
            'updated' => $updatedCount,
            'failed' => count($errors),
            'errors' => $errors,
        ];
    }
}
