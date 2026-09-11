<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppBadge from '@/Components/UI/AppBadge.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppInput from '@/Components/UI/AppInput.vue';
import AppPagination from '@/Components/UI/AppPagination.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import { ArrowLeft, ArrowLeftRight, User, MapPin, Phone, Mail, Eye } from 'lucide-vue-next';

interface WarehouseDetail {
    id: number;
    code: string;
    name: string;
    description?: string;
    address?: string;
    manager_name?: string;
    phone?: string;
    email?: string;
    is_active: boolean;
    total_products: number;
    total_stock: number;
}

interface InventoryItem {
    id: number;
    product_id: number;
    product_sku: string;
    product_name: string;
    category_name: string;
    unit_abbr: string;
    quantity: number;
    min_stock: number;
    status: string;
    updated_at: string;
}

interface Props {
    warehouse: WarehouseDetail;
    inventories: {
        data: InventoryItem[];
        links: any[];
        from: number;
        to: number;
        total: number;
    };
    filters: {
        search?: string;
    };
}

const props = defineProps<Props>();
const search = ref(props.filters.search || '');

let searchTimeout: any = null;
watch(search, (val) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get(
            route('warehouses.show', props.warehouse.id),
            { search: val || undefined },
            { preserveState: true, replace: true }
        );
    }, 400);
});

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
</script>

<template>
    <Head :title="`Almacén: ${warehouse.name}`" />

    <AuthenticatedLayout
        :title="warehouse.name"
        :breadcrumbs="[
            { label: 'Almacenes', href: route('warehouses.index') },
            { label: warehouse.code }
        ]"
    >
        <template #actions>
            <Link :href="route('warehouses.index')">
                <AppButton size="sm" variant="outline">
                    <ArrowLeft class="w-4 h-4" />
                    <span>Regresar</span>
                </AppButton>
            </Link>
        </template>

        <div class="space-y-6">
            <!-- Encabezado con información del almacén -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-5 transition-colors">
                <div class="space-y-2">
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-mono font-bold bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 px-2.5 py-1 rounded">
                            {{ warehouse.code }}
                        </span>
                        <AppBadge :variant="warehouse.is_active ? 'green' : 'slate'" size="sm">
                            {{ warehouse.is_active ? 'Activo' : 'Inactivo' }}
                        </AppBadge>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">{{ warehouse.name }}</h3>
                    <p v-if="warehouse.description" class="text-xs text-slate-600 dark:text-slate-300">{{ warehouse.description }}</p>

                    <div class="flex items-center gap-4 text-xs text-slate-500 dark:text-slate-400 pt-1 flex-wrap">
                        <span v-if="warehouse.manager_name" class="flex items-center gap-1.5">
                            <User class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                            Responsable: <strong class="text-slate-700 dark:text-slate-200">{{ warehouse.manager_name }}</strong>
                        </span>
                        <span v-if="warehouse.address" class="flex items-center gap-1.5">
                            <MapPin class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                            {{ warehouse.address }}
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-8 md:border-l md:border-slate-100 md:dark:border-slate-800 md:pl-8 shrink-0">
                    <div>
                        <span class="text-xs text-slate-400 dark:text-slate-500 block font-medium">Productos registrados</span>
                        <span class="text-2xl font-bold text-slate-900 dark:text-white">{{ warehouse.total_products }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-400 dark:text-slate-500 block font-medium">Total de unidades</span>
                        <span class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ warehouse.total_stock }}</span>
                    </div>
                </div>
            </div>

            <!-- Tabla de existencias de este almacén -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden transition-colors">
                <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h4 class="text-sm font-bold text-slate-900 dark:text-white">Existencias actuales</h4>
                    </div>

                    <div class="w-full sm:w-64">
                        <AppInput
                            v-model="search"
                            placeholder="Buscar por producto o SKU..."
                        />
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/60 text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase">
                                <th class="py-3 px-4">SKU</th>
                                <th class="py-3 px-4">Producto</th>
                                <th class="py-3 px-4">Categoría</th>
                                <th class="py-3 px-4 text-center">Existencias</th>
                                <th class="py-3 px-4 text-center">Estado</th>
                                <th class="py-3 px-4 text-right">Actualizado</th>
                                <th class="py-3 px-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                            <tr v-for="inv in inventories.data" :key="inv.id" class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="py-3.5 px-4 font-mono font-medium text-slate-800 dark:text-slate-200">{{ inv.product_sku }}</td>
                                <td class="py-3.5 px-4 font-semibold text-slate-900 dark:text-white">
                                    <Link :href="route('products.show', inv.product_id)" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                        {{ inv.product_name }}
                                    </Link>
                                </td>
                                <td class="py-3.5 px-4 text-slate-600 dark:text-slate-300">{{ inv.category_name }}</td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="font-bold text-slate-900 dark:text-white text-sm">{{ inv.quantity }}</span>
                                    <span class="text-[10px] text-slate-400 dark:text-slate-500 ml-1">{{ inv.unit_abbr }}</span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <AppBadge :variant="getStatusBadge(inv.status).variant" size="sm">
                                        {{ getStatusBadge(inv.status).label }}
                                    </AppBadge>
                                </td>
                                <td class="py-3.5 px-4 text-right text-slate-500 dark:text-slate-400 text-[11px]">{{ inv.updated_at }}</td>
                                <td class="py-3.5 px-4 text-right">
                                    <Link
                                        :href="route('kardex.index', { product_id: inv.product_id, warehouse_id: warehouse.id })"
                                        class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 font-semibold text-xs transition-colors"
                                    >
                                        Kardex &rarr;
                                    </Link>
                                </td>
                            </tr>

                            <tr v-if="inventories.data.length === 0">
                                <td colspan="7" class="p-8">
                                    <EmptyState
                                        title="Este almacén no tiene existencias"
                                        description="Los productos ingresados o transferidos a este almacén aparecerán aquí."
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-slate-100 dark:border-slate-800">
                    <AppPagination
                        :links="inventories.links"
                        :from="inventories.from"
                        :to="inventories.to"
                        :total="inventories.total"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
