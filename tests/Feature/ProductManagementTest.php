<?php

namespace Tests\Feature;

use App\Actions\Inventory\RegisterEntryAction;
use App\Models\Product;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $adminRole = Role::where('slug', 'admin')->first();
        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->roles()->sync([$adminRole->id]);

        $this->unit = Unit::create(['name' => 'Pieza', 'abbreviation' => 'pza']);
    }

    public function test_can_create_product_with_valid_data(): void
    {
        $response = $this->actingAs($this->admin)->post(route('products.store'), [
            'sku' => 'PROD-NEW-01',
            'name' => 'Monitor LED 27 Pulgadas',
            'unit_id' => $this->unit->id,
            'cost_price' => 3500.00,
            'selling_price' => 4999.00,
            'min_stock' => 5,
            'reorder_point' => 10,
            'is_active' => true,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('products', [
            'sku' => 'PROD-NEW-01',
            'name' => 'Monitor LED 27 Pulgadas',
        ]);
    }

    public function test_rejects_duplicate_sku(): void
    {
        Product::create([
            'sku' => 'SKU-UNIQUE-01',
            'name' => 'Producto Original',
            'unit_id' => $this->unit->id,
            'cost_price' => 100,
            'selling_price' => 200,
            'min_stock' => 1,
            'reorder_point' => 2,
        ]);

        $response = $this->actingAs($this->admin)->post(route('products.store'), [
            'sku' => 'SKU-UNIQUE-01',
            'name' => 'Producto Duplicado',
            'unit_id' => $this->unit->id,
            'cost_price' => 100,
            'selling_price' => 200,
            'min_stock' => 1,
            'reorder_point' => 2,
        ]);

        $response->assertSessionHasErrors('sku');
    }

    public function test_product_with_movements_is_deactivated_instead_of_deleted(): void
    {
        $product = Product::create([
            'sku' => 'PROD-WITH-MOV',
            'name' => 'Producto con Movimiento',
            'unit_id' => $this->unit->id,
            'cost_price' => 50,
            'selling_price' => 100,
            'min_stock' => 5,
            'reorder_point' => 10,
            'is_active' => true,
        ]);

        $warehouse = Warehouse::create([
            'code' => 'ALM-TEST',
            'name' => 'Almacén de Pruebas',
            'is_active' => true,
        ]);

        // Registrar movimiento previo
        app(RegisterEntryAction::class)->execute([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 10,
            'reason' => 'Entrada de prueba',
        ], $this->admin);

        // Intentar eliminar producto con historial
        $response = $this->actingAs($this->admin)->delete(route('products.destroy', $product->id));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'is_active' => false,
        ]);
    }
}
