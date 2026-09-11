<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import {
    Sun,
    Moon,
    Monitor,
    Type,
    Sparkles,
    Shield,
    Check,
    X,
    Sliders,
    Info,
} from 'lucide-vue-next';
import { useAppearance, type Theme, type FontSize } from '@/composables/useAppearance';
import AppButton from './AppButton.vue';

interface Props {
    show: boolean;
}

defineProps<Props>();
const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'open-tour'): void;
}>();

const page = usePage<any>();
const user = computed(() => page.props.auth?.user);
const userRole = computed(() => user.value?.roles?.[0] || 'Consulta');

const { theme, fontSize, fontSizesMap, setTheme, setFontSize } = useAppearance();

const themes: { id: Theme; label: string; description: string; icon: any }[] = [
    {
        id: 'light',
        label: 'Modo claro',
        description: 'Fondo claro.',
        icon: Sun,
    },
    {
        id: 'dark',
        label: 'Modo oscuro',
        description: 'Fondo oscuro para descansar la vista.',
        icon: Moon,
    },
    {
        id: 'system',
        label: 'Automático',
        description: 'Según la configuración de tu equipo.',
        icon: Monitor,
    },
];

const fontSizes: FontSize[] = ['small', 'normal', 'large', 'huge'];

const triggerTour = () => {
    emit('close');
    emit('open-tour');
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <!-- Fondo oscuro desenfocado -->
        <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs transition-opacity" @click="$emit('close')"></div>

        <div class="min-h-full flex items-center justify-center p-4">
            <div
                class="relative w-full max-w-xl bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200/80 dark:border-slate-800 overflow-hidden transition-all text-slate-900 dark:text-slate-100"
            >
                <!-- Encabezado del modal -->
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                            <Sliders class="w-4 h-4" />
                        </div>
                        <div>
                            <h3 class="text-base font-bold leading-tight">Ajustes</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Personaliza la apariencia del sistema.</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                        @click="$emit('close')"
                    >
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Cuerpo del modal -->
                <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto">
                    <!-- SECCIÓN 1: APARIENCIA Y TEMA -->
                    <div>
                        <div class="flex items-center gap-2 mb-3">
                            <Sun class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                Tema
                            </h4>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <button
                                v-for="t in themes"
                                :key="t.id"
                                type="button"
                                @click="setTheme(t.id)"
                                :class="[
                                    'p-3.5 rounded-xl border text-left transition-all flex flex-col justify-between relative group',
                                    theme === t.id
                                        ? 'border-indigo-600 bg-indigo-50/60 dark:bg-indigo-950/40 ring-2 ring-indigo-500/20'
                                        : 'border-slate-200 dark:border-slate-800 hover:border-slate-300 dark:hover:border-slate-700 bg-white dark:bg-slate-900/50'
                                ]"
                            >
                                <div class="flex items-center justify-between mb-2">
                                    <component
                                        :is="t.icon"
                                        :class="[
                                            'w-5 h-5',
                                            theme === t.id ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-500 dark:text-slate-400'
                                        ]"
                                    />
                                    <div
                                        v-if="theme === t.id"
                                        class="w-4 h-4 rounded-full bg-indigo-600 text-white flex items-center justify-center text-[10px]"
                                    >
                                        <Check class="w-3 h-3 stroke-[3]" />
                                    </div>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-slate-900 dark:white">{{ t.label }}</p>
                                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 leading-snug">{{ t.description }}</p>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- SECCIÓN 2: TAMAÑO DE LETRA -->
                    <div class="pt-4 border-t border-slate-100 dark:border-slate-800">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <Type class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                    Tamaño de letra
                                </h4>
                            </div>
                            <span class="text-xs font-medium text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/60 px-2 py-0.5 rounded-md">
                                {{ fontSizesMap[fontSize].percent }}
                            </span>
                        </div>

                        <p class="text-xs text-slate-500 dark:text-slate-400 mb-3">
                            Ajusta el tamaño del texto para leer con mayor comodidad.
                        </p>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                            <button
                                v-for="key in fontSizes"
                                :key="key"
                                type="button"
                                @click="setFontSize(key)"
                                :class="[
                                    'p-3 rounded-xl border text-center transition-all',
                                    fontSize === key
                                        ? 'border-indigo-600 bg-indigo-50/60 dark:bg-indigo-950/40 font-bold text-indigo-700 dark:text-indigo-300 ring-2 ring-indigo-500/20'
                                        : 'border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:border-slate-300 dark:hover:border-slate-700 bg-white dark:bg-slate-900/50'
                                ]"
                            >
                                <span class="block text-xs font-semibold">{{ fontSizesMap[key].label }}</span>
                                <span class="block text-[10px] text-slate-400 mt-0.5">{{ fontSizesMap[key].percent }}</span>
                            </button>
                        </div>

                    </div>

                    <!-- SECCIÓN 3: GUÍA DE USO -->
                    <div class="pt-4 border-t border-slate-100 dark:border-slate-800">
                        <div class="p-4 rounded-xl bg-indigo-50/50 dark:bg-indigo-950/30 border border-indigo-100/80 dark:border-indigo-900/50 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-xl bg-indigo-600 text-white flex items-center justify-center shrink-0 shadow-xs">
                                    <Sparkles class="w-4 h-4" />
                                </div>
                                <div>
                                    <h5 class="text-xs font-bold text-slate-900 dark:text-white">
                                        Guía de uso
                                    </h5>
                                    <p class="text-[11px] text-slate-600 dark:text-slate-300 mt-0.5">
                                        Vuelve a consultar la guía para tu rol de <strong>{{ userRole }}</strong>.
                                    </p>
                                </div>
                            </div>
                            <AppButton variant="primary" size="sm" @click="triggerTour" class="shrink-0 w-full sm:w-auto">
                                Ver guía
                            </AppButton>
                        </div>
                    </div>

                    <!-- SECCIÓN 4: INFORMACIÓN DEL SISTEMA -->
                    <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs text-slate-500 dark:text-slate-400">
                        <div class="flex items-center gap-1.5">
                            <Shield class="w-3.5 h-3.5 text-slate-400" />
                            <span>Usuario: <strong class="text-slate-800 dark:text-slate-200">{{ user?.name }}</strong></span>
                        </div>
                        <div>
                            <span>Ynentario v1.0.0</span>
                        </div>
                    </div>
                </div>

                <!-- Pie del modal -->
                <div class="px-6 py-3.5 bg-slate-50 dark:bg-slate-800/60 border-t border-slate-100 dark:border-slate-800 flex justify-end">
                    <AppButton variant="outline" size="sm" @click="$emit('close')">
                        Cerrar
                    </AppButton>
                </div>
            </div>
        </div>
    </div>
</template>
