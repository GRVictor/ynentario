<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Props {
    links: PaginationLink[];
    from?: number | null;
    to?: number | null;
    total?: number;
}

const props = defineProps<Props>();

const cleanLabel = (label: string): string => {
    if (label.includes('Previous') || label.includes('&laquo;')) return 'Anterior';
    if (label.includes('Next') || label.includes('&raquo;')) return 'Siguiente';
    return label;
};

const hasPages = computed(() => (props.links?.length ?? 0) > 3);
</script>

<template>
    <div v-if="links && links.length > 1" class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-4 border-t border-slate-100 dark:border-slate-800">
        <div v-if="total !== undefined && from !== undefined && to !== undefined" class="text-xs text-slate-500 dark:text-slate-400">
            Mostrando <span class="font-medium text-slate-800 dark:text-slate-200">{{ from ?? 0 }}</span> a <span class="font-medium text-slate-800 dark:text-slate-200">{{ to ?? 0 }}</span> de <span class="font-medium text-slate-800 dark:text-slate-200">{{ total }}</span> resultados
        </div>
        <div v-else class="text-xs text-slate-500 dark:text-slate-400">Paginación</div>

        <nav v-if="hasPages" class="inline-flex rounded-lg shadow-xs -space-x-px text-xs">
            <template v-for="(link, key) in links" :key="key">
                <span
                    v-if="!link.url"
                    class="relative inline-flex items-center px-3 py-1.5 border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-400 dark:text-slate-500 cursor-not-allowed first:rounded-l-lg last:rounded-r-lg"
                    v-html="cleanLabel(link.label)"
                />
                <Link
                    v-else
                    :href="link.url"
                    preserve-scroll
                    :class="[
                        'relative inline-flex items-center px-3 py-1.5 border border-slate-200 dark:border-slate-700 text-xs font-medium transition-colors first:rounded-l-lg last:rounded-r-lg',
                        link.active
                            ? 'z-10 bg-indigo-600 text-white border-indigo-600 font-semibold'
                            : 'bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white',
                    ]"
                    v-html="cleanLabel(link.label)"
                />
            </template>
        </nav>
    </div>
</template>
