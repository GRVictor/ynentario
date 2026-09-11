<script setup lang="ts">
import AppButton from './AppButton.vue';

interface Props {
    show: boolean;
    title: string;
    message: string;
    confirmText?: string;
    cancelText?: string;
    variant?: 'danger' | 'primary';
    loading?: boolean;
}

withDefaults(defineProps<Props>(), {
    confirmText: 'Confirmar',
    cancelText: 'Cancelar',
    variant: 'danger',
    loading: false,
});

defineEmits<{
    (e: 'confirm'): void;
    (e: 'cancel'): void;
}>();
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <!-- Fondo oscuro con desenfoque -->
        <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs transition-opacity" @click="$emit('cancel')"></div>

        <div class="min-h-full flex items-center justify-center p-4">
            <div class="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-2xl p-6 shadow-2xl border border-slate-100 dark:border-slate-800 transition-all">
                <div class="flex items-start gap-4">
                    <div
                        :class="[
                            'w-10 h-10 rounded-full flex items-center justify-center shrink-0',
                            variant === 'danger'
                                ? 'bg-red-100 dark:bg-red-950/60 text-red-600 dark:text-red-400'
                                : 'bg-indigo-100 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400'
                        ]"
                    >
                        <svg v-if="variant === 'danger'" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>

                    <div class="flex-1">
                        <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ title }}</h3>
                        <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400 leading-relaxed">{{ message }}</p>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-2.5">
                    <AppButton variant="outline" size="sm" :disabled="loading" @click="$emit('cancel')">
                        {{ cancelText }}
                    </AppButton>
                    <AppButton :variant="variant" size="sm" :loading="loading" @click="$emit('confirm')">
                        {{ confirmText }}
                    </AppButton>
                </div>
            </div>
        </div>
    </div>
</template>
