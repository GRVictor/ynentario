<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProductCsvImportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $adminRole = Role::where('slug', 'admin')->first();
        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->roles()->sync([$adminRole->id]);

        Unit::create(['name' => 'Pieza', 'abbreviation' => 'pza']);
    }

    public function test_can_import_valid_products_from_csv(): void
    {
        $csvContent = implode("\n", [
            'sku,nombre,categoria,marca,unidad,precio_costo,precio_venta,stock_minimo,stock_maximo,punto_reorden',
            'IMP-001,Teclado Mecánico RGB,Accesorios,Logitech,Pieza,800.00,1200.00,5,20,10',
            'IMP-002,Mouse Pad Gamer XL,Accesorios,Razer,Pieza,150.00,280.00,10,50,15',
        ]);

        $file = UploadedFile::fake()->createWithContent('productos.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post(route('products.import.process'), [
            'file' => $file,
            'update_existing' => true,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('products', ['sku' => 'IMP-001', 'name' => 'Teclado Mecánico RGB']);
        $this->assertDatabaseHas('products', ['sku' => 'IMP-002', 'name' => 'Mouse Pad Gamer XL']);
    }

    public function test_csv_import_reports_errors_for_invalid_rows(): void
    {
        $csvContent = implode("\n", [
            'sku,nombre,categoria,marca,unidad,precio_costo,precio_venta,stock_minimo,stock_maximo,punto_reorden',
            'IMP-VALID,Producto Valido,Oficina,HP,Pieza,100,150,5,20,10',
            ',Producto Sin SKU,Oficina,HP,Pieza,100,150,5,20,10', // Fila con SKU faltante
            'IMP-BAD-NUM,Producto Mal Precio,Oficina,HP,Pieza,INVALID_PRICE,150,5,20,10', // Costo numérico inválido
        ]);

        $file = UploadedFile::fake()->createWithContent('productos_con_error.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post(route('products.import.process'), [
            'file' => $file,
            'update_existing' => false,
        ]);

        $response->assertSessionHasNoErrors();

        // El producto válido fue importado
        $this->assertDatabaseHas('products', ['sku' => 'IMP-VALID']);

        // Verificar que el resumen en sesión contiene 1 importado y 2 fallidos
        $importResult = session('importResult');
        $this->assertNotNull($importResult);
        $this->assertEquals(1, $importResult['imported']);
        $this->assertEquals(2, $importResult['failed']);
        $this->assertCount(2, $importResult['errors']);
    }
}
