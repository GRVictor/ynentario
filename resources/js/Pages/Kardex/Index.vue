<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppBadge from '@/Components/UI/AppBadge.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppSelect from '@/Components/UI/AppSelect.vue';
import AppInput from '@/Components/UI/AppInput.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import { ClipboardList, Download, Filter, RotateCcw } from 'lucide-vue-next';

interface ProductOption {
    id: number;
    name: string;
    sku: string;
}

interface WarehouseOption {
    id: number;
    name: string;
    code: string;
}

interface KardexRow {
    id: number;
    folio: string;
    date: string;
    type: string;
    type_label: string;
    warehouse_name: string;
    destination_warehouse_name?: string;
    entry: number | null;
    exit: number | null;
    unit_cost?: number | null;
    previous_quantity: number;
    new_quantity: number;
    user_name: string;
    reason: string;
    reference?: string;
}

interface Props {
    products: ProductOption[];
    warehouses: WarehouseOption[];
    kardexRows: KardexRow[];
    selectedProduct: {
        id: number;
        name: string;
        sku: string;
        unit: string;
        unit_abbr: string;
        min_stock: number;
        reorder_point: number;
    } | null;
    selectedWarehouse: {
        id: number;
        name: string;
        code: string;
    } | null;
    currentBalance: number;
    filters: {
        product_id: number | null;
        warehouse_id: number | null;
        start_date: string | null;
        end_date: string | null;
    };
}

const props = defineProps<Props>();

const productId = ref(props.filters.product_id || (props.products[0]?.id || ''));
const warehouseId = ref(props.filters.warehouse_id || '');
const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');

const applyFilters = () => {
    router.get(
        route('kardex.index'),
        {
            product_id: productId.value || undefined,
            warehouse_id: warehouseId.value || undefined,
            start_date: startDate.value || undefined,
            end_date: endDate.value || undefined,
        },
        { preserveState: true, replace: true }
    );
};

