<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppBadge from '@/Components/UI/AppBadge.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppInput from '@/Components/UI/AppInput.vue';
import AppSelect from '@/Components/UI/AppSelect.vue';
import AppPagination from '@/Components/UI/AppPagination.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import { Download, ArrowLeft, RotateCcw } from 'lucide-vue-next';

interface StockReportItem {
    id: number;
    sku: string;
    product_name: string;
    warehouse_name: string;
    category_name: string;
    brand_name: string;
    unit_abbr: string;
    quantity: number;
    min_stock: number;
    reorder_point: number;
    status: string;
}

interface OptionItem {
    id: number;
    name: string;
}

interface Props {
    inventories: {
        data: StockReportItem[];
        links: any[];
        from: number;
        to: number;
        total: number;
    };
    filters: {
        warehouse_id?: string | number;
        category_id?: string | number;
        brand_id?: string | number;
        search?: string;
    };
    warehouses: OptionItem[];
    categories: OptionItem[];
    brands: OptionItem[];
}

const props = defineProps<Props>();

const search = ref(props.filters.search || '');
const warehouseId = ref(props.filters.warehouse_id || '');
const categoryId = ref(props.filters.category_id || '');
const brandId = ref(props.filters.brand_id || '');

const applyFilters = () => {
    router.get(
        route('reports.current-stock'),
        {
            search: search.value || undefined,
            warehouse_id: warehouseId.value || undefined,
            category_id: categoryId.value || undefined,
            brand_id: brandId.value || undefined,
        },
        { preserveState: true, replace: true }
    );
};

const resetFilters = () => {
    search.value = '';
    warehouseId.value = '';
    categoryId.value = '';
    brandId.value = '';
    applyFilters();
};

let searchTimeout: any = null;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 400);
});

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'in_stock': return { variant: 'green' as const, label: 'Disponible' };
        case 'low_stock': return { variant: 'amber' as const, label: 'Stock bajo' };
        case 'out_of_stock': return { variant: 'red' as const, label: 'Sin existencias' };
        default: return { variant: 'slate' as const, label: 'Inactivo' };
    }
};
</script>

<template>
    <Head title="Existencias actuales" />

    <AuthenticatedLayout
        title="Existencias actuales"
        :breadcrumbs="[
            { label: 'Reportes', href: route('reports.index') },
            { label: 'Existencias actuales' }
        ]"
    >
        <template #actions>
            <Link :href="route('reports.index')">
                <AppButton size="sm" variant="outline">
                    <ArrowLeft class="w-4 h-4" />
                    <span>Regresar</span>
                </AppButton>
            </Link>
            <a :href="route('reports.current-stock.export', filters)">
                <AppButton size="sm">
                    <Download class="w-4 h-4" />
                    <span>Exportar CSV</span>
                </AppButton>
            </a>
        </template>

        <div class="space-y-4">
            <!-- Barra de filtros -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs transition-colors">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <AppInput
                        v-model="search"
                        placeholder="Buscar por SKU o producto..."
                    />

                    <AppSelect
                        v-model="warehouseId"
                        :options="warehouses"
                        placeholder="Todos los almacenes"
                        @update:model-value="applyFilters"
                    />

                    <AppSelect
                        v-model="categoryId"
                        :options="categories"
                        placeholder="Todas las categorías"
                        @update:model-value="applyFilters"
                    />

                    <div class="flex items-center gap-2">
                        <div class="flex-1">
                            <AppSelect
                                v-model="brandId"
                                :options="brands"
                                placeholder="Todas las marcas"
                                @update:model-value="applyFilters"
                            />
                        </div>
                        <button
                            type="button"
                            title="Limpiar filtros"
                            class="p-2 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
                            @click="resetFilters"
                        >
                            <RotateCcw class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabla de resultados -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden transition-colors">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/60 text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase">
                                <th class="py-3 px-4">SKU</th>
                                <th class="py-3 px-4">Producto</th>
                                <th class="py-3 px-4">Almacén</th>
                                <th class="py-3 px-4">Categoría / Marca</th>
                                <th class="py-3 px-4 text-center">Existencias</th>
                                <th class="py-3 px-4 text-center">Stock mínimo</th>
                                <th class="py-3 px-4 text-center">Punto de reorden</th>
                                <th class="py-3 px-4 text-center">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                            <tr v-for="inv in inventories.data" :key="inv.id" class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="py-3.5 px-4 font-mono font-medium text-slate-900 dark:text-white">{{ inv.sku }}</td>
                                <td class="py-3.5 px-4 font-semibold text-slate-900 dark:text-white">{{ inv.product_name }}</td>
                                <td class="py-3.5 px-4 text-slate-800 dark:text-slate-200">{{ inv.warehouse_name }}</td>
                                <td class="py-3.5 px-4 text-slate-600 dark:text-slate-300">
                                    <span>{{ inv.category_name }}</span>
                                    <span class="text-[10px] text-slate-400 dark:text-slate-500 block">{{ inv.brand_name }}</span>
                                </td>
                                <td class="py-3.5 px-4 text-center font-bold text-slate-900 dark:text-white">
                                    {{ inv.quantity }} <small class="text-slate-400 dark:text-slate-500">{{ inv.unit_abbr }}</small>
                                </td>
                                <td class="py-3.5 px-4 text-center text-slate-600 dark:text-slate-400">{{ inv.min_stock }}</td>
                                <td class="py-3.5 px-4 text-center text-amber-600 dark:text-amber-400 font-semibold">{{ inv.reorder_point }}</td>
                                <td class="py-3.5 px-4 text-center">
                                    <AppBadge :variant="getStatusBadge(inv.status).variant" size="sm">
                                        {{ getStatusBadge(inv.status).label }}
                                    </AppBadge>
                                </td>
                            </tr>

                            <tr v-if="inventories.data.length === 0">
                                <td colspan="8" class="p-8">
                                    <EmptyState
                                        title="No encontramos productos"
                                        description="Prueba cambiando los filtros de búsqueda."
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
