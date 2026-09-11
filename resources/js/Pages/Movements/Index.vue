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
import {
    ArrowDownLeft,
    ArrowUpRight,
    ArrowLeftRight,
    SlidersHorizontal,
    Download,
    RotateCcw,
} from 'lucide-vue-next';

interface MovementItem {
    id: number;
    folio: string;
    type: 'entry' | 'exit' | 'adjustment' | 'transfer';
    type_label: string;
    product_id: number;
    product_name: string;
    product_sku: string;
    warehouse_name: string;
    destination_warehouse_name?: string;
    quantity: number;
    previous_quantity: number;
    new_quantity: number;
    unit_cost?: number | null;
    user_name: string;
    reason: string;
    reference?: string;
    created_at: string;
}

interface OptionItem {
    id: number;
    name: string;
    sku?: string;
}

interface Props {
    movements: {
        data: MovementItem[];
        links: any[];
        from: number;
        to: number;
        total: number;
    };
    filters: {
        type?: string;
        warehouse_id?: string | number;
        product_id?: string | number;
        user_id?: string | number;
        start_date?: string;
        end_date?: string;
        search?: string;
    };
    warehouses: OptionItem[];
    products: OptionItem[];
    users: OptionItem[];
}

const props = defineProps<Props>();

const search = ref(props.filters.search || '');
const type = ref(props.filters.type || '');
const warehouseId = ref(props.filters.warehouse_id || '');
const productId = ref(props.filters.product_id || '');
const userId = ref(props.filters.user_id || '');
const startDate = ref(props.filters.start_date || '');
const endDate = ref(props.filters.end_date || '');

const applyFilters = () => {
    router.get(
        route('movements.index'),
        {
            search: search.value || undefined,
            type: type.value || undefined,
            warehouse_id: warehouseId.value || undefined,
            product_id: productId.value || undefined,
            user_id: userId.value || undefined,
            start_date: startDate.value || undefined,
            end_date: endDate.value || undefined,
        },
        { preserveState: true, replace: true }
    );
};

const resetFilters = () => {
    search.value = '';
    type.value = '';
    warehouseId.value = '';
    productId.value = '';
    userId.value = '';
    startDate.value = '';
    endDate.value = '';
    applyFilters();
};

