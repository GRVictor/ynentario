<script setup lang="ts">
interface Props {
    title?: string;
    description?: string;
}

defineProps<Props>();
</script>

<template>
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden transition-colors">
        <div v-if="title || $slots.header || $slots.actions" class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between gap-4">
            <div>
                <slot name="header">
                    <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100 tracking-tight">{{ title }}</h3>
                    <p v-if="description" class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ description }}</p>
                </slot>
            </div>
            <div v-if="$slots.actions" class="flex items-center gap-2">
                <slot name="actions" />
            </div>
        </div>

        <div class="p-5">
            <slot />
        </div>

        <div v-if="$slots.footer" class="px-5 py-3.5 bg-slate-50 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-800">
            <slot name="footer" />
        </div>
    </div>
</template>
