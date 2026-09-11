<script setup lang="ts">
interface Props {
    show: boolean;
    title?: string;
    maxWidth?: 'sm' | 'md' | 'lg' | 'xl' | '2xl';
}

withDefaults(defineProps<Props>(), {
    maxWidth: 'md',
});

defineEmits<{
    (e: 'close'): void;
}>();
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs transition-opacity" @click="$emit('close')"></div>

        <div class="min-h-full flex items-center justify-center p-4">
            <div
                :class="[
                    'relative w-full bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden transition-all',
                    maxWidth === 'sm' && 'max-w-sm',
                    maxWidth === 'md' && 'max-w-md',
                    maxWidth === 'lg' && 'max-w-lg',
                    maxWidth === 'xl' && 'max-w-xl',
                    maxWidth === '2xl' && 'max-w-2xl',
                ]"
            >
                <div v-if="title || $slots.header" class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">
                        <slot name="header">{{ title }}</slot>
                    </h3>
                    <button
                        type="button"
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded-md transition-colors"
                        @click="$emit('close')"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6 text-slate-800 dark:text-slate-200">
                    <slot />
                </div>

                <div v-if="$slots.footer" class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-2.5">
                    <slot name="footer" />
                </div>
            </div>
        </div>
    </div>
</template>