const resetFilters = () => {
    productId.value = props.products[0]?.id || '';
    warehouseId.value = '';
    startDate.value = '';
    endDate.value = '';
    applyFilters();
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
    <Head title="Kardex" />

    <AuthenticatedLayout title="Kardex">
        <template #actions>
            <a
                v-if="selectedProduct"
                :href="route('kardex.export', {
                    product_id: productId,
                    warehouse_id: warehouseId || undefined,
                    start_date: startDate || undefined,
                    end_date: endDate || undefined,
                })"
            >
                <AppButton size="sm" variant="outline">
                    <Download class="w-4 h-4 text-slate-600" />
                    <span>Exportar</span>
                </AppButton>
            </a>
        </template>

        <div class="space-y-6">
            <!-- Barra de filtros -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs transition-colors">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div>
                        <AppSelect
                            v-model="productId"
                            label="Producto"
                            placeholder="Selecciona un producto"
                            @update:model-value="applyFilters"
                            required
                        >
                            <option v-for="p in products" :key="p.id" :value="p.id">
                                {{ p.sku }} - {{ p.name }}
                            </option>
                        </AppSelect>
                    </div>

                    <div>
                        <AppSelect
                            v-model="warehouseId"
                            label="Almacén"
                            placeholder="Todos los almacenes"
                            @update:model-value="applyFilters"
                        >
                            <option v-for="w in warehouses" :key="w.id" :value="w.id">
                                {{ w.code }} - {{ w.name }}
                            </option>
                        </AppSelect>
                    </div>

                    <div>
                        <AppInput
                            v-model="startDate"
                            type="date"
                            label="Desde"
                            @update:model-value="applyFilters"
                        />
                    </div>

                    <div class="flex items-end gap-2">
                        <div class="flex-1">
                            <AppInput
                                v-model="endDate"
                                type="date"
                                label="Hasta"
                                @update:model-value="applyFilters"
                            />
                        </div>
                        <button
                            type="button"
                            title="Limpiar filtros"
                            class="p-2 mb-0.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors"
                            @click="resetFilters"
                        >
                            <RotateCcw class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Resumen de cabecera del producto -->
            <div v-if="selectedProduct" class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4 transition-colors">
                <div>
                    <span class="text-xs font-mono font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-400 px-2 py-0.5 rounded">
                        {{ selectedProduct.sku }}
                    </span>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white mt-1">{{ selectedProduct.name }}</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Almacén: <strong class="text-slate-800 dark:text-slate-200">{{ selectedWarehouse?.name || 'Todos los almacenes' }}</strong>
                    </p>
                </div>

                <div class="flex items-center gap-6 sm:border-l sm:border-slate-100 sm:dark:border-slate-800 sm:pl-6 shrink-0">
                    <div>
                        <span class="text-xs text-slate-400 dark:text-slate-500 block font-medium">Saldo actual</span>
                        <span class="text-2xl font-black text-slate-900 dark:text-white leading-none">
                            {{ currentBalance }}
                            <small class="text-xs font-normal text-slate-500 dark:text-slate-400">{{ selectedProduct.unit_abbr }}</small>
                        </span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-400 dark:text-slate-500 block font-medium">Punto de reorden</span>
                        <span class="text-lg font-bold text-amber-600 dark:text-amber-400 leading-none">
                            {{ selectedProduct.reorder_point }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Tabla cronológica de Kardex -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden transition-colors">
                <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-bold text-slate-900 dark:text-white">Movimientos registrados</h4>
                    </div>
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ kardexRows.length }} movimientos</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/60 text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase">
                                <th class="py-3 px-4">Fecha</th>
                                <th class="py-3 px-4">Folio</th>
                                <th class="py-3 px-4">Tipo</th>
                                <th class="py-3 px-4">Almacén</th>
                                <th class="py-3 px-4 text-right text-emerald-700 dark:text-emerald-400">Entrada (+)</th>
                                <th class="py-3 px-4 text-right text-rose-700 dark:text-rose-400">Salida (-)</th>
                                <th class="py-3 px-4 text-right">Costo unit.</th>
                                <th class="py-3 px-4 text-right">Exist. anterior</th>
                                <th class="py-3 px-4 text-right font-bold text-slate-900 dark:text-white">Saldo</th>
                                <th class="py-3 px-4">Usuario</th>
                                <th class="py-3 px-4">Motivo / Ref.</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                            <tr v-for="row in kardexRows" :key="row.id" class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="py-3.5 px-4 text-slate-500 dark:text-slate-400 whitespace-nowrap">{{ row.date }}</td>
                                <td class="py-3.5 px-4 font-mono font-bold text-slate-900 dark:text-white">{{ row.folio }}</td>
                                <td class="py-3.5 px-4">
                                    <AppBadge :variant="getMovementVariant(row.type)" size="sm">
                                        {{ row.type_label }}
                                    </AppBadge>
                                </td>
                                <td class="py-3.5 px-4 text-slate-800 dark:text-slate-200">
                                    <div>{{ row.warehouse_name }}</div>
                                    <span v-if="row.destination_warehouse_name" class="block text-[10px] text-sky-600 dark:text-sky-400 font-medium">
                                        &rarr; {{ row.destination_warehouse_name }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right font-bold text-emerald-600 dark:text-emerald-400">
                                    {{ row.entry !== null ? `+${row.entry}` : '-' }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-bold text-rose-600 dark:text-rose-400">
                                    {{ row.exit !== null ? `-${row.exit}` : '-' }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono text-slate-700 dark:text-slate-300">
                                    {{ row.unit_cost !== null && row.unit_cost !== undefined ? `$${Number(row.unit_cost).toFixed(2)}` : '-' }}
                                </td>
                                <td class="py-3.5 px-4 text-right text-slate-500 dark:text-slate-400 font-mono">{{ row.previous_quantity }}</td>
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-900 dark:text-white bg-slate-50/50 dark:bg-slate-800/40">
                                    {{ row.new_quantity }}
                                </td>
                                <td class="py-3.5 px-4 text-slate-700 dark:text-slate-300 whitespace-nowrap">{{ row.user_name }}</td>
                                <td class="py-3.5 px-4 text-slate-600 dark:text-slate-300 max-w-xs">
                                    <div class="truncate">{{ row.reason }}</div>
                                    <span v-if="row.reference" class="text-[10px] text-slate-400 dark:text-slate-500 font-mono">Ref: {{ row.reference }}</span>
                                </td>
                            </tr>

                            <tr v-if="kardexRows.length === 0">
                                <td colspan="11" class="p-8">
                                    <EmptyState
                                        title="No encontramos movimientos"
                                        description="Prueba cambiando los filtros o el rango de fechas."
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
