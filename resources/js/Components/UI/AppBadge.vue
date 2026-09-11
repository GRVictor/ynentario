<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    variant?: 'green' | 'amber' | 'red' | 'blue' | 'slate' | 'indigo' | 'brand';
    size?: 'sm' | 'md';
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'slate',
    size: 'md',
});

const variantClasses = computed(() => {
    switch (props.variant) {
        case 'brand':
            return 'bg-brand-50 text-brand-700 border-brand-200 ring-brand-600/10 dark:bg-brand-950/50 dark:text-brand-300 dark:border-brand-900/80';
        case 'green':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200 ring-emerald-600/10 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-800/80';
        case 'amber':
            return 'bg-amber-50 text-amber-700 border-amber-200 ring-amber-600/10 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-800/80';
        case 'red':
            return 'bg-rose-50 text-rose-700 border-rose-200 ring-rose-600/10 dark:bg-rose-950/50 dark:text-rose-300 dark:border-rose-800/80';
        case 'blue':
            return 'bg-sky-50 text-sky-700 border-sky-200 ring-sky-600/10 dark:bg-sky-950/50 dark:text-sky-300 dark:border-sky-800/80';
        case 'indigo':
            return 'bg-indigo-50 text-indigo-700 border-indigo-200 ring-indigo-600/10 dark:bg-indigo-950/50 dark:text-indigo-300 dark:border-indigo-800/80';
        case 'slate':
        default:
            return 'bg-slate-50 text-slate-700 border-slate-200 ring-slate-600/10 dark:bg-slate-800/80 dark:text-slate-300 dark:border-slate-700';
    }
});

const sizeClasses = computed(() => {
    return props.size === 'sm' ? 'px-2 py-0.5 text-xs' : 'px-2.5 py-1 text-xs font-semibold';
});
</script>

<template>
    <span
        :class="[
            'inline-flex items-center gap-1.5 rounded-md border font-medium ring-1 ring-inset ring-transparent select-none transition-colors',
            variantClasses,
            sizeClasses,
        ]"
    >
        <span
            :class="[
                'w-1.5 h-1.5 rounded-full',
                variant === 'brand' && 'bg-brand-600',
                variant === 'green' && 'bg-emerald-500',
                variant === 'amber' && 'bg-amber-500',
                variant === 'red' && 'bg-rose-500',
                variant === 'blue' && 'bg-sky-500',
                variant === 'indigo' && 'bg-indigo-500',
                variant === 'slate' && 'bg-slate-400',
            ]"
        />
        <slot />
    </span>
</template>
