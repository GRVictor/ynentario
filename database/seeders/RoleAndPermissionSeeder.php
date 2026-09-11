<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Products
            ['slug' => 'products.view', 'name' => 'Ver productos', 'group' => 'Productos', 'description' => 'Consultar el catálogo y detalle de productos'],
            ['slug' => 'products.create', 'name' => 'Crear productos', 'group' => 'Productos', 'description' => 'Registrar nuevos productos en el catálogo'],
            ['slug' => 'products.update', 'name' => 'Editar productos', 'group' => 'Productos', 'description' => 'Modificar datos de productos existentes'],
            ['slug' => 'products.delete', 'name' => 'Eliminar o desactivar productos', 'group' => 'Productos', 'description' => 'Dar de baja productos del catálogo'],

            // Warehouses
            ['slug' => 'warehouses.view', 'name' => 'Ver almacenes', 'group' => 'Almacenes', 'description' => 'Consultar almacenes y sus existencias'],
            ['slug' => 'warehouses.create', 'name' => 'Crear almacenes', 'group' => 'Almacenes', 'description' => 'Registrar nuevas ubicaciones o bodegas'],
            ['slug' => 'warehouses.update', 'name' => 'Editar almacenes', 'group' => 'Almacenes', 'description' => 'Modificar datos de almacenes'],
            ['slug' => 'warehouses.delete', 'name' => 'Eliminar o desactivar almacenes', 'group' => 'Almacenes', 'description' => 'Dar de baja almacenes'],

            // Inventory
            ['slug' => 'inventory.view', 'name' => 'Ver existencias e historial', 'group' => 'Inventario', 'description' => 'Consultar inventario general y Kardex'],
            ['slug' => 'inventory.entry', 'name' => 'Registrar entradas', 'group' => 'Inventario', 'description' => 'Dar entrada a existencias de producto'],
            ['slug' => 'inventory.exit', 'name' => 'Registrar salidas', 'group' => 'Inventario', 'description' => 'Dar salida o baja a existencias'],
            ['slug' => 'inventory.transfer', 'name' => 'Registrar transferencias', 'group' => 'Inventario', 'description' => 'Mover existencias entre almacenes'],
            ['slug' => 'inventory.adjustment', 'name' => 'Realizar ajustes de inventario', 'group' => 'Inventario', 'description' => 'Ajustar diferencias por auditoría'],

            // Reports
            ['slug' => 'reports.view', 'name' => 'Consultar reportes', 'group' => 'Reportes', 'description' => 'Acceso a los reportes de inventario y movimientos'],
            ['slug' => 'reports.export', 'name' => 'Exportar datos', 'group' => 'Reportes', 'description' => 'Descargar reportes y tablas en formato CSV'],

            // Users & Roles
            ['slug' => 'users.view', 'name' => 'Ver usuarios', 'group' => 'Usuarios', 'description' => 'Consultar lista de usuarios del sistema'],
            ['slug' => 'users.create', 'name' => 'Crear usuarios', 'group' => 'Usuarios', 'description' => 'Registrar nuevos usuarios'],
            ['slug' => 'users.update', 'name' => 'Editar usuarios', 'group' => 'Usuarios', 'description' => 'Modificar usuarios y sus accesos'],
            ['slug' => 'users.delete', 'name' => 'Eliminar usuarios', 'group' => 'Usuarios', 'description' => 'Eliminar cuentas de usuario'],
            ['slug' => 'roles.manage', 'name' => 'Gestionar roles y permisos', 'group' => 'Usuarios', 'description' => 'Configuración de roles de acceso'],

            // Imports
            ['slug' => 'imports.execute', 'name' => 'Importar productos CSV', 'group' => 'Importaciones', 'description' => 'Cargar productos masivamente mediante archivo CSV'],
        ];

        foreach ($permissions as $perm) {
            Permission::updateOrCreate(['slug' => $perm['slug']], $perm);
        }

        // Roles
        $adminRole = Role::updateOrCreate(['slug' => 'admin'], [
            'name' => 'Administrador',
            'description' => 'Acceso total y sin restricciones a todos los módulos y configuraciones del sistema.',
        ]);

        $supervisorRole = Role::updateOrCreate(['slug' => 'warehouse_supervisor'], [
            'name' => 'Supervisor de almacén',
            'description' => 'Gestiona productos, existencias, entradas, salidas, transferencias, ajustes y reportes.',
        ]);

        $operatorRole = Role::updateOrCreate(['slug' => 'operator'], [
            'name' => 'Operador',
            'description' => 'Consulta existencias y registra entradas y salidas operativas.',
        ]);

        $viewerRole = Role::updateOrCreate(['slug' => 'viewer'], [
            'name' => 'Consulta',
            'description' => 'Acceso de solo lectura a productos, inventario, almacenes y reportes.',
        ]);

        // Assign permissions to supervisor
        $supervisorPermissions = Permission::whereIn('slug', [
            'products.view',
            'products.create',
            'products.update',
            'warehouses.view',
            'inventory.view',
            'inventory.entry',
            'inventory.exit',
            'inventory.transfer',
            'inventory.adjustment',
            'reports.view',
            'reports.export',
            'imports.execute',
        ])->pluck('id');
        $supervisorRole->permissions()->sync($supervisorPermissions);

        // Assign permissions to operator
        $operatorPermissions = Permission::whereIn('slug', [
            'products.view',
            'warehouses.view',
            'inventory.view',
            'inventory.entry',
            'inventory.exit',
        ])->pluck('id');
        $operatorRole->permissions()->sync($operatorPermissions);

        // Assign permissions to viewer
        $viewerPermissions = Permission::whereIn('slug', [
            'products.view',
            'warehouses.view',
            'inventory.view',
            'reports.view',
        ])->pluck('id');
        $viewerRole->permissions()->sync($viewerPermissions);
    }
}
