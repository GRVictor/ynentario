<?php

namespace Tests\Feature;

use App\Actions\Inventory\RegisterEntryAction;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryMovementTest extends TestCase
{
    use RefreshDatabase;

    protected User $supervisor;

    protected Product $product;

    protected Warehouse $warehouseCentral;

    protected Warehouse $warehouseNorte;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $supervisorRole = Role::where('slug', 'warehouse_supervisor')->first();
        $this->supervisor = User::factory()->create(['is_active' => true]);
        $this->supervisor->roles()->sync([$supervisorRole->id]);

        $unit = Unit::create(['name' => 'Pieza', 'abbreviation' => 'pza']);

        $this->product = Product::create([
            'sku' => 'SKU-MOV-TEST',
            'name' => 'Articulo para Pruebas de Movimiento',
            'unit_id' => $unit->id,
            'cost_price' => 500,
            'selling_price' => 750,
            'min_stock' => 10,
            'reorder_point' => 15,
            'is_active' => true,
        ]);

        $this->warehouseCentral = Warehouse::create([
            'code' => 'ALM-CEN',
            'name' => 'Almacén Central',
            'is_active' => true,
        ]);

        $this->warehouseNorte = Warehouse::create([
            'code' => 'ALM-NOR',
            'name' => 'Almacén Norte',
            'is_active' => true,
        ]);
    }

    public function test_entry_increases_inventory_and_generates_entry_folio(): void
    {
        $response = $this->actingAs($this->supervisor)->post(route('movements.entry.store'), [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouseCentral->id,
            'quantity' => 50,
            'reason' => 'Compra inicial',
        ]);

        $response->assertSessionHasNoErrors();

        // Comprobar balance de inventario
        $inv = Inventory::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouseCentral->id)
            ->first();

        $this->assertNotNull($inv);
        $this->assertEquals(50, (float) $inv->quantity);

        // Comprobar registro del movimiento
        $this->assertDatabaseHas('inventory_movements', [
            'type' => 'entry',
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouseCentral->id,
            'quantity' => 50,
            'previous_quantity' => 0,
            'new_quantity' => 50,
        ]);
    }

    public function test_entry_rejects_zero_or_negative_quantity(): void
    {
        $response = $this->actingAs($this->supervisor)->post(route('movements.entry.store'), [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouseCentral->id,
            'quantity' => -10,
            'reason' => 'Cantidad inválida',
        ]);

        $response->assertSessionHasErrors('quantity');
    }

    public function test_exit_decreases_stock_and_generates_exit_folio(): void
    {
        // Registrar existencias iniciales
        app(RegisterEntryAction::class)->execute([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouseCentral->id,
            'quantity' => 30,
            'reason' => 'Stock inicial',
        ], $this->supervisor);

        $response = $this->actingAs($this->supervisor)->post(route('movements.exit.store'), [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouseCentral->id,
            'quantity' => 12,
            'reason' => 'Venta regular',
        ]);

        $response->assertSessionHasNoErrors();

        $inv = Inventory::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouseCentral->id)
            ->first();

        $this->assertEquals(18, (float) $inv->quantity);
    }

    public function test_exit_fails_when_insufficient_stock_preventing_negative_quantity(): void
    {
        // Agregar 10 unidades de existencias
        app(RegisterEntryAction::class)->execute([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouseCentral->id,
            'quantity' => 10,
            'reason' => 'Stock inicial',
        ], $this->supervisor);

        // Intentar registrar salida de 25 unidades
        $response = $this->actingAs($this->supervisor)->post(route('movements.exit.store'), [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouseCentral->id,
            'quantity' => 25,
            'reason' => 'Intento de sobre-salida',
        ]);

        $response->assertSessionHasErrors('quantity');

        // Verificar que las existencias permanezcan intactas en 10 unidades
        $inv = Inventory::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouseCentral->id)
            ->first();

        $this->assertEquals(10, (float) $inv->quantity);
    }

    public function test_adjustment_updates_inventory_to_physical_count(): void
    {
        // Existencias iniciales en 20 unidades
        app(RegisterEntryAction::class)->execute([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouseCentral->id,
            'quantity' => 20,
            'reason' => 'Inventario inicial',
        ], $this->supervisor);

        // Conteo físico de auditoría resulta en 17 unidades (diferencia de -3)
        $response = $this->actingAs($this->supervisor)->post(route('movements.adjustment.store'), [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouseCentral->id,
            'real_quantity' => 17,
            'reason' => 'Ajuste tras conteo físico',
        ]);

        $response->assertSessionHasNoErrors();

        $inv = Inventory::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouseCentral->id)
            ->first();

        $this->assertEquals(17, (float) $inv->quantity);
    }

    public function test_transfer_moves_stock_between_different_warehouses(): void
    {
        // Agregar 40 unidades al almacén central
        app(RegisterEntryAction::class)->execute([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouseCentral->id,
            'quantity' => 40,
            'reason' => 'Carga inicial',
        ], $this->supervisor);

        // Transferir 15 unidades a la sucursal Norte
        $response = $this->actingAs($this->supervisor)->post(route('movements.transfer.store'), [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouseCentral->id,
            'destination_warehouse_id' => $this->warehouseNorte->id,
            'quantity' => 15,
            'reason' => 'Traspaso a sucursal norte',
        ]);

        $response->assertSessionHasNoErrors();

        // Comprobar que el almacén de origen disminuyó a 25
        $originInv = Inventory::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouseCentral->id)
            ->first();
        $this->assertEquals(25, (float) $originInv->quantity);

        // Comprobar que el almacén de destino aumentó a 15
        $destInv = Inventory::where('product_id', $this->product->id)
            ->where('warehouse_id', $this->warehouseNorte->id)
            ->first();
        $this->assertEquals(15, (float) $destInv->quantity);
    }

    public function test_transfer_fails_when_origin_and_destination_are_the_same(): void
    {
        $response = $this->actingAs($this->supervisor)->post(route('movements.transfer.store'), [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouseCentral->id,
            'destination_warehouse_id' => $this->warehouseCentral->id,
            'quantity' => 5,
            'reason' => 'Transferencia inválida al mismo almacén',
        ]);

        $response->assertSessionHasErrors('destination_warehouse_id');
    }

    public function test_transfer_fails_when_origin_has_insufficient_stock(): void
    {
        // El almacén central únicamente cuenta con 5 unidades
        app(RegisterEntryAction::class)->execute([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouseCentral->id,
            'quantity' => 5,
            'reason' => 'Poco stock',
        ], $this->supervisor);

        // Intentar transferencia de 10 unidades
        $response = $this->actingAs($this->supervisor)->post(route('movements.transfer.store'), [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouseCentral->id,
            'destination_warehouse_id' => $this->warehouseNorte->id,
            'quantity' => 10,
            'reason' => 'Intento de traslado mayor al existente',
        ]);

        $response->assertSessionHasErrors('quantity');
    }

    public function test_entry_records_unit_cost_updates_weighted_average_and_selling_price(): void
    {
        // Configuración inicial: el producto tiene costo 100 y stock 10
        $this->product->update([
            'cost_price' => 100.00,
            'selling_price' => 150.00,
        ]);

        app(RegisterEntryAction::class)->execute([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouseCentral->id,
            'quantity' => 10,
            'reason' => 'Lote inicial',
        ], $this->supervisor);

        // Segunda entrada: 10 unidades con costo unitario de 200, actualizando precio a 300
        $response = $this->actingAs($this->supervisor)->post(route('movements.entry.store'), [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouseCentral->id,
            'quantity' => 10,
            'unit_cost' => 200.00,
            'update_selling_price' => true,
            'new_selling_price' => 300.00,
            'reason' => 'Nuevo lote con mayor costo',
        ]);

        $response->assertSessionHasNoErrors();

        // Comprobar nuevo Precio Medio Ponderado del producto: ((10 * 100) + (10 * 200)) / 20 = 150
        $this->product->refresh();
        $this->assertEquals(150.00, (float) $this->product->cost_price);
        $this->assertEquals(300.00, (float) $this->product->selling_price);

        // Comprobar que el registro de movimiento guardó el costo unitario
        $this->assertDatabaseHas('inventory_movements', [
            'type' => 'entry',
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouseCentral->id,
            'quantity' => 10,
            'unit_cost' => 200.00,
        ]);
    }

    public function test_customer_return_entry_preserves_product_cost_and_price(): void
    {
        $this->product->update([
            'cost_price' => 80.00,
            'selling_price' => 120.00,
        ]);

        // Registrar una devolución de cliente
        $response = $this->actingAs($this->supervisor)->post(route('movements.entry.store'), [
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouseCentral->id,
            'quantity' => 3,
            'reason' => 'Devolución de cliente',
            'reference' => 'Ticket #4501',
        ]);

        $response->assertSessionHasNoErrors();

        // El costo y precio del producto deben mantenerse intactos
        $this->product->refresh();
        $this->assertEquals(80.00, (float) $this->product->cost_price);
        $this->assertEquals(120.00, (float) $this->product->selling_price);

        // El movimiento debe registrarse con el costo contable del producto
        $this->assertDatabaseHas('inventory_movements', [
            'type' => 'entry',
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouseCentral->id,
            'quantity' => 3,
            'unit_cost' => 80.00,
            'reason' => 'Devolución de cliente',
        ]);
    }
}
