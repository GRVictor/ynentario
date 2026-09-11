<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_viewer_cannot_create_products(): void
    {
        $viewerRole = Role::where('slug', 'viewer')->first();
        $viewer = User::factory()->create(['is_active' => true]);
        $viewer->roles()->sync([$viewerRole->id]);

        $unit = Unit::create(['name' => 'Pieza', 'abbreviation' => 'pza']);

        $response = $this->actingAs($viewer)->post(route('products.store'), [
            'sku' => 'TEST-001',
            'name' => 'Producto de Prueba',
            'unit_id' => $unit->id,
            'cost_price' => 100,
            'selling_price' => 150,
            'min_stock' => 5,
            'reorder_point' => 10,
        ]);

        $response->assertForbidden();
    }

    public function test_admin_has_full_permission_to_create_product(): void
    {
        $adminRole = Role::where('slug', 'admin')->first();
        $admin = User::factory()->create(['is_active' => true]);
        $admin->roles()->sync([$adminRole->id]);

        $unit = Unit::create(['name' => 'Pieza', 'abbreviation' => 'pza']);

        $response = $this->actingAs($admin)->post(route('products.store'), [
            'sku' => 'TEST-ADMIN-001',
            'name' => 'Producto Autorizado',
            'unit_id' => $unit->id,
            'cost_price' => 100,
            'selling_price' => 150,
            'min_stock' => 5,
            'reorder_point' => 10,
            'is_active' => true,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('products', ['sku' => 'TEST-ADMIN-001']);
    }

    public function test_operator_cannot_access_user_management(): void
    {
        $operatorRole = Role::where('slug', 'operator')->first();
        $operator = User::factory()->create(['is_active' => true]);
        $operator->roles()->sync([$operatorRole->id]);

        $response = $this->actingAs($operator)->get(route('users.index'));

        $response->assertForbidden();
    }
}
