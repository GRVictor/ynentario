<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppCard from '@/Components/UI/AppCard.vue';
import {
    Boxes,
    AlertTriangle,
    ArrowLeftRight,
    ClipboardList,
    Download,
    ArrowRight,
} from 'lucide-vue-next';
</script>

<template>
    <Head title="Reportes" />

    <AuthenticatedLayout title="Reportes">
        <div class="space-y-6 max-w-5xl">
            <p class="text-xs text-slate-500">
                Consulta el inventario y exporta la información en cualquier momento.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Reporte 1: Existencias Actuales -->
                <AppCard title="Existencias actuales">
                    <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed mb-4">
                        Cantidades disponibles por producto y almacén, con opción de exportar a CSV.
                    </p>
                    <div class="flex items-center justify-between pt-3 border-t border-slate-100 dark:border-slate-800">
                        <span class="text-[11px] text-slate-400 dark:text-slate-500">Por almacén y categoría</span>
                        <Link :href="route('reports.current-stock')" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 flex items-center gap-1">
                            <span>Ver reporte</span>
                            <ArrowRight class="w-3.5 h-3.5" />
                        </Link>
                    </div>
                </AppCard>

                <!-- Reporte 2: Stock Bajo -->
                <AppCard title="Stock bajo">
                    <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed mb-4">
                        Productos que alcanzaron o están por debajo de su cantidad mínima o punto de reorden.
                    </p>
                    <div class="flex items-center justify-between pt-3 border-t border-slate-100 dark:border-slate-800">
                        <span class="text-[11px] text-amber-600 dark:text-amber-400 font-medium">Alertas</span>
                        <Link :href="route('reports.low-stock')" class="text-xs font-semibold text-amber-600 dark:text-amber-400 hover:text-amber-800 dark:hover:text-amber-300 flex items-center gap-1">
                            <span>Ver reporte</span>
                            <ArrowRight class="w-3.5 h-3.5" />
                        </Link>
                    </div>
                </AppCard>

                <!-- Reporte 3: Movimientos -->
                <AppCard title="Historial de movimientos">
                    <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed mb-4">
                        Registro de todas las entradas, salidas, transferencias y ajustes realizados.
                    </p>
                    <div class="flex items-center justify-between pt-3 border-t border-slate-100 dark:border-slate-800">
                        <span class="text-[11px] text-slate-400 dark:text-slate-500">Con folios y usuarios</span>
                        <Link :href="route('movements.index')" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 flex items-center gap-1">
                            <span>Ver movimientos</span>
                            <ArrowRight class="w-3.5 h-3.5" />
                        </Link>
                    </div>
                </AppCard>

                <!-- Reporte 4: Kardex -->
                <AppCard title="Kardex">
                    <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed mb-4">
                        Historial detallado de entradas, salidas y saldos de un producto específico.
                    </p>
                    <div class="flex items-center justify-between pt-3 border-t border-slate-100 dark:border-slate-800">
                        <span class="text-[11px] text-slate-400 dark:text-slate-500">Por almacén</span>
                        <Link :href="route('kardex.index')" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 flex items-center gap-1">
                            <span>Ver kardex</span>
                            <ArrowRight class="w-3.5 h-3.5" />
                        </Link>
                    </div>
                </AppCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
