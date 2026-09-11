<script setup lang="ts">
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import {
    Boxes,
    BarChart3,
    ArrowLeftRight,
    ClipboardList,
    Users,
    CheckCircle2,
    Sparkles,
    ChevronLeft,
    ChevronRight,
    ShieldCheck,
    Eye,
    TrendingUp,
    FileSpreadsheet,
    PackagePlus,
    X,
} from 'lucide-vue-next';
import AppButton from './AppButton.vue';

interface Props {
    show: boolean;
    userRole?: string;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    (e: 'close'): void;
}>();

const page = usePage<any>();
const currentSlide = ref(0);
const dontShowAgain = ref(true);

const userId = computed(() => page.props.auth?.user?.id || 'guest');
const role = computed(() => {
    const roles = page.props.auth?.user?.roles || [];
    if (roles.includes('Administrador') || roles.includes('admin')) return 'Administrador';
    if (roles.includes('Supervisor') || roles.includes('supervisor')) return 'Supervisor';
    if (roles.includes('Operador') || roles.includes('operador')) return 'Operador';
    return 'Consulta';
});

interface Slide {
    tag: string;
    title: string;
    description: string;
    icon: any;
    color: string;
    features: string[];
    actionLabel?: string;
    actionRoute?: string;
}

const slidesByRole = computed<Record<string, Slide[]>>(() => ({
    Administrador: [
        {
            tag: 'Resumen',
            title: 'Estado del inventario',
            description:
                'Consulta el valor total del inventario, el número de almacenes y los productos que están por agotarse.',
            icon: BarChart3,
            color: 'indigo',
            features: [
                'Valor total del inventario y productos registrados.',
                'Alertas de productos con stock bajo o agotado.',
                'Historial de los últimos movimientos realizados.',
            ],
            actionLabel: 'Ver inicio',
            actionRoute: 'dashboard',
        },
        {
            tag: 'Usuarios',
            title: 'Cuentas y accesos',
            description:
                'Crea cuentas para tu equipo y asigna los roles según las actividades que realiza cada persona.',
            icon: Users,
            color: 'violet',
            features: [
                'Roles de acceso: Administrador, Supervisor, Operador y Consulta.',
                'Gestión de almacenes y sucursales.',
                'Catálogos de categorías, marcas y unidades de medida.',
            ],
            actionLabel: 'Ver usuarios',
            actionRoute: 'users.index',
        },
        {
            tag: 'Reportes',
            title: 'Descarga y exportación',
            description:
                'Descarga reportes de existencias en formato CSV para abrirlos en hojas de cálculo o respaldar información.',
            icon: FileSpreadsheet,
            color: 'emerald',
            features: [
                'Reportes de existencias actuales y stock bajo.',
                'Importación de productos desde archivos CSV.',
                'Historial detallado de movimientos por producto.',
            ],
            actionLabel: 'Ver reportes',
            actionRoute: 'reports.index',
        },
    ],
    Supervisor: [
        {
            tag: 'Existencias',
            title: 'Control de productos',
            description:
                'Revisa las existencias en cada almacén y detecta a tiempo los productos que necesitan resurtirse.',
            icon: Boxes,
            color: 'indigo',
            features: [
                'Búsqueda por nombre, código o categoría.',
                'Alertas cuando el stock esté por debajo del mínimo.',
                'Existencias detalladas por cada almacén.',
            ],
            actionLabel: 'Ver productos',
            actionRoute: 'products.index',
        },
        {
            tag: 'Historial',
            title: 'Kardex de movimientos',
            description:
                'Revisa el historial de entradas, salidas, transferencias y ajustes de cualquier producto con sus saldos al día.',
            icon: ClipboardList,
            color: 'sky',
            features: [
                'Seguimiento de entradas, salidas y transferencias.',
                'Motivo y usuario responsable de cada movimiento.',
                'Cálculo automático de existencias tras cada operación.',
            ],
            actionLabel: 'Ver kardex',
            actionRoute: 'kardex.index',
        },
        {
            tag: 'Ajustes',
            title: 'Ajustes y reportes',
            description:
                'Registra ajustes cuando el conteo físico no coincida con el sistema y exporta reportes de existencias.',
            icon: FileSpreadsheet,
            color: 'emerald',
            features: [
                'Ajustes de inventario por conteo físico o mermas.',
                'Reportes de existencias listos para descargar.',
                'Carga de productos mediante archivos CSV.',
            ],
            actionLabel: 'Ver reportes',
            actionRoute: 'reports.index',
        },
    ],
    Operador: [
        {
            tag: 'Operación',
            title: 'Entradas y salidas',
            description:
                'Registra la recepción de mercancía y la salida de productos en pocos pasos con validación de existencias.',
            icon: ArrowLeftRight,
            color: 'indigo',
            features: [
                'Folio automático generado para cada movimiento.',
                'Validación para evitar salidas mayores a lo disponible.',
                'Búsqueda rápida por nombre o código.',
            ],
            actionLabel: 'Registrar movimiento',
            actionRoute: 'movements.index',
        },
        {
            tag: 'Transferencias',
            title: 'Mover mercancía entre almacenes',
            description:
                'Transfiere productos de un almacén a otro. El sistema descuenta del origen y suma al destino en una sola acción.',
            icon: TrendingUp,
            color: 'amber',
            features: [
                'Selección directa de almacén de origen y destino.',
                'Folio de seguimiento para cada transferencia.',
                'Actualización inmediata de existencias.',
            ],
            actionLabel: 'Ver movimientos',
            actionRoute: 'movements.index',
        },
        {
            tag: 'Consulta',
            title: 'Búsqueda rápida de existencias',
            description:
                'Busca cualquier producto para saber cuántas piezas hay y en qué almacén se encuentran ubicadas.',
            icon: Boxes,
            color: 'emerald',
            features: [
                'Búsqueda rápida con Ctrl + K desde cualquier pantalla.',
                'Consulta de precios y existencias por almacén.',
                'Acceso directo al detalle del producto.',
            ],
            actionLabel: 'Ver productos',
            actionRoute: 'products.index',
        },
    ],
    Consulta: [
        {
            tag: 'Productos',
            title: 'Catálogo de productos',
            description:
                'Consulta los productos registrados, sus precios de venta y las existencias disponibles en cada almacén.',
            icon: Eye,
            color: 'indigo',
            features: [
                'Búsqueda por nombre, categoría y marca.',
                'Existencias detalladas por almacén.',
                'Vista de consulta protegida contra cambios accidentales.',
            ],
            actionLabel: 'Ver productos',
            actionRoute: 'products.index',
        },
        {
            tag: 'Movimientos',
            title: 'Historial de movimientos',
            description:
                'Revisa el flujo de entradas y salidas para dar seguimiento a las operaciones del inventario.',
            icon: ClipboardList,
            color: 'sky',
            features: [
                'Consulta de fechas, cantidades y tipo de movimiento.',
                'Historial de operaciones por producto.',
                'Información clara y actualizada en todo momento.',
            ],
            actionLabel: 'Ver movimientos',
            actionRoute: 'movements.index',
        },
        {
            tag: 'Reportes',
            title: 'Reportes de stock',
            description:
                'Descarga reportes de existencias en formato CSV para trabajar en hojas de cálculo o compartir información.',
            icon: FileSpreadsheet,
            color: 'emerald',
            features: [
                'Descarga directa compatible con Excel.',
                'Revisión rápida de productos disponibles.',
                'Modo claro y oscuro según tu preferencia.',
            ],
            actionLabel: 'Ver reportes',
            actionRoute: 'reports.index',
        },
    ],
}));

