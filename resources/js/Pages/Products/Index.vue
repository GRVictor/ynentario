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
import ConfirmDialog from '@/Components/UI/ConfirmDialog.vue';
import {
    Plus,
    Upload,
    Download,
    Search,
    Filter,
    Eye,
    Edit2,
    Trash2,
    RotateCcw,
} from 'lucide-vue-next';

interface ProductItem {
    id: number;
    sku: string;
    name: string;
    category_name: string;
    brand_name: string;
    unit_abbr: string;
    cost_price: number;
    selling_price: number;
    min_stock: number;
    reorder_point: number;
    total_stock: number;
    stock_status: 'in_stock' | 'low_stock' | 'out_of_stock' | 'inactive';
    is_active: boolean;
}

interface OptionItem {
    id: number;
    name: string;
}

interface Props {
    products: {
        data: ProductItem[];
        links: any[];
        from: number;
        to: number;
        total: number;
    };
    filters: {
        search?: string;
        category_id?: string | number;
        brand_id?: string | number;
        stock_status?: string;
        warehouse_id?: string | number;
        is_active?: string;
    };
    categories: OptionItem[];
    brands: OptionItem[];
    warehouses: OptionItem[];
}

const props = defineProps<Props>();

const search = ref(props.filters.search || '');
const categoryId = ref(props.filters.category_id || '');
const brandId = ref(props.filters.brand_id || '');
const stockStatus = ref(props.filters.stock_status || '');
const warehouseId = ref(props.filters.warehouse_id || '');

const deleteModalOpen = ref(false);
const productToDelete = ref<ProductItem | null>(null);
const deleteLoading = ref(false);

const applyFilters = () => {
    router.get(
        route('products.index'),
        {
            search: search.value || undefined,
            category_id: categoryId.value || undefined,
            brand_id: brandId.value || undefined,
            stock_status: stockStatus.value || undefined,
            warehouse_id: warehouseId.value || undefined,
        },
        { preserveState: true, replace: true }
    );
};

const resetFilters = () => {
    search.value = '';
    categoryId.value = '';
    brandId.value = '';
    stockStatus.value = '';
    warehouseId.value = '';
    applyFilters();
};

let searchTimeout: any = null;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 400);
});

const confirmDelete = (product: ProductItem) => {
    productToDelete.value = product;
    deleteModalOpen.value = true;
};

const executeDelete = () => {
    if (!productToDelete.value) return;

    deleteLoading.value = true;
    router.delete(route('products.destroy', productToDelete.value.id), {
        onFinish: () => {
            deleteLoading.value = false;
            deleteModalOpen.value = false;
            productToDelete.value = null;
        },
    });
};

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
        case 'inactive':
        default:
            return { variant: 'slate' as const, label: 'Inactivo' };
    }
};
</script>

