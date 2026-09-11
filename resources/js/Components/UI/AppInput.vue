<script setup lang="ts">
interface Props {
    modelValue: string | number | null | undefined;
    label?: string;
    type?: string;
    error?: string;
    hint?: string;
    placeholder?: string;
    required?: boolean;
    disabled?: boolean;
    step?: string | number;
    min?: string | number;
    max?: string | number;
}

withDefaults(defineProps<Props>(), {
    type: 'text',
    modelValue: '',
    required: false,
    disabled: false,
});

defineEmits<{
    (e: 'update:modelValue', value: string | number): void;
}>();
</script>

<template>
    <div class="w-full">
        <label v-if="label" class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
            {{ label }}
            <span v-if="required" class="text-red-500 ml-0.5">*</span>
        </label>
        <div class="relative rounded-lg shadow-xs">
            <input
                :type="type"
                :value="modelValue"
                :placeholder="placeholder"
                :required="required"
                :disabled="disabled"
                :step="step"
                :min="min"
                :max="max"
                @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
                :class="[
                    'block w-full rounded-lg text-sm transition-colors border px-3 py-2 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-offset-1 dark:focus:ring-offset-slate-900 disabled:bg-slate-50 disabled:dark:bg-slate-800 disabled:text-slate-500 disabled:dark:text-slate-500 disabled:cursor-not-allowed',
                    error
                        ? 'border-red-400 dark:border-red-500 text-red-900 dark:text-red-300 focus:border-red-500 focus:ring-red-200 dark:focus:ring-red-900/30'
                        : 'border-slate-300 dark:border-slate-700 hover:border-slate-400 dark:hover:border-slate-600 focus:border-indigo-500 focus:ring-indigo-100 dark:focus:ring-indigo-900/30',
                ]"
            />
        </div>
        <p v-if="error" class="mt-1.5 text-xs text-red-600 dark:text-red-400 flex items-center gap-1 font-medium">
            <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            {{ error }}
        </p>
        <p v-else-if="hint" class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ hint }}</p>
    </div>
</template>
