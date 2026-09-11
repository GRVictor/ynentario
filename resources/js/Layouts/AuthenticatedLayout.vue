<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    LayoutDashboard,
    Boxes,
    Tag,
    ArrowLeftRight,
    ClipboardList,
    Warehouse,
    BarChart3,
    Users,
    User,
    LogOut,
    Menu,
    X,
    CheckCircle2,
    AlertCircle,
    Info,
    Sun,
    Moon,
    Settings,
    HelpCircle,
    Search,
} from 'lucide-vue-next';
import { useAppearance } from '@/composables/useAppearance';
import SettingsModal from '@/Components/UI/SettingsModal.vue';
import WelcomeCarouselModal from '@/Components/UI/WelcomeCarouselModal.vue';
import QuickStockSearchModal from '@/Components/UI/QuickStockSearchModal.vue';

interface Props {
    title?: string;
    breadcrumbs?: { label: string; href?: string }[];
}

defineProps<Props>();

const page = usePage<any>();
const mobileMenuOpen = ref(false);
const showSettingsModal = ref(false);
const showWelcomeModal = ref(false);
const showQuickSearch = ref(false);

const { isDark, toggleDark, initAppearance } = useAppearance();

const user = computed(() => page.props.auth.user);
const permissions = computed<string[]>(() => page.props.auth.user?.permissions || []);
const isSuperAdmin = computed(() => page.props.auth.user?.roles?.includes('Administrador') || page.props.auth.user?.roles?.includes('admin'));

const can = (permission: string): boolean => {
    if (isSuperAdmin.value) return true;
    return permissions.value.includes(permission);
};

const flash = computed(() => page.props.flash || {});
const showFlash = ref(true);

const handleGlobalKeyDown = (e: KeyboardEvent) => {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        showQuickSearch.value = true;
    }
};

onMounted(() => {
    initAppearance();
    window.addEventListener('keydown', handleGlobalKeyDown);

    // Verificar si el usuario ya descartó el recorrido de bienvenida
    if (user.value?.id && typeof window !== 'undefined') {
        const dismissed = localStorage.getItem(`ynentario_welcome_dismissed_${user.value.id}`);
        if (!dismissed) {
            showWelcomeModal.value = true;
        }
    }
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleGlobalKeyDown);
});

const navigation = computed(() => [
    {
        name: 'Inicio',
        href: route('dashboard'),
        icon: LayoutDashboard,
        active: route().current('dashboard'),
        show: true,
    },
    {
        name: 'Productos',
        href: route('products.index'),
        icon: Boxes,
        active: route().current('products.*'),
        show: can('products.view'),
    },
    {
        name: 'Catálogos',
        href: route('catalogs.index'),
        icon: Tag,
        active: route().current('catalogs.*'),
        show: can('products.view'),
    },
    {
        name: 'Movimientos',
        href: route('movements.index'),
        icon: ArrowLeftRight,
        active: route().current('movements.*'),
        show: can('inventory.view'),
    },
    {
        name: 'Kardex',
        href: route('kardex.index'),
        icon: ClipboardList,
        active: route().current('kardex.*'),
        show: can('inventory.view'),
    },
    {
        name: 'Almacenes',
        href: route('warehouses.index'),
        icon: Warehouse,
        active: route().current('warehouses.*'),
        show: can('warehouses.view'),
    },
    {
        name: 'Reportes',
        href: route('reports.index'),
        icon: BarChart3,
        active: route().current('reports.*'),
        show: can('reports.view'),
    },
    {
        name: 'Usuarios',
        href: route('users.index'),
        icon: Users,
        active: route().current('users.*'),
        show: can('users.view'),
    },
]);
</script>

