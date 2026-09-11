<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppBadge from '@/Components/UI/AppBadge.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppCard from '@/Components/UI/AppCard.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import {
    Edit2,
    ArrowDownLeft,
    ArrowUpRight,
    ArrowLeftRight,
    Info,
    Warehouse,
    ClipboardList,
    Barcode,
    MapPin,
} from 'lucide-vue-next';

interface ProductDetail {
    id: number;
    sku: string;
    internal_code?: string;
    barcode?: string;
    name: string;
    description?: string;
    category_name: string;
    brand_name: string;
    unit_name: string;
    unit_abbr: string;
    supplier_name: string;
    cost_price: number;
    selling_price: number;
    min_stock: number;
    max_stock: number | null;
    reorder_point: number;
    default_location?: string;
    notes?: string;
    is_active: boolean;
    total_stock: number;
    stock_status: string;
    has_movements: boolean;
    created_at: string;
    updated_at: string;
}

interface WarehouseBalance {
    warehouse_id: number;
    warehouse_name: string;
    warehouse_code: string;
    quantity: number;
    status: string;
    updated_at: string;
}

interface ProductMovement {
    id: number;
    folio: string;
    type: string;
    type_label: string;
    warehouse_name: string;
    destination_warehouse_name?: string;
    quantity: number;
    previous_quantity: number;
    new_quantity: number;
    user_name: string;
    reason: string;
    reference?: string;
    created_at: string;
}

interface Props {
    product: ProductDetail;
    warehouseBalances: WarehouseBalance[];
    movements: ProductMovement[];
}

const props = defineProps<Props>();

const activeTab = ref<'general' | 'existencias' | 'movimientos'>('general');

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN',
    }).format(amount);
};

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'in_stock':
            return { variant: 'green' as const, label: 'Disponible' };
        case 'low_stock':
            return { variant: 'amber' as const, label: 'Stock bajo' };
        case 'out_of_stock':
            return { variant: 'red' as const, label: 'Sin existencias' };
        default:
            return { variant: 'slate' as const, label: 'Inactivo' };
    }
};

const getMovementVariant = (type: string) => {
    switch (type) {
        case 'entry': return 'green' as const;
        case 'exit': return 'red' as const;
        case 'transfer': return 'blue' as const;
        case 'adjustment': return 'amber' as const;
        default: return 'slate' as const;
    }
};
</script>

