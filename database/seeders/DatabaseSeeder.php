<?php

namespace Database\Seeders;

use App\Actions\Inventory\RegisterAdjustmentAction;
use App\Actions\Inventory\RegisterEntryAction;
use App\Actions\Inventory\RegisterExitAction;
use App\Actions\Inventory\RegisterTransferAction;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleAndPermissionSeeder::class);

        $adminRole = Role::where('slug', 'admin')->first();
        $supervisorRole = Role::where('slug', 'warehouse_supervisor')->first();
        $operatorRole = Role::where('slug', 'operator')->first();
        $viewerRole = Role::where('slug', 'viewer')->first();

        // 1. Users
        $admin = User::firstOrCreate(['email' => 'admin@ynentario.local'], [
            'name' => 'Administrador General',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $admin->roles()->sync([$adminRole->id]);

        $supervisor = User::firstOrCreate(['email' => 'supervisor@ynentario.local'], [
            'name' => 'Carlos Supervisor',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $supervisor->roles()->sync([$supervisorRole->id]);

        $operator = User::firstOrCreate(['email' => 'operador@ynentario.local'], [
            'name' => 'María Operadora',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $operator->roles()->sync([$operatorRole->id]);

        $viewer = User::firstOrCreate(['email' => 'consulta@ynentario.local'], [
            'name' => 'Laura Consulta',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $viewer->roles()->sync([$viewerRole->id]);

        // 2. Catalogs: Units
        $unitPza = Unit::firstOrCreate(['name' => 'Pieza', 'abbreviation' => 'pza']);
        $unitCja = Unit::firstOrCreate(['name' => 'Caja', 'abbreviation' => 'cja']);
        $unitPaq = Unit::firstOrCreate(['name' => 'Paquete', 'abbreviation' => 'paq']);
        $unitM = Unit::firstOrCreate(['name' => 'Metro', 'abbreviation' => 'm']);
        $unitKg = Unit::firstOrCreate(['name' => 'Kilogramo', 'abbreviation' => 'kg']);

        // Catalogs: Categories
        $catComp = Category::firstOrCreate(['name' => 'Cómputo y Electrónica'], ['description' => 'Equipos, periféricos y accesorios informáticos']);
        $catOfic = Category::firstOrCreate(['name' => 'Papelería y Oficina'], ['description' => 'Artículos para oficinas y suministros administrativos']);
        $catHerr = Category::firstOrCreate(['name' => 'Herramientas y Mantenimiento'], ['description' => 'Herramientas manuales y consumibles de taller']);
        $catRed = Category::firstOrCreate(['name' => 'Redes y Conectividad'], ['description' => 'Cables, routers, switches y conectores']);

        // Catalogs: Brands
        $brandLogi = Brand::firstOrCreate(['name' => 'Logitech']);
        $brandDell = Brand::firstOrCreate(['name' => 'Dell']);
        $brandHP = Brand::firstOrCreate(['name' => 'HP']);
        $brandTrup = Brand::firstOrCreate(['name' => 'Truper']);
        $brandKings = Brand::firstOrCreate(['name' => 'Kingston']);
        $brand3M = Brand::firstOrCreate(['name' => '3M']);

        // Catalogs: Suppliers
        $suppTech = Supplier::firstOrCreate(['name' => 'Mayorista Tech México'], [
            'code' => 'PROV-001',
            'contact_name' => 'Ing. Roberto Gómez',
            'email' => 'ventas@maytech.mx',
            'phone' => '55-1234-5678',
            'address' => 'Av. Insurgentes Sur 1400, CDMX',
        ]);
        $suppPapel = Supplier::firstOrCreate(['name' => 'Distribuidora Papelera Nacional'], [
            'code' => 'PROV-002',
            'contact_name' => 'Lic. Ana Morales',
            'email' => 'pedidos@papeleranacional.com',
            'phone' => '33-9876-5432',
            'address' => 'Calzada Federalismo 450, Guadalajara',
        ]);
        $suppInd = Supplier::firstOrCreate(['name' => 'Soluciones Industriales del Norte'], [
            'code' => 'PROV-003',
            'contact_name' => 'Ing. Fernando Treviño',
            'email' => 'contacto@indnorte.mx',
            'phone' => '81-4567-8901',
            'address' => 'Parque Industrial Mitras, Monterrey',
        ]);

        // 3. Warehouses
        $whCentral = Warehouse::firstOrCreate(['code' => 'ALM-CDMX'], [
            'name' => 'Almacén Central CDMX',
            'description' => 'Centro de distribución principal Valle de México',
            'address' => 'Eje Central Lázaro Cárdenas 890, Benito Juárez, CDMX',
            'manager_name' => 'Carlos Supervisor',
            'phone' => '55-5555-1001',
            'email' => 'almacen.central@ynentario.local',
        ]);

        $whGdl = Warehouse::firstOrCreate(['code' => 'ALM-GDL'], [
            'name' => 'Almacén Guadalajara',
            'description' => 'Bodega regional Occidente',
            'address' => 'Av. Vallarta 2300, Zapopan, Jalisco',
            'manager_name' => 'María Operadora',
            'phone' => '33-3333-2002',
            'email' => 'almacen.gdl@ynentario.local',
        ]);

        $whMty = Warehouse::firstOrCreate(['code' => 'ALM-MTY'], [
            'name' => 'Almacén Monterrey',
            'description' => 'Bodega regional Noreste',
            'address' => 'Av. Gonzalitos 1200, Monterrey, Nuevo León',
            'manager_name' => 'Ing. Héctor Garza',
            'phone' => '81-8181-3003',
            'email' => 'almacen.mty@ynentario.local',
        ]);

        // 4. Products
        $productsData = [
            [
                'sku' => 'LAP-DELL-5520',
                'name' => 'Laptop Dell Latitude 5520 15.6"',
                'internal_code' => 'EQU-001',
                'barcode' => '7501000100011',
                'category_id' => $catComp->id,
                'brand_id' => $brandDell->id,
                'unit_id' => $unitPza->id,
                'supplier_id' => $suppTech->id,
                'cost_price' => 14500.00,
                'selling_price' => 18999.00,
                'min_stock' => 5,
                'max_stock' => 30,
                'reorder_point' => 8,
                'default_location' => 'Pasillo A - Estante 1',
                'description' => 'Laptop empresarial con Intel Core i7, 16GB RAM y 512GB SSD.',
            ],
            [
                'sku' => 'MOU-LOG-MX3',
                'name' => 'Mouse Inalámbrico Logitech MX Master 3S',
                'internal_code' => 'ACC-002',
                'barcode' => '7501000100028',
                'category_id' => $catComp->id,
                'brand_id' => $brandLogi->id,
                'unit_id' => $unitPza->id,
                'supplier_id' => $suppTech->id,
                'cost_price' => 1250.00,
                'selling_price' => 1850.00,
                'min_stock' => 10,
                'max_stock' => 60,
                'reorder_point' => 15,
                'default_location' => 'Pasillo A - Estante 3',
                'description' => 'Mouse ergonómico para productividad con sensor Darkfield de 8000 DPI.',
            ],
            [
                'sku' => 'TEC-LOG-K380',
                'name' => 'Teclado Bluetooth Logitech K380 Multidispositivo',
                'internal_code' => 'ACC-003',
                'barcode' => '7501000100035',
                'category_id' => $catComp->id,
                'brand_id' => $brandLogi->id,
                'unit_id' => $unitPza->id,
                'supplier_id' => $suppTech->id,
                'cost_price' => 550.00,
                'selling_price' => 899.00,
                'min_stock' => 12,
                'max_stock' => 80,
                'reorder_point' => 20,
                'default_location' => 'Pasillo A - Estante 4',
                'description' => 'Teclado compacto multilenguaje compatible con Windows, Mac, iOS y Android.',
            ],
            [
                'sku' => 'SSD-KIN-1TB',
                'name' => 'Unidad SSD Kingston NV2 1TB PCIe NVMe',
                'internal_code' => 'REF-004',
                'barcode' => '7501000100042',
                'category_id' => $catComp->id,
                'brand_id' => $brandKings->id,
                'unit_id' => $unitPza->id,
                'supplier_id' => $suppTech->id,
                'cost_price' => 980.00,
                'selling_price' => 1420.00,
                'min_stock' => 15,
                'max_stock' => 100,
                'reorder_point' => 25,
                'default_location' => 'Gaveta B - Cajón 2',
                'description' => 'Almacenamiento de alta velocidad para actualización de portátiles y PCs.',
            ],
            [
                'sku' => 'PAP-BOND-CJA',
                'name' => 'Caja Papel Bond Carta 75g (5000 hojas)',
                'internal_code' => 'PAP-005',
                'barcode' => '7501000100059',
                'category_id' => $catOfic->id,
                'brand_id' => $brandHP->id,
                'unit_id' => $unitCja->id,
                'supplier_id' => $suppPapel->id,
                'cost_price' => 620.00,
                'selling_price' => 890.00,
                'min_stock' => 20,
                'max_stock' => 200,
                'reorder_point' => 35,
                'default_location' => 'Zona Tarimas 1',
                'description' => 'Papel bond blanco para impresoras láser e inyección de tinta.',
            ],
            [
                'sku' => 'NOT-POST-PAQ',
                'name' => 'Paquete Notas Adhesivas Post-it 76x76mm (12 blocs)',
                'internal_code' => 'PAP-006',
                'barcode' => '7501000100066',
                'category_id' => $catOfic->id,
                'brand_id' => $brand3M->id,
                'unit_id' => $unitPaq->id,
                'supplier_id' => $suppPapel->id,
                'cost_price' => 180.00,
                'selling_price' => 280.00,
                'min_stock' => 15,
                'max_stock' => 120,
                'reorder_point' => 25,
                'default_location' => 'Pasillo B - Estante 2',
                'description' => 'Notas adhesivas reposicionables colores surtidos.',
            ],
            [
                'sku' => 'HER-ROTO-TRU',
                'name' => 'Rotomartillo Inalámbrico Truper Max 20V',
                'internal_code' => 'HER-007',
                'barcode' => '7501000100073',
                'category_id' => $catHerr->id,
                'brand_id' => $brandTrup->id,
                'unit_id' => $unitPza->id,
                'supplier_id' => $suppInd->id,
                'cost_price' => 1850.00,
                'selling_price' => 2690.00,
                'min_stock' => 4,
                'max_stock' => 20,
                'reorder_point' => 6,
                'default_location' => 'Taller - Jaula 1',
                'description' => 'Herramienta industrial con 2 baterías de litio y cargador rápido.',
            ],
            [
                'sku' => 'HER-JUE-DESM',
                'name' => 'Juego de Destornilladores de Precisión 32 pzas',
                'internal_code' => 'HER-008',
                'barcode' => '7501000100080',
                'category_id' => $catHerr->id,
                'brand_id' => $brandTrup->id,
                'unit_id' => $unitPza->id,
                'supplier_id' => $suppInd->id,
                'cost_price' => 140.00,
                'selling_price' => 250.00,
                'min_stock' => 8,
                'max_stock' => 40,
                'reorder_point' => 12,
                'default_location' => 'Taller - Estante 2',
                'description' => 'Puntas magnéticas intercambiables para electrónica y reparación.',
            ],
            [
                'sku' => 'CAB-UTP-CAT6',
                'name' => 'Bobina Cable UTP Cat6 305m 100% Cobre',
                'internal_code' => 'RED-009',
                'barcode' => '7501000100097',
                'category_id' => $catRed->id,
                'brand_id' => $brand3M->id,
                'unit_id' => $unitCja->id,
                'supplier_id' => $suppInd->id,
                'cost_price' => 2100.00,
                'selling_price' => 2950.00,
                'min_stock' => 3,
                'max_stock' => 25,
                'reorder_point' => 5,
                'default_location' => 'Zona Tarimas 2',
                'description' => 'Cable para instalaciones de red gigabit de alta durabilidad.',
            ],
            [
                'sku' => 'CIN-DUCK-3M',
                'name' => 'Cinta Adhesiva Reforzada para Embalaje 3M 50m',
                'internal_code' => 'EMB-010',
                'barcode' => '7501000100103',
                'category_id' => $catOfic->id,
                'brand_id' => $brand3M->id,
                'unit_id' => $unitPza->id,
                'supplier_id' => $suppPapel->id,
                'cost_price' => 45.00,
                'selling_price' => 85.00,
                'min_stock' => 25,
                'max_stock' => 200,
                'reorder_point' => 40,
                'default_location' => 'Pasillo B - Estante 4',
                'description' => 'Cinta de polipropileno para cerrado seguro de cajas y envíos.',
            ],
            // Low stock and out of stock test products
            [
                'sku' => 'MON-DELL-24',
                'name' => 'Monitor Dell 24" Full HD IPS 75Hz',
                'internal_code' => 'EQU-011',
                'barcode' => '7501000100110',
                'category_id' => $catComp->id,
                'brand_id' => $brandDell->id,
                'unit_id' => $unitPza->id,
                'supplier_id' => $suppTech->id,
                'cost_price' => 2300.00,
                'selling_price' => 3200.00,
                'min_stock' => 8,
                'max_stock' => 50,
                'reorder_point' => 12,
                'default_location' => 'Pasillo A - Estante 2',
                'description' => 'Pantalla antirreflejante con entradas HDMI y DisplayPort.',
            ],
            [
                'sku' => 'TON-HP-85A',
                'name' => 'Cartucho de Tóner Original HP LaserJet 85A Negro',
                'internal_code' => 'CON-012',
                'barcode' => '7501000100127',
                'category_id' => $catOfic->id,
                'brand_id' => $brandHP->id,
                'unit_id' => $unitPza->id,
                'supplier_id' => $suppPapel->id,
                'cost_price' => 1350.00,
                'selling_price' => 1890.00,
                'min_stock' => 10,
                'max_stock' => 50,
                'reorder_point' => 15,
                'default_location' => 'Pasillo B - Estante 1',
                'description' => 'Rendimiento estándar aproximado de 1,600 páginas.',
            ],
        ];

        $createdProducts = [];
        foreach ($productsData as $data) {
            $createdProducts[$data['sku']] = Product::firstOrCreate(['sku' => $data['sku']], $data);
        }

        // 5. Initial movements using Actions to ensure 100% transactional consistency
        $entryAction = app(RegisterEntryAction::class);
        $exitAction = app(RegisterExitAction::class);
        $transferAction = app(RegisterTransferAction::class);
        $adjustmentAction = app(RegisterAdjustmentAction::class);

        // Initial entries in Almacén Central
        $initialStock = [
            'LAP-DELL-5520' => 18,
            'MOU-LOG-MX3' => 45,
            'TEC-LOG-K380' => 35,
            'SSD-KIN-1TB' => 60,
            'PAP-BOND-CJA' => 80,
            'NOT-POST-PAQ' => 90,
            'HER-ROTO-TRU' => 12,
            'HER-JUE-DESM' => 28,
            'CAB-UTP-CAT6' => 15,
            'CIN-DUCK-3M' => 120,
            'MON-DELL-24' => 4, // low stock! min_stock is 8
            // TON-HP-85A remains with 0 stock -> Out of stock!
        ];

        foreach ($initialStock as $sku => $qty) {
            $prod = $createdProducts[$sku];
            $entryAction->execute([
                'product_id' => $prod->id,
                'warehouse_id' => $whCentral->id,
                'quantity' => $qty,
                'reason' => 'Inventario inicial de apertura',
                'reference' => 'INICIAL-2026',
                'notes' => 'Carga inicial autorizada por gerencia de almacenes',
            ], $admin);
        }

        // Some entries in Almacén GDL & MTY
        $entryAction->execute([
            'product_id' => $createdProducts['MOU-LOG-MX3']->id,
            'warehouse_id' => $whGdl->id,
            'quantity' => 15,
            'reason' => 'Recepción de compra directa',
            'reference' => 'FAC-9842',
        ], $supervisor);

        $entryAction->execute([
            'product_id' => $createdProducts['PAP-BOND-CJA']->id,
            'warehouse_id' => $whMty->id,
            'quantity' => 25,
            'reason' => 'Recepción de compra local',
            'reference' => 'FAC-9843',
        ], $supervisor);

        // Transferencia de Central a GDL
        $transferAction->execute([
            'product_id' => $createdProducts['LAP-DELL-5520']->id,
            'warehouse_id' => $whCentral->id,
            'destination_warehouse_id' => $whGdl->id,
            'quantity' => 4,
            'reason' => 'Abastecimiento programado de sucursal Occidente',
            'reference' => 'ORD-TRA-001',
        ], $supervisor);

        // Salida de mercancía
        $exitAction->execute([
            'product_id' => $createdProducts['SSD-KIN-1TB']->id,
            'warehouse_id' => $whCentral->id,
            'quantity' => 5,
            'reason' => 'Consumo interno departamento de sistemas',
            'reference' => 'REQ-SIS-04',
        ], $operator);

        // Ajuste de auditoría
        $adjustmentAction->execute([
            'product_id' => $createdProducts['CIN-DUCK-3M']->id,
            'warehouse_id' => $whCentral->id,
            'real_quantity' => 118,
            'reason' => 'Ajuste tras conteo físico cíclico de bodega',
            'reference' => 'AUD-2026-01',
            'notes' => '2 unidades dañadas por humedad retiradas de estantería',
        ], $supervisor);
    }
}
