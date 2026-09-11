<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppBadge from '@/Components/UI/AppBadge.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import {
    Boxes,
    Warehouse,
    AlertTriangle,
    XCircle,
    ArrowUpRight,
    ArrowDownLeft,
    TrendingUp,
    Plus,
    ArrowLeftRight,
    SlidersHorizontal,
    Search,
    Sparkles,
    HelpCircle,
} from 'lucide-vue-next';
import QuickStockSearchModal from '@/Components/UI/QuickStockSearchModal.vue';
import { ref } from 'vue';

interface MetricData {
    activeProducts: number;
    activeWarehouses: number;
    lowStock: number;
    outOfStock: number;
    movementsToday: number;
    monthEntries: number;
    monthExits: number;
}

interface ChartData {
    labels: string[];
    entries: number[];
    exits: number[];
}

interface TopProduct {
    id: number;
    name: string;
    sku: string;
    quantity: number;
    movements: number;
}

interface RecentMovement {
    id: number;
    folio: string;
    type: 'entry' | 'exit' | 'adjustment' | 'transfer';
    type_label: string;
    product_name: string;
    product_sku: string;
    warehouse_name: string;
    quantity: number;
    user_name: string;
    created_at: string;
}

interface LowStockProduct {
    id: number;
    name: string;
    sku: string;
    stock: number;
    min_stock: number;
    status: 'low_stock' | 'out_of_stock';
}

interface Props {
    metrics: MetricData;
    chartData: ChartData;
    topProducts: TopProduct[];
    recentMovements: RecentMovement[];
    lowStockProducts: LowStockProduct[];
}

defineProps<Props>();

const showSearchModal = ref(false);

const getMovementVariant = (type: string) => {
    switch (type) {
        case 'entry': return 'green';
        case 'exit': return 'red';
        case 'transfer': return 'blue';
        case 'adjustment': return 'amber';
        default: return 'slate';
    }
};
</script>

