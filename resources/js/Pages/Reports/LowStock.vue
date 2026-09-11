<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppBadge from '@/Components/UI/AppBadge.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppInput from '@/Components/UI/AppInput.vue';
import AppSelect from '@/Components/UI/AppSelect.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import { Download, ArrowLeft, RotateCcw, AlertTriangle } from 'lucide-vue-next';

interface LowStockProduct {
    id: number;
    sku: string;
    name: string;
    category_name: string;
    brand_name: string;
    unit_abbr: string;
    stock: number;
    min_stock: number;
    reorder_point: number;
    status: 'low_stock' | 'out_of_stock';
}

interface OptionItem {
    id: number;
    name: string;
}

interface Props {
    products: LowStockProduct[];
    filters: {
        category_id?: string | number;
        search?: string;
    };
    categories: OptionItem[];
}

const props = defineProps<Props>();

const search = ref(props.filters.search || '');
const categoryId = ref(props.filters.category_id || '');

const applyFilters = () => {
    router.get(
        route('reports.low-stock'),
        {
            search: search.value || undefined,
            category_id: categoryId.value || undefined,
        },
        { preserveState: true, replace: true }
    );
};

const resetFilters = () => {
    search.value = '';
    categoryId.value = '';
    applyFilters();
};

let searchTimeout: any = null;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 400);
});
</script>

<template>
    <Head title="Stock bajo" />

    <AuthenticatedLayout
        title="Stock bajo"
        :breadcrumbs="[
            { label: 'Reportes', href: route('reports.index') },
            { label: 'Stock bajo' }
        ]"
    >
        <template #actions>
            <Link :href="route('reports.index')">
                <AppButton size="sm" variant="outline">
                    <ArrowLeft class="w-4 h-4" />
                    <span>Regresar</span>
                </AppButton>
            </Link>
            <a :href="route('reports.low-stock.export', filters)">
                <AppButton size="sm">
                    <Download class="w-4 h-4" />
                    <span>Exportar CSV</span>
                </AppButton>
            </a>
        </template>

        <div class="space-y-4">
            <!-- Barra de filtros -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs transition-colors">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <AppInput
                        v-model="search"
                        placeholder="Buscar por SKU o producto..."
                    />

                    <AppSelect
                        v-model="categoryId"
                        :options="categories"
                        placeholder="Todas las categorías"
                        @update:model-value="applyFilters"
                    />

                    <div class="flex items-center justify-end">
                        <button
                            type="button"
                            title="Limpiar filtros"
                            class="p-2 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors flex items-center gap-1.5 text-xs font-semibold"
                            @click="resetFilters"
                        >
                            <RotateCcw class="w-4 h-4" />
                            <span>Limpiar filtros</span>
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
                                <th class="py-3 px-4">Categoría / Marca</th>
                                <th class="py-3 px-4 text-center text-rose-700 dark:text-rose-400">Existencias</th>
                                <th class="py-3 px-4 text-center">Stock mínimo</th>
                                <th class="py-3 px-4 text-center">Punto de reorden</th>
                                <th class="py-3 px-4 text-center">Estado</th>
                                <th class="py-3 px-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                            <tr v-for="p in products" :key="p.id" class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="py-3.5 px-4 font-mono font-medium text-slate-900 dark:text-white">{{ p.sku }}</td>
                                <td class="py-3.5 px-4 font-semibold text-slate-900 dark:text-white">
                                    <Link :href="route('products.show', p.id)" class="hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                        {{ p.name }}
                                    </Link>
                                </td>
                                <td class="py-3.5 px-4 text-slate-600 dark:text-slate-300">
                                    <div>{{ p.category_name }}</div>
                                    <span class="text-[10px] text-slate-400 dark:text-slate-500">{{ p.brand_name }}</span>
                                </td>
                                <td class="py-3.5 px-4 text-center font-bold text-sm text-slate-900 dark:text-white">
                                    {{ p.stock }} <small class="text-slate-400 dark:text-slate-500">{{ p.unit_abbr }}</small>
                                </td>
                                <td class="py-3.5 px-4 text-center text-slate-600 dark:text-slate-400">{{ p.min_stock }}</td>
                                <td class="py-3.5 px-4 text-center text-amber-600 dark:text-amber-400 font-semibold">{{ p.reorder_point }}</td>
                                <td class="py-3.5 px-4 text-center">
                                    <AppBadge :variant="p.status === 'out_of_stock' ? 'red' : 'amber'" size="sm">
                                        {{ p.status === 'out_of_stock' ? 'Sin existencias' : 'Stock bajo' }}
                                    </AppBadge>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <Link :href="route('movements.entry.create', { product_id: p.id })">
                                        <AppButton size="sm" variant="outline">
                                            <span>Entrada</span>
                                        </AppButton>
                                    </Link>
                                </td>
                            </tr>

                            <tr v-if="products.length === 0">
                                <td colspan="8" class="p-8">
                                    <EmptyState
                                        title="No hay productos con stock bajo"
                                        description="Todas las existencias se encuentran por encima del mínimo."
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