const currentRoleSlides = computed(() => {
    return slidesByRole.value[role.value] || slidesByRole.value.Consulta;
});

const slide = computed(() => currentRoleSlides.value[currentSlide.value]);

const isLastSlide = computed(() => currentSlide.value === currentRoleSlides.value.length - 1);

const nextSlide = () => {
    if (isLastSlide.value) {
        finishTour();
    } else {
        currentSlide.value++;
    }
};

const prevSlide = () => {
    if (currentSlide.value > 0) {
        currentSlide.value--;
    }
};

const finishTour = () => {
    if (dontShowAgain.value && typeof window !== 'undefined') {
        localStorage.setItem(`ynentario_welcome_dismissed_${userId.value}`, 'true');
    }
    emit('close');
};

const handleActionClick = (routeName?: string) => {
    finishTour();
    if (routeName && route().has(routeName)) {
        router.visit(route(routeName));
    }
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <!-- Fondo oscuro desenfocado -->
        <div class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs transition-opacity" @click="finishTour"></div>

        <div class="min-h-full flex items-center justify-center p-4">
            <div
                class="relative w-full max-w-2xl bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200/80 dark:border-slate-800 overflow-hidden transition-all"
            >
                <!-- Línea superior con color de marca -->
                <div class="h-1 bg-gradient-to-r from-brand-600 via-brand-500 to-indigo-600"></div>

                <!-- Encabezado decorativo superior -->
                <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 p-6 sm:p-7 text-white relative">
                    <button
                        type="button"
                        class="absolute top-4 right-4 text-white/70 hover:text-white p-1 rounded-lg hover:bg-white/10 transition-colors"
                        @click="finishTour"
                    >
                        <X class="w-5 h-5" />
                    </button>

                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-6 h-6 rounded-lg bg-gradient-to-br from-brand-500 to-brand-700 flex items-center justify-center text-white font-black text-xs shadow-xs">
                            Y
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-white/15 text-white backdrop-blur-xs">
                            <Sparkles class="w-3.5 h-3.5 text-amber-300" />
                            Guía rápida
                        </span>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-brand-500/20 text-rose-200 border border-brand-500/30">
                            <ShieldCheck class="w-3 h-3" />
                            Rol: {{ role }}
                        </span>
                    </div>

                    <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-white">
                        Bienvenido a Ynentario
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-300 mt-1 max-w-lg">
                        Conoce las funciones principales disponibles para tu rol de <strong>{{ role }}</strong>.
                    </p>
                </div>

                <!-- Contenido de la diapositiva actual -->
                <div class="p-6 sm:p-7">
                    <!-- Barra de progreso e indicador de pasos -->
                    <div class="flex items-center justify-between mb-5">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold uppercase tracking-wider text-brand-600 dark:text-brand-400">
                                {{ slide.tag }}
                            </span>
                            <span class="text-slate-300 dark:text-slate-700">•</span>
                            <span class="text-xs text-slate-500 dark:text-slate-400">
                                Paso {{ currentSlide + 1 }} de {{ currentRoleSlides.length }}
                            </span>
                        </div>

                        <!-- Indicador de puntos de navegación -->
                        <div class="flex items-center gap-1.5">
                            <button
                                v-for="(_, index) in currentRoleSlides"
                                :key="index"
                                type="button"
                                @click="currentSlide = index"
                                :class="[
                                    'h-2 rounded-full transition-all duration-200',
                                    currentSlide === index
                                        ? 'w-6 bg-brand-600 dark:bg-brand-500'
                                        : 'w-2 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300'
                                ]"
                                :title="`Ir al paso ${index + 1}`"
                            />
                        </div>
                    </div>

                    <!-- Cuerpo de la tarjeta informativa -->
                    <div class="flex flex-col sm:flex-row gap-5 items-start">
                        <!-- Contenedor del ícono -->
                        <div
                            class="w-14 h-14 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-900/60 flex items-center justify-center shrink-0 shadow-xs"
                        >
                            <component :is="slide.icon" class="w-7 h-7" />
                        </div>

                        <!-- Textos y puntos clave -->
                        <div class="flex-1 min-w-0">
                            <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white leading-snug">
                                {{ slide.title }}
                            </h3>
                            <p class="mt-1.5 text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                                {{ slide.description }}
                            </p>

                            <!-- Lista de características -->
                            <div class="mt-4 space-y-2">
                                <div
                                    v-for="(feat, idx) in slide.features"
                                    :key="idx"
                                    class="flex items-start gap-2 text-xs text-slate-700 dark:text-slate-300"
                                >
                                    <CheckCircle2 class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5" />
                                    <span>{{ feat }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Acceso directo opcional a la función -->
                    <div v-if="slide.actionLabel && slide.actionRoute" class="mt-5 pt-4 border-t border-slate-100 dark:border-slate-800 flex justify-end">
                        <button
                            type="button"
                            @click="handleActionClick(slide.actionRoute)"
                            class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 group"
                        >
                            <span>{{ slide.actionLabel }}</span>
                            <ChevronRight class="w-3.5 h-3.5 transition-transform group-hover:translate-x-0.5" />
                        </button>
                    </div>
                </div>

                <!-- Navegación inferior y botones de control -->
                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/60 border-t border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <label class="flex items-center gap-2 cursor-pointer select-none text-xs text-slate-500 dark:text-slate-400">
                        <input
                            type="checkbox"
                            v-model="dontShowAgain"
                            class="rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500 bg-white dark:bg-slate-900"
                        />
                        <span>No volver a mostrar automáticamente</span>
                    </label>

                    <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                        <AppButton
                            v-if="currentSlide > 0"
                            variant="outline"
                            size="sm"
                            @click="prevSlide"
                        >
                            <ChevronLeft class="w-4 h-4 mr-1" />
                            Anterior
                        </AppButton>

                        <button
                            v-if="!isLastSlide"
                            type="button"
                            @click="finishTour"
                            class="text-xs font-medium text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 px-3 py-2"
                        >
                            Omitir
                        </button>

                        <AppButton
                            variant="primary"
                            size="sm"
                            @click="nextSlide"
                        >
                            <span v-if="!isLastSlide">Siguiente</span>
                            <span v-else class="flex items-center gap-1">
                                <Sparkles class="w-3.5 h-3.5" />
                                Comenzar
                            </span>
                            <ChevronRight v-if="!isLastSlide" class="w-4 h-4 ml-1" />
                        </AppButton>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