<template>
    <Head title="Panel de Control" />

    <AuthenticatedLayout title="Panel de Control">
        <template #actions>
            <Link :href="route('movements.entry.create')">
                <AppButton size="sm" variant="outline" class="hidden sm:inline-flex">
                    <ArrowDownLeft class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                    <span>Entrada</span>
                </AppButton>
            </Link>
            <Link :href="route('movements.exit.create')">
                <AppButton size="sm" variant="outline" class="hidden sm:inline-flex">
                    <ArrowUpRight class="w-4 h-4 text-rose-600 dark:text-rose-400" />
                    <span>Salida</span>
                </AppButton>
            </Link>
            <Link :href="route('products.create')">
                <AppButton size="sm">
                    <Plus class="w-4 h-4" />
                    <span>Nuevo Producto</span>
                </AppButton>
            </Link>
        </template>

        <div class="space-y-6">
            <!-- Banner de bienvenida -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 p-5 sm:p-6 text-white shadow-md border border-slate-800">
                <!-- Línea superior con color de marca -->
                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-brand-600 via-brand-500 to-indigo-600"></div>

                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-brand-500/20 text-xs font-semibold backdrop-blur-xs mb-2 text-rose-100 border border-brand-500/30">
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-400"></span>
                            <span>Ynentario • Panel principal</span>
                        </div>
                        <h2 class="text-xl sm:text-2xl font-black tracking-tight">
                            ¿Qué necesitas hacer hoy?
                        </h2>
                        <p class="text-xs sm:text-sm text-slate-300 mt-1 max-w-xl">
                            Selecciona una acción o busca existencias de cualquier producto.
                        </p>
                    </div>

                    <!-- Botón destacado de búsqueda rápida -->
                    <button
                        type="button"
                        @click="showSearchModal = true"
                        class="flex items-center gap-3 px-4 py-3 rounded-xl bg-white/10 hover:bg-white/20 backdrop-blur-xs border border-white/15 text-left transition-all text-xs font-medium text-white shadow-inner max-w-md w-full md:w-auto shrink-0"
                    >
                        <Search class="w-4 h-4 text-brand-300 shrink-0" />
                        <span class="truncate">Buscar existencias...</span>
                        <span class="hidden sm:inline-block px-1.5 py-0.5 rounded bg-white/20 text-[10px] font-mono ml-auto">Ctrl K</span>
                    </button>
                </div>

                <!-- Círculo decorativo difuminado -->
                <div class="absolute -right-10 -bottom-10 w-48 h-48 rounded-full bg-brand-600/10 pointer-events-none blur-2xl"></div>
            </div>

            <!-- Centro de Acciones Rápidas -->
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                        Acciones rápidas
                    </h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Acción 1: Entrada -->
                    <Link
                        :href="route('movements.entry.create')"
                        class="group p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 hover:border-emerald-500 dark:hover:border-emerald-500 shadow-xs hover:shadow-md transition-all flex flex-col justify-between"
                    >
                        <div>
                            <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mb-3 group-hover:scale-105 transition-transform">
                                <ArrowDownLeft class="w-6 h-6" />
                            </div>
                            <div class="flex items-center gap-1.5">
                                 <h4 class="text-base font-bold text-slate-900 dark:text-white">
                                     Entrada de mercancía
                                 </h4>
                                 <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-400">Entrada</span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed">
                                Registra entradas por compras o devoluciones para aumentar existencias.
                            </p>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs font-bold text-emerald-600 dark:text-emerald-400">
                            <span>Registrar entrada</span>
                            <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
                        </div>
                    </Link>

                    <!-- Acción 2: Salida -->
                    <Link
                        :href="route('movements.exit.create')"
                        class="group p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 hover:border-rose-500 dark:hover:border-rose-500 shadow-xs hover:shadow-md transition-all flex flex-col justify-between"
                    >
                        <div>
                            <div class="w-12 h-12 rounded-xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center mb-3 group-hover:scale-105 transition-transform">
                                <ArrowUpRight class="w-6 h-6" />
                            </div>
                            <div class="flex items-center gap-1.5">
                                <h4 class="text-base font-bold text-slate-900 dark:text-white">
                                     Salida de mercancía
                                </h4>
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-400">Salida</span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed">
                                Registra salidas por ventas o entregas para descontar existencias.
                            </p>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs font-bold text-rose-600 dark:text-rose-400">
                            <span>Registrar salida</span>
                            <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
                        </div>
                    </Link>

                    <!-- Acción 3: Traspaso -->
                    <Link
                        :href="route('movements.transfer.create')"
                        class="group p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 hover:border-sky-500 dark:hover:border-sky-500 shadow-xs hover:shadow-md transition-all flex flex-col justify-between"
                    >
                        <div>
                            <div class="w-12 h-12 rounded-xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center mb-3 group-hover:scale-105 transition-transform">
                                <ArrowLeftRight class="w-6 h-6" />
                            </div>
                            <div class="flex items-center gap-1.5">
                                <h4 class="text-base font-bold text-slate-900 dark:text-white">
                                     Transferir mercancía
                                </h4>
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-sky-100 dark:bg-sky-950 text-sky-700 dark:text-sky-400">Traspaso</span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed">
                                Mueve productos entre almacenes manteniendo tus existencias al día.
                            </p>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs font-bold text-sky-600 dark:text-sky-400">
                            <span>Transferir mercancía</span>
                            <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
                        </div>
                    </Link>

                    <!-- Acción 4: Ajuste / Conteo -->
                    <Link
                        :href="route('movements.adjustment.create')"
                        class="group p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 hover:border-amber-500 dark:hover:border-amber-500 shadow-xs hover:shadow-md transition-all flex flex-col justify-between"
                    >
                        <div>
                            <div class="w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center mb-3 group-hover:scale-105 transition-transform">
                                <SlidersHorizontal class="w-6 h-6" />
                            </div>
                            <div class="flex items-center gap-1.5">
                                <h4 class="text-base font-bold text-slate-900 dark:text-white">
                                     Ajustar existencias
                                </h4>
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-400">Ajuste</span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed">
                                Corrige diferencias entre el conteo físico y el sistema.
                            </p>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs font-bold text-amber-600 dark:text-amber-400">
                            <span>Ajustar inventario</span>
                            <span class="group-hover:translate-x-1 transition-transform">&rarr;</span>
                        </div>
                    </Link>
                </div>
            </div>

            <!-- Cuadrícula de tarjetas métricas -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Tarjeta 1: Productos activos -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs flex items-center justify-between transition-colors">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Productos activos</p>
                        <h4 class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ metrics.activeProducts }}</h4>
                        <Link :href="route('products.index')" class="text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 mt-2 inline-block">
                            Ver catálogo &rarr;
                        </Link>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                        <Boxes class="w-6 h-6" />
                    </div>
                </div>

                <!-- Tarjeta 2: Stock bajo -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs flex items-center justify-between transition-colors">
                    <div>
                        <p class="text-xs font-semibold text-amber-600 dark:text-amber-400 uppercase tracking-wider">Stock bajo</p>
                        <h4 class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ metrics.lowStock }}</h4>
                        <Link :href="route('reports.low-stock')" class="text-xs font-medium text-amber-600 dark:text-amber-400 hover:text-amber-700 mt-2 inline-block">
                            Revisar alertas &rarr;
                        </Link>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                        <AlertTriangle class="w-6 h-6" />
                    </div>
                </div>

                <!-- Tarjeta 3: Sin existencias -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs flex items-center justify-between transition-colors">
                    <div>
                        <p class="text-xs font-semibold text-rose-600 dark:text-rose-400 uppercase tracking-wider">Sin existencias</p>
                        <h4 class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ metrics.outOfStock }}</h4>
                        <span class="text-xs text-slate-500 dark:text-slate-400 mt-2 inline-block">Requieren resurtido</span>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                        <XCircle class="w-6 h-6" />
                    </div>
                </div>

                <!-- Tarjeta 4: Almacenes -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs flex items-center justify-between transition-colors">
                    <div>
                        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Almacenes</p>
                        <h4 class="text-2xl font-bold text-slate-900 dark:text-white mt-1">{{ metrics.activeWarehouses }}</h4>
                        <Link :href="route('warehouses.index')" class="text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 mt-2 inline-block">
                            Ver almacenes &rarr;
                        </Link>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 flex items-center justify-center">
                        <Warehouse class="w-6 h-6" />
                    </div>
                </div>
            </div>

            <!-- Barra secundaria de métricas -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 px-5 py-4 flex items-center gap-4 transition-colors">
                    <div class="w-10 h-10 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                        <ArrowLeftRight class="w-5 h-5" />
                    </div>
                    <div>
                        <span class="text-xs text-slate-500 dark:text-slate-400">Movimientos Hoy</span>
                        <p class="text-lg font-bold text-slate-900 dark:text-white">{{ metrics.movementsToday }}</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 px-5 py-4 flex items-center gap-4 transition-colors">
                    <div class="w-10 h-10 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                        <ArrowDownLeft class="w-5 h-5" />
                    </div>
                    <div>
                        <span class="text-xs text-slate-500 dark:text-slate-400">Entradas del Mes</span>
                        <p class="text-lg font-bold text-emerald-700 dark:text-emerald-400">+{{ metrics.monthEntries }}</p>
                    </div>
                </div>

                <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 px-5 py-4 flex items-center gap-4 transition-colors">
                    <div class="w-10 h-10 rounded-lg bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                        <ArrowUpRight class="w-5 h-5" />
                    </div>
                    <div>
                        <span class="text-xs text-slate-500 dark:text-slate-400">Salidas del Mes</span>
                        <p class="text-lg font-bold text-rose-700 dark:text-rose-400">-{{ metrics.monthExits }}</p>
                    </div>
                </div>
            </div>

            <!-- Sección de gráficas y movimientos -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Gráfica de barras de actividad (7 días) -->
                <div class="lg:col-span-2 bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs transition-colors">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Actividad de Movimientos (Últimos 7 días)</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Entradas y salidas de los últimos 7 días</p>
                        </div>
                        <div class="flex items-center gap-4 text-xs">
                            <span class="inline-flex items-center gap-1.5 font-medium text-slate-600 dark:text-slate-400">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Entradas
                            </span>
                            <span class="inline-flex items-center gap-1.5 font-medium text-slate-600 dark:text-slate-400">
                                <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span> Salidas
                            </span>
                        </div>
                    </div>

                    <!-- Gráfica visual limpia de barras -->
                    <div class="h-48 flex items-end justify-between gap-3 pt-4 border-b border-slate-100 dark:border-slate-800">
                        <div
                            v-for="(label, idx) in chartData.labels"
                            :key="idx"
                            class="flex-1 flex flex-col items-center gap-1 h-full justify-end"
                        >
                            <div class="w-full flex items-end justify-center gap-1 h-36">
                                <!-- Barra de entradas -->
                                <div
                                    class="w-3.5 bg-emerald-500 rounded-t-xs transition-all hover:opacity-80 relative group"
                                    :style="{ height: `${Math.min(100, Math.max(8, (chartData.entries[idx] || 0) * 2))}%` }"
                                >
                                    <div class="absolute -top-7 left-1/2 -translate-x-1/2 hidden group-hover:block bg-slate-900 dark:bg-slate-800 text-white text-[10px] px-1.5 py-0.5 rounded shadow-sm z-10 whitespace-nowrap">
                                        +{{ chartData.entries[idx] }} unid.
                                    </div>
                                </div>
                                <!-- Barra de salidas -->
                                <div
                                    class="w-3.5 bg-rose-500 rounded-t-xs transition-all hover:opacity-80 relative group"
                                    :style="{ height: `${Math.min(100, Math.max(8, (chartData.exits[idx] || 0) * 2))}%` }"
                                >
                                    <div class="absolute -top-7 left-1/2 -translate-x-1/2 hidden group-hover:block bg-slate-900 dark:bg-slate-800 text-white text-[10px] px-1.5 py-0.5 rounded shadow-sm z-10 whitespace-nowrap">
                                        -{{ chartData.exits[idx] }} unid.
                                    </div>
                                </div>
                            </div>
                            <span class="text-[10px] font-medium text-slate-400 dark:text-slate-500 mt-1">{{ label }}</span>
                        </div>
                    </div>
                </div>

                <!-- 5 productos con mayor movimiento -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs flex flex-col transition-colors">
                    <div class="flex items-center gap-2 mb-3">
                        <TrendingUp class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Mayor movimiento</h3>
                    </div>

                    <div v-if="topProducts.length > 0" class="space-y-3 flex-1">
                        <div
                            v-for="(prod, i) in topProducts"
                            :key="prod.id"
                            class="flex items-center justify-between text-xs pb-2 border-b border-slate-100 dark:border-slate-800 last:border-0"
                        >
                            <div class="min-w-0 pr-2">
                                <p class="font-semibold text-slate-800 dark:text-slate-200 truncate">{{ prod.name }}</p>
                                <span class="text-[10px] text-slate-400 dark:text-slate-500">{{ prod.sku }}</span>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="font-bold text-slate-900 dark:text-white">{{ prod.quantity }}</span>
                                <span class="text-[10px] text-slate-400 dark:text-slate-500 block">{{ prod.movements }} movs</span>
                            </div>
                        </div>
                    </div>
                    <EmptyState
                        v-else
                        title="Sin movimientos"
                        description="Aún no hay movimientos registrados."
                    />
                </div>
            </div>

            <!-- Dos columnas: Alertas de stock bajo y Movimientos recientes -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Alertas críticas de stock bajo -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs transition-colors">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <AlertTriangle class="w-4 h-4 text-amber-500" />
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Stock bajo</h3>
                        </div>
                        <Link :href="route('reports.low-stock')" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300">
                            Ver todas &rarr;
                        </Link>
                    </div>

                    <div v-if="lowStockProducts.length > 0" class="divide-y divide-slate-100 dark:divide-slate-800">
                        <div
                            v-for="prod in lowStockProducts"
                            :key="prod.id"
                            class="py-3 flex items-center justify-between"
                        >
                            <div class="min-w-0 pr-3">
                                <Link :href="route('products.show', prod.id)" class="text-xs font-semibold text-slate-800 dark:text-slate-200 hover:text-indigo-600 dark:hover:text-indigo-400 truncate block">
                                    {{ prod.name }}
                                </Link>
                                <span class="text-[10px] text-slate-400 dark:text-slate-500 font-mono">{{ prod.sku }}</span>
                            </div>
                            <div class="flex items-center gap-3 shrink-0">
                                <div class="text-right">
                                    <span class="text-xs font-bold text-slate-900 dark:text-white">{{ prod.stock }}</span>
                                    <span class="text-[10px] text-slate-400 dark:text-slate-500 block">Mín: {{ prod.min_stock }}</span>
                                </div>
                                <AppBadge :variant="prod.status === 'out_of_stock' ? 'red' : 'amber'" size="sm">
                                    {{ prod.status === 'out_of_stock' ? 'Agotado' : 'Stock bajo' }}
                                </AppBadge>
                            </div>
                        </div>
                    </div>
                    <EmptyState
                        v-else
                        title="Inventario en orden"
                        description="Todas las existencias se encuentran por encima del mínimo."
                    />
                </div>

                <!-- Movimientos recientes -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs transition-colors">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <ArrowLeftRight class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Movimientos recientes</h3>
                        </div>
                        <Link :href="route('movements.index')" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300">
                            Ver historial &rarr;
                        </Link>
                    </div>

                    <div v-if="recentMovements.length > 0" class="divide-y divide-slate-100 dark:divide-slate-800">
                        <div
                            v-for="m in recentMovements"
                            :key="m.id"
                            class="py-3 flex items-center justify-between text-xs"
                        >
                            <div class="min-w-0 pr-3">
                                <div class="flex items-center gap-2 mb-0.5">
                                    <span class="font-mono text-[11px] font-bold text-slate-700 dark:text-slate-300">{{ m.folio }}</span>
                                    <AppBadge :variant="getMovementVariant(m.type)" size="sm">
                                        {{ m.type_label }}
                                    </AppBadge>
                                </div>
                                <p class="text-slate-800 dark:text-slate-200 font-medium truncate">{{ m.product_name }}</p>
                                <span class="text-[10px] text-slate-400 dark:text-slate-500">{{ m.warehouse_name }} &bull; {{ m.created_at }}</span>
                            </div>
                            <div class="text-right shrink-0">
                                <span
                                    :class="[
                                        'font-bold text-xs',
                                        m.type === 'entry' && 'text-emerald-600 dark:text-emerald-400',
                                        m.type === 'exit' && 'text-rose-600 dark:text-rose-400',
                                        m.type === 'transfer' && 'text-sky-600 dark:text-sky-400',
                                        m.type === 'adjustment' && 'text-amber-600 dark:text-amber-400',
                                    ]"
                                >
                                    {{ m.type === 'entry' ? '+' : (m.type === 'exit' ? '-' : '') }}{{ m.quantity }}
                                </span>
                                <span class="text-[10px] text-slate-400 dark:text-slate-500 block">{{ m.user_name }}</span>
                            </div>
                        </div>
                    </div>
                    <EmptyState
                        v-else
                        title="Sin movimientos"
                        description="Aún no hay movimientos registrados."
                    />
                </div>
            </div>
        </div>

        <QuickStockSearchModal
            :show="showSearchModal"
            @close="showSearchModal = false"
        />
    </AuthenticatedLayout>
</template>
