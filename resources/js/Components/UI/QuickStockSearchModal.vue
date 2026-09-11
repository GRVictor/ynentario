<script setup lang="ts">
import { ref, watch, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';
import {
    Search,
    X,
    Boxes,
    Warehouse,
    ArrowDownLeft,
    ArrowUpRight,
    ExternalLink,
    AlertCircle,
    Loader2,
} from 'lucide-vue-next';
import axios from 'axios';

interface WarehouseStock {
    id: number;
    name: string;
    code: string;
    quantity: number;
}

interface SearchProduct {
    id: number;
    name: string;
    sku: string;
    barcode?: string;
    category: string;
    unit: string;
    selling_price: number;
    min_stock: number;
    total_stock: number;
    warehouses: WarehouseStock[];
}

const props = defineProps<{
    show: boolean;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const query = ref('');
const results = ref<SearchProduct[]>([]);
const loading = ref(false);
const searchInput = ref<HTMLInputElement | null>(null);
let searchTimeout: any = null;

const close = () => {
    emit('close');
};

const handleSearch = () => {
    clearTimeout(searchTimeout);
    if (!query.value.trim()) {
        results.value = [];
        loading.value = false;
        return;
    }

    loading.value = true;
    searchTimeout = setTimeout(async () => {
        try {
            const response = await axios.get(route('products.quick-search'), {
                params: { q: query.value.trim() },
            });
            results.value = response.data;
        } catch {
            results.value = [];
        } finally {
            loading.value = false;
        }
    }, 250);
};

watch(
    () => props.show,
    (val) => {
        if (val) {
            query.value = '';
            results.value = [];
            nextTick(() => {
                setTimeout(() => searchInput.value?.focus(), 50);
            });
        }
    }
);

const goToEntry = (productId: number) => {
    close();
    router.visit(route('movements.entry.create'), {
        data: { product_id: productId },
    });
};

const goToExit = (productId: number) => {
    close();
    router.visit(route('movements.exit.create'), {
        data: { product_id: productId },
    });
};

const goToShow = (productId: number) => {
    close();
    router.visit(route('products.show', productId));
};

const formatCurrency = (val: number) => {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(val);
};
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0 flex items-start justify-center pt-12 sm:pt-16"
        role="dialog"
        aria-modal="true"
    >
        <!-- Fondo oscuro desenfocado -->
        <div
            class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs transition-opacity"
            @click="close"
        ></div>

        <!-- Tarjeta contenedora del modal -->
        <div
            class="relative w-full max-w-2xl bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden z-10 transition-all flex flex-col max-h-[85vh]"
        >
            <!-- Encabezado superior de búsqueda -->
            <div class="p-4 sm:p-5 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-850/50 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                    <Search class="w-5 h-5" />
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                        <label for="quick-search-input" class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider">
                            Buscar existencias
                        </label>
                        <span class="text-[11px] text-slate-600 dark:text-slate-400 hidden sm:inline">Presiona <kbd class="px-1.5 py-0.5 rounded bg-slate-200 dark:bg-slate-800 text-[10px] font-mono">Esc</kbd> para cerrar</span>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                        Busca por nombre, SKU o código de barras.
                    </p>
                </div>
                <button
                    type="button"
                    @click="close"
                    class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                >
                    <X class="w-5 h-5" />
                </button>
            </div>

            <!-- Campo principal de búsqueda -->
            <div class="p-4 sm:px-6 border-b border-slate-100 dark:border-slate-800 relative bg-white dark:bg-slate-900">
                <div class="relative flex items-center">
                    <Search class="absolute left-4 w-5 h-5 text-indigo-500 pointer-events-none" />
                    <input
                        id="quick-search-input"
                        ref="searchInput"
                        v-model="query"
                        @input="handleSearch"
                        @keydown.esc="close"
                        type="text"
                        placeholder="Escribe el nombre o SKU del producto..."
                        class="w-full pl-12 pr-10 py-3.5 text-base sm:text-lg font-medium bg-slate-50 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all shadow-inner"
                    />
                    <div v-if="loading" class="absolute right-4 text-indigo-500 animate-spin">
                        <Loader2 class="w-5 h-5" />
                    </div>
                </div>
            </div>

            <!-- Área de contenido y lista de resultados -->
            <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-3">
                <!-- Estado 1: Invitación a buscar -->
                <div v-if="!query.trim()" class="text-center py-8 px-4">
                    <div class="w-16 h-16 mx-auto rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mb-3">
                        <Boxes class="w-8 h-8" />
                    </div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">
                        Busca un producto
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 max-w-md mx-auto mt-1.5 leading-relaxed">
                        Escribe el nombre o código para ver cuántas existencias hay en cada almacén.
                    </p>
                </div>

                <!-- Estado 2: Sin resultados encontrados -->
                <div v-else-if="!loading && results.length === 0" class="text-center py-8 px-4">
                    <div class="w-16 h-16 mx-auto rounded-2xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center mb-3">
                        <AlertCircle class="w-8 h-8" />
                    </div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">
                        No encontramos productos con "{{ query }}"
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 max-w-md mx-auto mt-1.5">
                        Revisa que esté bien escrito o prueba con otra palabra.
                    </p>
                </div>

                <!-- Estado 3: Lista de productos encontrados -->
                <div v-else class="space-y-3">
                    <div
                        v-for="p in results"
                        :key="p.id"
                        class="p-4 rounded-xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-slate-850 hover:border-indigo-400 dark:hover:border-indigo-500 transition-all shadow-xs"
                    >
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded-md bg-indigo-50 dark:bg-indigo-950/80 text-indigo-700 dark:text-indigo-300 font-mono text-xs font-bold">
                                        {{ p.sku }}
                                    </span>
                                    <span class="text-xs text-slate-600 dark:text-slate-300 font-medium">
                                        {{ p.category }}
                                    </span>
                                </div>
                                <h4 class="text-base font-bold text-slate-900 dark:text-white mt-1">
                                    {{ p.name }}
                                </h4>
                                <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                    Precio: <strong class="text-slate-800 dark:text-slate-200">{{ formatCurrency(p.selling_price) }}</strong>
                                </div>
                            </div>

                            <!-- Indicador destacado de existencias totales -->
                            <div class="sm:text-right flex sm:flex-col items-center sm:items-end justify-between gap-1 p-2.5 sm:p-0 bg-slate-50 dark:bg-slate-800/50 sm:bg-transparent rounded-lg">
                                <span class="text-[11px] uppercase font-bold text-slate-600 dark:text-slate-300">
                                    Existencias totales:
                                </span>
                                <div class="flex items-center gap-1.5">
                                    <span
                                        :class="[
                                            'text-2xl font-black font-mono',
                                            p.total_stock <= 0
                                                ? 'text-rose-600 dark:text-rose-400'
                                                : (p.total_stock <= p.min_stock
                                                    ? 'text-amber-600 dark:text-amber-400'
                                                    : 'text-emerald-600 dark:text-emerald-400')
                                        ]"
                                    >
                                        {{ p.total_stock }}
                                    </span>
                                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">
                                        {{ p.unit }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Desglose de existencias por almacén -->
                        <div class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800">
                            <span class="text-[11px] font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider block mb-1.5">
                                Existencias por almacén
                            </span>
                            <div v-if="p.warehouses && p.warehouses.length > 0" class="flex flex-wrap gap-2">
                                <div
                                    v-for="w in p.warehouses"
                                    :key="w.id"
                                    class="inline-flex items-center gap-2 px-2.5 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-xs text-slate-700 dark:text-slate-300 border border-slate-200/60 dark:border-slate-700"
                                >
                                    <Warehouse class="w-3.5 h-3.5 text-indigo-500 shrink-0" />
                                    <span class="font-medium">{{ w.name }}:</span>
                                    <strong class="font-mono text-slate-900 dark:text-white font-bold">{{ w.quantity }} {{ p.unit }}</strong>
                                </div>
                            </div>
                            <div v-else class="text-xs text-slate-600 dark:text-slate-300 italic">
                                Sin existencias en almacenes.
                            </div>
                        </div>

                        <!-- Acciones rápidas de un clic -->
                        <div class="mt-3 pt-3 border-t border-slate-100 dark:border-slate-800 flex flex-wrap items-center justify-end gap-2">
                            <button
                                type="button"
                                @click="goToEntry(p.id)"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 transition-colors"
                            >
                                <ArrowDownLeft class="w-3.5 h-3.5" />
                                <span>+ Entrada</span>
                            </button>
                            <button
                                type="button"
                                @click="goToExit(p.id)"
                                :disabled="p.total_stock <= 0"
                                :class="[
                                    'inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors',
                                    p.total_stock <= 0
                                        ? 'opacity-50 cursor-not-allowed bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'
                                        : 'bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 hover:bg-rose-100 dark:hover:bg-rose-900/60'
                                ]"
                            >
                                <ArrowUpRight class="w-3.5 h-3.5" />
                                <span>- Salida</span>
                            </button>
                            <button
                                type="button"
                                @click="goToShow(p.id)"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors"
                            >
                                <ExternalLink class="w-3.5 h-3.5" />
                                <span>Ver detalle</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Consejo y atajo en pie de modal -->
            <div class="p-3 px-6 bg-slate-50 dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 text-center text-xs text-slate-500 dark:text-slate-400">
                Puedes abrir este buscador en cualquier momento con <kbd class="px-1.5 py-0.5 rounded bg-white dark:bg-slate-800 text-[10px] font-mono border border-slate-300 dark:border-slate-700">Ctrl + K</kbd>
            </div>
        </div>
    </div>
</template>
