<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    type?: 'button' | 'submit' | 'reset';
    variant?: 'primary' | 'secondary' | 'outline' | 'danger' | 'ghost' | 'brand';
    size?: 'sm' | 'md' | 'lg';
    loading?: boolean;
    disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    type: 'button',
    variant: 'primary',
    size: 'md',
    loading: false,
    disabled: false,
});

const variantClasses = computed(() => {
    switch (props.variant) {
        case 'brand':
            return 'bg-brand-600 text-white hover:bg-brand-700 border-brand-600 focus:ring-brand-400 dark:focus:ring-brand-900 shadow-sm shadow-brand-600/30';
        case 'secondary':
            return 'bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-700 border-slate-200 dark:border-slate-700 focus:ring-slate-300 dark:focus:ring-slate-700';
        case 'outline':
            return 'bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800/80 border-slate-300 dark:border-slate-700 hover:border-slate-400 dark:hover:border-slate-600 focus:ring-slate-200 dark:focus:ring-slate-800';
        case 'danger':
            return 'bg-red-600 text-white hover:bg-red-700 border-red-600 focus:ring-red-400 dark:focus:ring-red-900 shadow-sm';
        case 'ghost':
            return 'bg-transparent text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-slate-100 border-transparent focus:ring-slate-200 dark:focus:ring-slate-800';
        case 'primary':
        default:
            return 'bg-indigo-600 text-white hover:bg-indigo-700 border-indigo-600 focus:ring-indigo-300 dark:focus:ring-indigo-900 shadow-sm';
    }
});

const sizeClasses = computed(() => {
    switch (props.size) {
        case 'sm':
            return 'px-2.5 py-1.5 text-xs rounded-md gap-1.5';
        case 'lg':
            return 'px-5 py-2.5 text-base rounded-lg gap-2.5';
        case 'md':
        default:
            return 'px-3.5 py-2 text-sm rounded-lg gap-2';
    }
});
</script>

<template>
    <button
        :type="type"
        :disabled="disabled || loading"
        :class="[
            'inline-flex items-center justify-center font-medium transition-colors duration-150 border focus:outline-none focus:ring-2 focus:ring-offset-1 dark:focus:ring-offset-slate-900 disabled:opacity-50 disabled:cursor-not-allowed select-none',
            variantClasses,
            sizeClasses,
        ]"
    >
        <svg
            v-if="loading"
            class="animate-spin -ml-0.5 h-4 w-4 text-current"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
        >
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <slot />
    </button>
</template>