<template>
    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 flex transition-colors duration-150">
        <!-- Barra lateral de escritorio -->
        <aside class="hidden lg:flex lg:flex-col lg:w-64 bg-slate-900 dark:bg-slate-950 border-r border-slate-800 shrink-0 sticky top-0 h-screen z-30">
            <!-- Marca y logotipo -->
            <div class="h-16 flex items-center px-6 border-b border-slate-800/80 gap-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-brand-500 via-brand-600 to-brand-700 flex items-center justify-center text-white font-black text-lg shadow-md shadow-brand-600/40 ring-1 ring-white/10">
                    Y
                </div>
                <div>
                    <div class="flex items-center gap-1.5">
                        <h1 class="text-white font-bold text-base tracking-tight leading-none">Ynentario</h1>
                        <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-brand-600/20 text-brand-300 border border-brand-600/30">v1.0</span>
                    </div>
                    <span class="text-[11px] text-slate-400 font-medium">Control de almacenes</span>
                </div>
            </div>

            <!-- Enlaces de navegación principal -->
            <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                <template v-for="item in navigation" :key="item.name">
                    <Link
                        v-if="item.show"
                        :href="item.href"
                        :class="[
                            'flex items-center gap-3 px-3 py-2.5 rounded-lg text-xs font-semibold transition-all group',
                            item.active
                                ? 'bg-indigo-600 text-white shadow-xs'
                                : 'text-slate-300 hover:bg-slate-800/80 hover:text-white',
                        ]"
                    >
                        <component
                            :is="item.icon"
                            :class="[
                                'w-4 h-4 shrink-0 transition-colors',
                                item.active ? 'text-white' : 'text-slate-400 group-hover:text-slate-200'
                            ]"
                        />
                        <span>{{ item.name }}</span>
                    </Link>
                </template>
            </nav>

            <!-- Perfil de usuario y accesos directos inferiores -->
            <div class="p-3 border-t border-slate-800 bg-slate-900/60 dark:bg-slate-950/80">
                <div class="flex items-center justify-between p-2 rounded-lg hover:bg-slate-800/60 transition-colors">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="w-8 h-8 rounded-full bg-slate-700 text-slate-200 flex items-center justify-center font-bold text-xs uppercase shrink-0">
                            {{ user?.name?.substring(0, 2) }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-white truncate">{{ user?.name }}</p>
                            <p class="text-[10px] text-slate-400 truncate">{{ user?.roles?.[0] || 'Usuario' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-0.5">
                        <button
                            type="button"
                            @click="showSettingsModal = true"
                            class="p-1.5 text-slate-400 hover:text-white rounded-md hover:bg-slate-700/60 transition-colors"
                            title="Ajustes"
                        >
                            <Settings class="w-4 h-4" />
                        </button>
                        <Link
                            :href="route('profile.edit')"
                            class="p-1.5 text-slate-400 hover:text-white rounded-md hover:bg-slate-700/60 transition-colors"
                            title="Perfil"
                        >
                            <User class="w-4 h-4" />
                        </Link>
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="p-1.5 text-slate-400 hover:text-rose-400 rounded-md hover:bg-slate-700/60 transition-colors"
                            title="Cerrar sesión"
                        >
                            <LogOut class="w-4 h-4" />
                        </Link>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Menú lateral móvil -->
        <div v-if="mobileMenuOpen" class="fixed inset-0 z-50 lg:hidden flex">
            <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs" @click="mobileMenuOpen = false"></div>
            <div class="relative w-64 max-w-xs bg-slate-900 flex-1 flex flex-col h-full z-10 shadow-2xl">
                <div class="h-16 flex items-center justify-between px-6 border-b border-slate-800">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-brand-500 via-brand-600 to-brand-700 flex items-center justify-center text-white font-black text-base shadow-md shadow-brand-600/40 ring-1 ring-white/10">
                            Y
                        </div>
                        <div>
                            <span class="text-white font-bold text-base">Ynentario</span>
                            <span class="block text-[10px] text-slate-400">Control de almacenes</span>
                        </div>
                    </div>
                    <button type="button" class="text-slate-400 hover:text-white" @click="mobileMenuOpen = false">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                    <template v-for="item in navigation" :key="item.name">
                        <Link
                            v-if="item.show"
                            :href="item.href"
                            @click="mobileMenuOpen = false"
                            :class="[
                                'flex items-center gap-3 px-3 py-2.5 rounded-lg text-xs font-semibold transition-colors',
                                item.active
                                    ? 'bg-indigo-600 text-white shadow-xs'
                                    : 'text-slate-300 hover:bg-slate-800/80 hover:text-white',
                            ]"
                        >
                            <component
                                :is="item.icon"
                                :class="[
                                    'w-4 h-4 shrink-0 transition-colors',
                                    item.active ? 'text-white' : 'text-slate-400',
                                ]"
                            />
                            <span>{{ item.name }}</span>
                        </Link>
                    </template>
                </nav>

                <!-- Perfil de usuario en menú móvil -->
                <div class="p-3 border-t border-slate-800 bg-slate-900/60">
                    <div class="flex items-center justify-between p-2 rounded-lg bg-slate-800/40">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-8 h-8 rounded-full bg-slate-700 text-slate-200 flex items-center justify-center font-bold text-xs uppercase shrink-0">
                                {{ user?.name?.substring(0, 2) }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-white truncate">{{ user?.name }}</p>
                                <p class="text-[10px] text-slate-400 truncate">{{ user?.roles?.[0] || 'Usuario' }}</p>
                            </div>
                        </div>
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="p-1.5 text-slate-400 hover:text-rose-400 rounded-md transition-colors"
                            title="Cerrar sesión"
                        >
                            <LogOut class="w-4 h-4" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Área de contenido principal -->
        <div class="flex-1 flex flex-col min-w-0 overflow-x-hidden">
            <!-- Línea superior con color de marca -->
            <div class="h-0.5 bg-gradient-to-r from-brand-600 via-brand-500 to-transparent"></div>

            <!-- Encabezado superior -->
            <header class="h-16 bg-white dark:bg-slate-900 border-b border-slate-200/80 dark:border-slate-800 sticky top-0 z-20 flex items-center justify-between px-4 sm:px-6 lg:px-8 transition-colors">
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        class="lg:hidden p-2 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800"
                        @click="mobileMenuOpen = true"
                    >
                        <Menu class="w-5 h-5" />
                    </button>

                    <!-- Migas de pan y título de página -->
                    <div>
                        <nav v-if="breadcrumbs && breadcrumbs.length > 0" class="flex items-center gap-1.5 text-[11px] text-slate-500 dark:text-slate-400 mb-0.5">
                            <Link :href="route('dashboard')" class="hover:text-slate-900 dark:hover:text-slate-200 transition-colors">Inicio</Link>
                            <template v-for="(crumb, idx) in breadcrumbs" :key="idx">
                                <span>/</span>
                                <Link v-if="crumb.href" :href="crumb.href" class="hover:text-slate-900 dark:hover:text-slate-200 transition-colors">
                                    {{ crumb.label }}
                                </Link>
                                <span v-else class="text-slate-800 dark:text-slate-200 font-medium">{{ crumb.label }}</span>
                            </template>
                        </nav>
                        <h2 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white leading-tight">
                            <slot name="title">{{ title }}</slot>
                        </h2>
                    </div>
                </div>

                <!-- Acciones derechas del encabezado (Búsqueda rápida, Tema, Guía, Ajustes) -->
                <div class="flex items-center gap-1.5 sm:gap-2.5">
                    <!-- Botón para disparar búsqueda rápida de existencias -->
                    <button
                        type="button"
                        @click="showQuickSearch = true"
                        class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-slate-700/80 transition-colors text-xs font-semibold border border-slate-200/80 dark:border-slate-700 shadow-2xs"
                        title="Buscar existencias (Ctrl + K)"
                    >
                        <Search class="w-3.5 h-3.5 text-indigo-500" />
                        <span class="hidden md:inline">Buscar existencias</span>
                        <kbd class="hidden lg:inline-flex px-1.5 py-0.5 rounded bg-white dark:bg-slate-900 text-[10px] font-mono text-slate-400 border border-slate-300 dark:border-slate-700">Ctrl K</kbd>
                    </button>

                    <!-- Botón de recorrido y guía de ayuda -->
                    <button
                        type="button"
                        @click="showWelcomeModal = true"
                        class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 text-xs font-semibold transition-colors"
                        title="Guía de uso"
                    >
                        <HelpCircle class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400" />
                        <span class="hidden sm:inline">Guía</span>
                    </button>

                    <!-- Botón rápido de cambio de tema claro/oscuro -->
                    <button
                        type="button"
                        @click="toggleDark"
                        class="p-2 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                        :title="isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
                    >
                        <Sun v-if="isDark" class="w-4 h-4 text-amber-400" />
                        <Moon v-else class="w-4 h-4 text-slate-600" />
                    </button>

                    <!-- Botón de ajustes -->
                    <button
                        type="button"
                        @click="showSettingsModal = true"
                        class="p-2 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                        title="Ajustes"
                    >
                        <Settings class="w-4 h-4" />
                    </button>

                    <!-- Ranura para acciones de página (ej. "Nuevo Producto", "Exportar", etc.) -->
                    <div v-if="$slots.actions" class="pl-2 border-l border-slate-200 dark:border-slate-800 flex items-center gap-2">
                        <slot name="actions" />
                    </div>
                </div>
            </header>

            <!-- Mensajes de alerta flash -->
            <div v-if="showFlash && (flash.success || flash.error || flash.info)" class="px-4 sm:px-6 lg:px-8 pt-4">
                <!-- Mensaje de éxito -->
                <div
                    v-if="flash.success"
                    class="rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200/80 dark:border-emerald-800 p-4 flex items-center justify-between gap-3 text-emerald-800 dark:text-emerald-300 shadow-xs"
                >
                    <div class="flex items-center gap-2.5">
                        <CheckCircle2 class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                        <span class="text-xs font-semibold">{{ flash.success }}</span>
                    </div>
                    <button type="button" @click="showFlash = false" class="text-emerald-500 hover:text-emerald-700 dark:hover:text-emerald-300">
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <!-- Mensaje de error -->
                <div
                    v-if="flash.error"
                    class="rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200/80 dark:border-rose-800 p-4 flex items-center justify-between gap-3 text-rose-800 dark:text-rose-300 shadow-xs"
                >
                    <div class="flex items-center gap-2.5">
                        <AlertCircle class="w-5 h-5 text-rose-600 dark:text-rose-400 shrink-0" />
                        <span class="text-xs font-semibold">{{ flash.error }}</span>
                    </div>
                    <button type="button" @click="showFlash = false" class="text-rose-500 hover:text-rose-700 dark:hover:text-rose-300">
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <!-- Mensaje informativo -->
                <div
                    v-if="flash.info"
                    class="rounded-xl bg-sky-50 dark:bg-sky-950/40 border border-sky-200/80 dark:border-sky-800 p-4 flex items-center justify-between gap-3 text-sky-800 dark:text-sky-300 shadow-xs"
                >
                    <div class="flex items-center gap-2.5">
                        <Info class="w-5 h-5 text-sky-600 dark:text-sky-400 shrink-0" />
                        <span class="text-xs font-semibold">{{ flash.info }}</span>
                    </div>
                    <button type="button" @click="showFlash = false" class="text-sky-500 hover:text-sky-700 dark:hover:text-sky-300">
                        <X class="w-4 h-4" />
                    </button>
                </div>
            </div>

            <!-- Contenido de la vista -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                <slot />
            </main>

            <!-- Pie de página de la aplicación -->
            <footer class="py-4 px-4 sm:px-6 lg:px-8 border-t border-slate-200/80 dark:border-slate-800/80 text-center text-xs text-slate-500 dark:text-slate-400 flex flex-col sm:flex-row items-center justify-between gap-2">
                <span>&copy; {{ new Date().getFullYear() }} Ynentario. Todos los derechos reservados.</span>
                <span class="inline-flex items-center gap-1 text-[11px]">
                    Designed and Developed by
                    <a
                        href="https://codestrokes.tech"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 underline underline-offset-2 transition-colors"
                    >
                        Code Strokes
                    </a>
                </span>
            </footer>
        </div>

        <!-- Modales globales: Ajustes, Carrusel de bienvenida y Búsqueda rápida -->
        <SettingsModal
            :show="showSettingsModal"
            @close="showSettingsModal = false"
            @open-tour="showWelcomeModal = true"
        />

        <WelcomeCarouselModal
            :show="showWelcomeModal"
            @close="showWelcomeModal = false"
        />

        <QuickStockSearchModal
            :show="showQuickSearch"
            @close="showQuickSearch = false"
        />
    </div>
</template>
