<script setup lang="ts">
import { onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Sun, Moon } from 'lucide-vue-next';
import { useAppearance } from '@/composables/useAppearance';

const { isDark, toggleDark, initAppearance } = useAppearance();

onMounted(() => {
    initAppearance();
});
</script>

<template>
    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 flex flex-col justify-center py-12 sm:px-6 lg:px-8 transition-colors duration-150 relative">
        <!-- Línea superior con color de marca -->
        <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-brand-600 via-brand-500 to-indigo-600"></div>

        <!-- Selector de tema en esquina superior derecha -->
        <div class="absolute top-4 right-4 sm:top-6 sm:right-6 z-50">
            <button
                type="button"
                @click="toggleDark"
                class="p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white shadow-xs hover:shadow transition-all flex items-center gap-2 text-xs font-semibold"
                :title="isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
            >
                <Sun v-if="isDark" class="w-4 h-4 text-amber-400" />
                <Moon v-else class="w-4 h-4 text-slate-600" />
                <span class="hidden sm:inline">{{ isDark ? 'Modo Claro' : 'Modo Oscuro' }}</span>
            </button>
        </div>

        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
            <Link :href="route('login')" class="inline-flex items-center gap-2.5">
                <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-brand-500 via-brand-600 to-brand-700 flex items-center justify-center text-white font-black text-2xl shadow-lg shadow-brand-600/35 ring-1 ring-white/20">
                    Y
                </div>
                <span class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Ynentario</span>
            </Link>
            <p class="mt-2 text-xs font-medium text-slate-500 dark:text-slate-400">
                Control de inventarios y almacenes
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4">
            <div class="bg-white dark:bg-slate-900 py-8 px-6 sm:px-10 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xl shadow-slate-200/40 dark:shadow-none transition-colors">
                <slot />
            </div>

            <!-- Pie de página -->
            <footer class="mt-8 text-center text-xs text-slate-500 dark:text-slate-400 space-y-1">
                <p>&copy; {{ new Date().getFullYear() }} Ynentario. Todos los derechos reservados.</p>
                <p class="text-[11px]">
                    Designed and Developed by
                    <a
                        href="https://codestrokes.tech"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="font-semibold text-brand-600 dark:text-brand-400 hover:text-brand-700 dark:hover:text-brand-300 underline underline-offset-2 transition-colors"
                    >
                        Code Strokes
                    </a>
                </p>
            </footer>
        </div>
    </div>
</template>