let searchTimeout: any = null;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 400);
});

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
    <Head title="Movimientos" />

    <AuthenticatedLayout title="Movimientos">
        <template #actions>
            <a :href="route('movements.export', filters)">
                <AppButton size="sm" variant="outline">
                    <Download class="w-4 h-4 text-slate-600" />
                    <span>Exportar</span>
                </AppButton>
            </a>
            <Link :href="route('movements.entry.create')">
                <AppButton size="sm" variant="outline">
                    <ArrowDownLeft class="w-4 h-4 text-emerald-600" />
                    <span>Entrada</span>
                </AppButton>
            </Link>
            <Link :href="route('movements.exit.create')">
                <AppButton size="sm" variant="outline">
                    <ArrowUpRight class="w-4 h-4 text-rose-600" />
                    <span>Salida</span>
                </AppButton>
            </Link>
            <Link :href="route('movements.adjustment.create')">
                <AppButton size="sm" variant="outline">
                    <SlidersHorizontal class="w-4 h-4 text-amber-600" />
                    <span>Ajuste</span>
                </AppButton>
            </Link>
            <Link :href="route('movements.transfer.create')">
                <AppButton size="sm">
                    <ArrowLeftRight class="w-4 h-4" />
                    <span>Transferencia</span>
                </AppButton>
            </Link>
        </template>

        <div class="space-y-4">
            <!-- Barra de filtros -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs transition-colors">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <AppInput
                        v-model="search"
                        placeholder="Buscar por folio, referencia o motivo..."
                    />

                    <AppSelect
                        v-model="type"
                        placeholder="Todos los tipos"
                        @update:model-value="applyFilters"
                    >
                        <option value="entry">Entrada</option>
                        <option value="exit">Salida</option>
                        <option value="adjustment">Ajuste</option>
                        <option value="transfer">Transferencia</option>
                    </AppSelect>

                    <AppSelect
                        v-model="warehouseId"
                        :options="warehouses"
                        placeholder="Todos los almacenes"
                        @update:model-value="applyFilters"
                    />

                    <AppSelect
                        v-model="productId"
                        :options="products"
                        placeholder="Todos los productos"
                        @update:model-value="applyFilters"
                    />
                </div>

                <!-- Barra de rango de fechas -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                    <AppInput
                        v-model="startDate"
                        type="date"
                        label="Desde"
                        @update:model-value="applyFilters"
                    />

                    <AppInput
                        v-model="endDate"
                        type="date"
                        label="Hasta"
                        @update:model-value="applyFilters"
                    />

                    <div class="flex items-end gap-2">
                        <div class="flex-1">
                            <AppSelect
                                v-model="userId"
                                :options="users"
                                label="Usuario"
                                placeholder="Todos los usuarios"
                                @update:model-value="applyFilters"
                            />
                        </div>
                        <button
                            type="button"
                            title="Limpiar filtros"
                            class="p-2 mb-0.5 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
                            @click="resetFilters"
                        >
                            <RotateCcw class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabla de movimientos -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden transition-colors">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/60 text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase">
                                <th class="py-3 px-4">Folio</th>
                                <th class="py-3 px-4">Fecha</th>
                                <th class="py-3 px-4">Tipo</th>
                                <th class="py-3 px-4">Producto</th>
                                <th class="py-3 px-4">Almacén</th>
                                <th class="py-3 px-4 text-right">Cantidad</th>
                                <th class="py-3 px-4 text-right">Antes / Después</th>
                                <th class="py-3 px-4">Usuario</th>
                                <th class="py-3 px-4">Motivo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                            <tr v-for="m in movements.data" :key="m.id" class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="py-3.5 px-4 font-mono font-bold text-slate-900 dark:text-white">{{ m.folio }}</td>
                                <td class="py-3.5 px-4 text-slate-500 dark:text-slate-400 text-[11px] whitespace-nowrap">{{ m.created_at }}</td>
                                <td class="py-3.5 px-4">
                                    <AppBadge :variant="getMovementVariant(m.type)" size="sm">
                                        {{ m.type_label }}
                                    </AppBadge>
                                </td>
                                <td class="py-3.5 px-4">
                                    <Link :href="route('products.show', m.product_id)" class="font-semibold text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 block transition-colors">
                                        {{ m.product_name }}
                                    </Link>
                                    <span class="text-[10px] font-mono text-slate-400 dark:text-slate-500">{{ m.product_sku }}</span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-800 dark:text-slate-200">
                                    <div>{{ m.warehouse_name }}</div>
                                    <span v-if="m.destination_warehouse_name" class="block text-[10px] text-sky-600 dark:text-sky-400 font-medium">
                                        &rarr; Destino: {{ m.destination_warehouse_name }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right font-bold text-sm">
                                    <span
                                        :class="[
                                            m.type === 'entry' && 'text-emerald-600 dark:text-emerald-400',
                                            m.type === 'exit' && 'text-rose-600 dark:text-rose-400',
                                            m.type === 'transfer' && 'text-sky-600 dark:text-sky-400',
                                            m.type === 'adjustment' && 'text-amber-600 dark:text-amber-400',
                                        ]"
                                    >
                                        {{ m.type === 'entry' ? '+' : (m.type === 'exit' ? '-' : '') }}{{ m.quantity }}
                                    </span>
                                    <span v-if="m.unit_cost !== null && m.unit_cost !== undefined" class="block text-[10px] font-mono text-slate-400 dark:text-slate-500 font-normal">
                                        ${{ Number(m.unit_cost).toFixed(2) }} c/u
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right text-[11px] text-slate-500 dark:text-slate-400">
                                    <span>{{ m.previous_quantity }}</span> &rarr;
                                    <strong class="text-slate-800 dark:text-slate-200">{{ m.new_quantity }}</strong>
                                </td>
                                <td class="py-3.5 px-4 text-slate-700 dark:text-slate-300 whitespace-nowrap">{{ m.user_name }}</td>
                                <td class="py-3.5 px-4 text-slate-600 dark:text-slate-300 max-w-xs">
                                    <div class="truncate">{{ m.reason }}</div>
                                    <span v-if="m.reference" class="text-[10px] text-slate-400 dark:text-slate-500 font-mono">Ref: {{ m.reference }}</span>
                                </td>
                            </tr>

                            <tr v-if="movements.data.length === 0">
                                <td colspan="9" class="p-8">
                                    <EmptyState
                                        title="No encontramos movimientos"
                                        description="Prueba cambiando los filtros o el rango de fechas."
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-slate-100 dark:border-slate-800">
                    <AppPagination
                        :links="movements.links"
                        :from="movements.from"
                        :to="movements.to"
                        :total="movements.total"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