<template>
    <Head title="Productos" />

    <AuthenticatedLayout title="Productos">
        <template #actions>
            <Link :href="route('products.import')">
                <AppButton size="sm" variant="outline">
                    <Upload class="w-4 h-4 text-slate-600" />
                    <span>Importar CSV</span>
                </AppButton>
            </Link>
            <a :href="route('products.export', filters)">
                <AppButton size="sm" variant="outline">
                    <Download class="w-4 h-4 text-slate-600" />
                    <span>Exportar</span>
                </AppButton>
            </a>
            <Link :href="route('products.create')">
                <AppButton size="sm">
                    <Plus class="w-4 h-4" />
                    <span>Nuevo Producto</span>
                </AppButton>
            </Link>
        </template>

        <div class="space-y-4">
            <!-- Panel de búsqueda y filtros -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs transition-colors">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                    <!-- Campo de búsqueda -->
                    <div class="lg:col-span-1">
                        <AppInput
                            v-model="search"
                            placeholder="Buscar por nombre, SKU..."
                        />
                    </div>

                    <!-- Filtro por categoría -->
                    <div>
                        <AppSelect
                            v-model="categoryId"
                            :options="categories"
                            placeholder="Todas las categorías"
                            @update:model-value="applyFilters"
                        />
                    </div>

                    <!-- Filtro por marca -->
                    <div>
                        <AppSelect
                            v-model="brandId"
                            :options="brands"
                            placeholder="Todas las marcas"
                            @update:model-value="applyFilters"
                        />
                    </div>

                    <!-- Filtro por almacén -->
                    <div>
                        <AppSelect
                            v-model="warehouseId"
                            :options="warehouses"
                            placeholder="Todos los almacenes"
                            @update:model-value="applyFilters"
                        />
                    </div>

                    <!-- Filtro por estado de existencias -->
                    <div class="flex items-center gap-2">
                        <div class="flex-1">
                            <AppSelect
                                v-model="stockStatus"
                                placeholder="Cualquier estado"
                                @update:model-value="applyFilters"
                            >
                                <option value="in_stock">Disponible</option>
                                <option value="low_stock">Stock bajo</option>
                                <option value="out_of_stock">Sin existencias</option>
                                <option value="inactive">Inactivo</option>
                            </AppSelect>
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

            <!-- Tabla de productos -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden transition-colors">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/60 text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                <th class="py-3 px-4">SKU</th>
                                <th class="py-3 px-4">Producto</th>
                                <th class="py-3 px-4">Categoría / Marca</th>
                                <th class="py-3 px-4 text-right">Precio Venta</th>
                                <th class="py-3 px-4 text-center">Existencia</th>
                                <th class="py-3 px-4 text-center">Estado</th>
                                <th class="py-3 px-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                            <tr
                                v-for="product in products.data"
                                :key="product.id"
                                class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition-colors"
                            >
                                <td class="py-3.5 px-4 font-mono font-medium text-slate-900 dark:text-white">
                                    {{ product.sku }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <Link
                                        :href="route('products.show', product.id)"
                                        class="font-semibold text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 block transition-colors"
                                    >
                                        {{ product.name }}
                                    </Link>
                                </td>
                                <td class="py-3.5 px-4 text-slate-600 dark:text-slate-300">
                                    <div class="font-medium text-slate-800 dark:text-slate-200">{{ product.category_name }}</div>
                                    <div class="text-[10px] text-slate-400 dark:text-slate-500">{{ product.brand_name }}</div>
                                </td>
                                <td class="py-3.5 px-4 text-right font-medium text-slate-900 dark:text-white">
                                    {{ formatCurrency(product.selling_price) }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="font-bold text-slate-900 dark:text-white text-sm">
                                        {{ product.total_stock }}
                                    </span>
                                    <span class="text-[10px] text-slate-400 dark:text-slate-500 ml-1">{{ product.unit_abbr }}</span>
                                    <span class="block text-[10px] text-slate-400 dark:text-slate-500">Mín: {{ product.min_stock }}</span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <AppBadge :variant="getStatusBadge(product.stock_status).variant" size="sm">
                                        {{ getStatusBadge(product.stock_status).label }}
                                    </AppBadge>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="inline-flex items-center gap-1">
                                        <Link
                                            :href="route('products.show', product.id)"
                                            class="p-1.5 text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 rounded-md hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                                            title="Ver detalles"
                                        >
                                            <Eye class="w-4 h-4" />
                                        </Link>
                                        <Link
                                            :href="route('products.edit', product.id)"
                                            class="p-1.5 text-slate-500 dark:text-slate-400 hover:text-amber-600 dark:hover:text-amber-400 rounded-md hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                                            title="Editar producto"
                                        >
                                            <Edit2 class="w-4 h-4" />
                                        </Link>
                                        <button
                                            type="button"
                                            class="p-1.5 text-slate-400 dark:text-slate-500 hover:text-rose-600 dark:hover:text-rose-400 rounded-md hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                                            title="Desactivar o eliminar"
                                            @click="confirmDelete(product)"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="products.data.length === 0">
                                <td colspan="7" class="p-8">
                                    <EmptyState
                                        v-if="search || categoryId || brandId || warehouseId || stockStatus"
                                        title="No encontramos productos"
                                        description="Prueba cambiando los filtros o el término de búsqueda."
                                    />
                                    <EmptyState
                                        v-else
                                        title="No hay productos registrados"
                                        description="Agrega tu primer producto para comenzar."
                                    >
                                        <template #action>
                                            <Link :href="route('products.create')">
                                                <AppButton size="sm">
                                                    <Plus class="w-4 h-4" />
                                                    <span>Nuevo producto</span>
                                                </AppButton>
                                            </Link>
                                        </template>
                                    </EmptyState>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div class="p-4 border-t border-slate-100 dark:border-slate-800">
                    <AppPagination
                        :links="products.links"
                        :from="products.from"
                        :to="products.to"
                        :total="products.total"
                    />
                </div>
            </div>
        </div>

        <!-- Diálogo de confirmación para desactivar o eliminar -->
        <ConfirmDialog
            :show="deleteModalOpen"
            title="¿Desactivar este producto?"
            :message="`'${productToDelete?.name}' ya no podrá utilizarse en nuevos movimientos, pero conservarás su historial.`"
            confirm-text="Desactivar"
            cancel-text="Cancelar"
            :loading="deleteLoading"
            @confirm="executeDelete"
            @cancel="deleteModalOpen = false"
        />
    </AuthenticatedLayout>
</template>