<template>
    <Head :title="`${product.name} (${product.sku})`" />

    <AuthenticatedLayout
        :title="product.name"
        :breadcrumbs="[
            { label: 'Productos', href: route('products.index') },
            { label: product.sku }
        ]"
    >
        <template #actions>
            <Link :href="route('movements.entry.create', { product_id: product.id })">
                <AppButton size="sm" variant="outline">
                    <ArrowDownLeft class="w-4 h-4 text-emerald-600" />
                    <span>Entrada</span>
                </AppButton>
            </Link>
            <Link :href="route('movements.exit.create', { product_id: product.id })">
                <AppButton size="sm" variant="outline">
                    <ArrowUpRight class="w-4 h-4 text-rose-600" />
                    <span>Salida</span>
                </AppButton>
            </Link>
            <Link :href="route('products.edit', product.id)">
                <AppButton size="sm">
                    <Edit2 class="w-4 h-4" />
                    <span>Editar</span>
                </AppButton>
            </Link>
        </template>

        <div class="space-y-6">
            <!-- Tarjeta de resumen y cabecera del producto -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs transition-colors">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-100 dark:border-indigo-900 flex items-center justify-center text-indigo-700 dark:text-indigo-400 font-bold font-mono text-base shrink-0">
                            {{ product.sku.substring(0, 3) }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ product.name }}</h3>
                                <AppBadge :variant="getStatusBadge(product.stock_status).variant">
                                    {{ getStatusBadge(product.stock_status).label }}
                                </AppBadge>
                            </div>
                            <div class="flex items-center gap-4 text-xs text-slate-500 dark:text-slate-400 mt-1 flex-wrap font-mono">
                                <span>SKU: <strong class="text-slate-800 dark:text-slate-200">{{ product.sku }}</strong></span>
                                <span v-if="product.barcode">Código de barras: <strong class="text-slate-800 dark:text-slate-200">{{ product.barcode }}</strong></span>
                                <span>Categoría: <strong class="text-slate-800 dark:text-slate-200 font-sans">{{ product.category_name }}</strong></span>
                                <span>Marca: <strong class="text-slate-800 dark:text-slate-200 font-sans">{{ product.brand_name }}</strong></span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-6 sm:border-l sm:border-slate-100 sm:dark:border-slate-800 sm:pl-6 shrink-0">
                        <div>
                            <span class="text-xs text-slate-400 dark:text-slate-500 block font-medium">Existencias</span>
                            <span class="text-2xl font-black text-slate-900 dark:text-white leading-none">
                                {{ product.total_stock }}
                                <small class="text-xs font-normal text-slate-500 dark:text-slate-400">{{ product.unit_abbr }}</small>
                            </span>
                        </div>
                        <div>
                            <span class="text-xs text-slate-400 dark:text-slate-500 block font-medium">Precio de venta</span>
                            <span class="text-lg font-bold text-emerald-600 dark:text-emerald-400 leading-none">
                                {{ formatCurrency(product.selling_price) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Navegación de pestañas -->
                <div class="flex items-center gap-2 mt-6 pt-4 border-t border-slate-100 dark:border-slate-800 text-xs font-semibold">
                    <button
                        type="button"
                        @click="activeTab = 'general'"
                        :class="[
                            'px-4 py-2 rounded-lg transition-colors flex items-center gap-2',
                            activeTab === 'general'
                                ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-400'
                                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800',
                        ]"
                    >
                        <Info class="w-4 h-4" />
                        <span>General</span>
                    </button>

                    <button
                        type="button"
                        @click="activeTab = 'existencias'"
                        :class="[
                            'px-4 py-2 rounded-lg transition-colors flex items-center gap-2',
                            activeTab === 'existencias'
                                ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-400'
                                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800',
                        ]"
                    >
                        <Warehouse class="w-4 h-4" />
                        <span>Existencias por almacén</span>
                        <span class="bg-indigo-200/60 dark:bg-indigo-900/60 text-indigo-800 dark:text-indigo-300 rounded-full px-1.5 py-0.2 text-[10px]">
                            {{ warehouseBalances.length }}
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="activeTab = 'movimientos'"
                        :class="[
                            'px-4 py-2 rounded-lg transition-colors flex items-center gap-2',
                            activeTab === 'movimientos'
                                ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-400'
                                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800',
                        ]"
                    >
                        <ClipboardList class="w-4 h-4" />
                        <span>Movimientos</span>
                        <span class="bg-indigo-200/60 dark:bg-indigo-900/60 text-indigo-800 dark:text-indigo-300 rounded-full px-1.5 py-0.2 text-[10px]">
                            {{ movements.length }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- Pestaña 1: Datos generales -->
            <div v-if="activeTab === 'general'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Tarjeta de datos del producto -->
                <AppCard title="Datos del producto">
                    <dl class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                        <div class="py-2.5 flex justify-between">
                            <dt class="text-slate-500 dark:text-slate-400">SKU</dt>
                            <dd class="font-mono font-bold text-slate-800 dark:text-slate-200">{{ product.sku }}</dd>
                        </div>
                        <div class="py-2.5 flex justify-between">
                            <dt class="text-slate-500 dark:text-slate-400">Código interno</dt>
                            <dd class="font-mono text-slate-800 dark:text-slate-200">{{ product.internal_code || 'No asignado' }}</dd>
                        </div>
                        <div class="py-2.5 flex justify-between">
                            <dt class="text-slate-500 dark:text-slate-400">Código de barras</dt>
                            <dd class="font-mono text-slate-800 dark:text-slate-200">{{ product.barcode || 'Sin código' }}</dd>
                        </div>
                        <div class="py-2.5 flex justify-between">
                            <dt class="text-slate-500 dark:text-slate-400">Categoría</dt>
                            <dd class="font-medium text-slate-800 dark:text-slate-200">{{ product.category_name }}</dd>
                        </div>
                        <div class="py-2.5 flex justify-between">
                            <dt class="text-slate-500 dark:text-slate-400">Marca</dt>
                            <dd class="font-medium text-slate-800 dark:text-slate-200">{{ product.brand_name }}</dd>
                        </div>
                        <div class="py-2.5 flex justify-between">
                            <dt class="text-slate-500 dark:text-slate-400">Unidad de medida</dt>
                            <dd class="font-medium text-slate-800 dark:text-slate-200">{{ product.unit_name }} ({{ product.unit_abbr }})</dd>
                        </div>
                        <div class="py-2.5 flex justify-between">
                            <dt class="text-slate-500 dark:text-slate-400">Proveedor principal</dt>
                            <dd class="font-medium text-slate-800 dark:text-slate-200">{{ product.supplier_name }}</dd>
                        </div>
                        <div class="py-2.5 flex justify-between">
                            <dt class="text-slate-500 dark:text-slate-400">Ubicación predeterminada</dt>
                            <dd class="font-medium text-slate-800 dark:text-slate-200">{{ product.default_location || 'Sin ubicación' }}</dd>
                        </div>
                    </dl>
                </AppCard>

                <!-- Reglas de stock y datos financieros -->
                <div class="space-y-6">
                    <AppCard title="Precios">
                        <dl class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                            <div class="py-2.5 flex justify-between">
                                <dt class="text-slate-500 dark:text-slate-400">Precio de costo</dt>
                                <dd class="font-bold text-slate-900 dark:text-white">{{ formatCurrency(product.cost_price) }}</dd>
                            </div>
                            <div class="py-2.5 flex justify-between">
                                <dt class="text-slate-500 dark:text-slate-400">Precio de venta</dt>
                                <dd class="font-bold text-emerald-700 dark:text-emerald-400">{{ formatCurrency(product.selling_price) }}</dd>
                            </div>
                            <div class="py-2.5 flex justify-between">
                                <dt class="text-slate-500 dark:text-slate-400">Margen estimado</dt>
                                <dd class="font-medium text-slate-700 dark:text-slate-300">
                                    {{ product.selling_price > 0 ? (((product.selling_price - product.cost_price) / product.selling_price) * 100).toFixed(1) + '%' : '0%' }}
                                </dd>
                            </div>
                        </dl>
                    </AppCard>

                    <AppCard title="Límites de stock">
                        <dl class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                            <div class="py-2.5 flex justify-between">
                                <dt class="text-slate-500 dark:text-slate-400">Stock mínimo</dt>
                                <dd class="font-bold text-slate-800 dark:text-slate-200">{{ product.min_stock }} {{ product.unit_abbr }}</dd>
                            </div>
                            <div class="py-2.5 flex justify-between">
                                <dt class="text-slate-500 dark:text-slate-400">Punto de reorden</dt>
                                <dd class="font-bold text-amber-600 dark:text-amber-400">{{ product.reorder_point }} {{ product.unit_abbr }}</dd>
                            </div>
                            <div class="py-2.5 flex justify-between">
                                <dt class="text-slate-500 dark:text-slate-400">Stock máximo</dt>
                                <dd class="font-medium text-slate-700 dark:text-slate-300">{{ product.max_stock ? `${product.max_stock} ${product.unit_abbr}` : 'Sin límite' }}</dd>
                            </div>
                        </dl>
                    </AppCard>
                </div>
            </div>

            <!-- Pestaña 2: Existencias por almacén -->
            <div v-if="activeTab === 'existencias'" class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden transition-colors">
                <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-bold text-slate-900 dark:text-white">Existencias por almacén</h4>
                    </div>
                    <Link :href="route('movements.transfer.create', { product_id: product.id })">
                        <AppButton size="sm" variant="outline">
                            <ArrowLeftRight class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                            <span>Transferir</span>
                        </AppButton>
                    </Link>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/60 text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                <th class="py-3 px-4">Código</th>
                                <th class="py-3 px-4">Almacén</th>
                                <th class="py-3 px-4 text-center">Cantidad disponible</th>
                                <th class="py-3 px-4 text-center">Estado</th>
                                <th class="py-3 px-4 text-right">Actualizado</th>
                                <th class="py-3 px-4 text-right">Kardex</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                            <tr
                                v-for="wh in warehouseBalances"
                                :key="wh.warehouse_id"
                                class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition-colors"
                            >
                                <td class="py-3 px-4 font-mono font-medium text-slate-700 dark:text-slate-300">{{ wh.warehouse_code }}</td>
                                <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">{{ wh.warehouse_name }}</td>
                                <td class="py-3 px-4 text-center">
                                    <span class="text-sm font-bold text-slate-900 dark:text-white">{{ wh.quantity }}</span>
                                    <span class="text-[10px] text-slate-400 dark:text-slate-500 ml-1">{{ product.unit_abbr }}</span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <AppBadge :variant="getStatusBadge(wh.status).variant" size="sm">
                                        {{ getStatusBadge(wh.status).label }}
                                    </AppBadge>
                                </td>
                                <td class="py-3 px-4 text-right text-slate-500 dark:text-slate-400 text-[11px]">{{ wh.updated_at }}</td>
                                <td class="py-3 px-4 text-right">
                                    <Link
                                        :href="route('kardex.index', { product_id: product.id, warehouse_id: wh.warehouse_id })"
                                        class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 font-semibold transition-colors"
                                    >
                                        Ver Kardex &rarr;
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="warehouseBalances.length === 0">
                                <td colspan="6" class="p-8">
                                    <EmptyState
                                        title="Sin existencias"
                                        description="Este producto aún no tiene existencias registradas en ningún almacén."
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pestaña 3: Movimientos recientes -->
            <div v-if="activeTab === 'movimientos'" class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden transition-colors">
                <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-bold text-slate-900 dark:text-white">Movimientos</h4>
                    </div>
                    <Link :href="route('kardex.index', { product_id: product.id })">
                        <AppButton size="sm" variant="outline">
                            <ClipboardList class="w-4 h-4" />
                            <span>Ver Kardex</span>
                        </AppButton>
                    </Link>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/60 text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                <th class="py-3 px-4">Folio</th>
                                <th class="py-3 px-4">Fecha</th>
                                <th class="py-3 px-4">Tipo</th>
                                <th class="py-3 px-4">Almacén</th>
                                <th class="py-3 px-4 text-right">Cantidad</th>
                                <th class="py-3 px-4 text-right">Existencia ant.</th>
                                <th class="py-3 px-4 text-right">Existencia post.</th>
                                <th class="py-3 px-4">Usuario</th>
                                <th class="py-3 px-4">Motivo / Ref.</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                            <tr
                                v-for="m in movements"
                                :key="m.id"
                                class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition-colors"
                            >
                                <td class="py-3 px-4 font-mono font-bold text-slate-800 dark:text-slate-200">{{ m.folio }}</td>
                                <td class="py-3 px-4 text-slate-500 dark:text-slate-400 text-[11px]">{{ m.created_at }}</td>
                                <td class="py-3 px-4">
                                    <AppBadge :variant="getMovementVariant(m.type)" size="sm">
                                        {{ m.type_label }}
                                    </AppBadge>
                                </td>
                                <td class="py-3 px-4 text-slate-800 dark:text-slate-200">
                                    {{ m.warehouse_name }}
                                    <span v-if="m.destination_warehouse_name" class="block text-[10px] text-sky-600 dark:text-sky-400 font-medium">
                                        &rarr; {{ m.destination_warehouse_name }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right font-bold text-slate-900 dark:text-white">
                                    {{ m.type === 'entry' ? '+' : (m.type === 'exit' ? '-' : '') }}{{ m.quantity }}
                                </td>
                                <td class="py-3 px-4 text-right text-slate-500 dark:text-slate-400 font-mono">{{ m.previous_quantity }}</td>
                                <td class="py-3 px-4 text-right font-semibold text-slate-800 dark:text-slate-200 font-mono">{{ m.new_quantity }}</td>
                                <td class="py-3 px-4 text-slate-700 dark:text-slate-300">{{ m.user_name }}</td>
                                <td class="py-3 px-4 text-slate-600 dark:text-slate-300">
                                    <div>{{ m.reason }}</div>
                                    <span v-if="m.reference" class="text-[10px] text-slate-400 dark:text-slate-500 font-mono">Ref: {{ m.reference }}</span>
                                </td>
                            </tr>

                            <tr v-if="movements.length === 0">
                                <td colspan="9" class="p-8">
                                    <EmptyState
                                        title="Sin movimientos"
                                        description="Este producto aún no tiene movimientos registrados."
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
