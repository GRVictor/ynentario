<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppCard from '@/Components/UI/AppCard.vue';
import { Download, UploadCloud, AlertCircle, CheckCircle2, ArrowLeft } from 'lucide-vue-next';

interface ImportError {
    row: number;
    field: string;
    message: string;
}

interface ImportSummary {
    total: number;
    imported: number;
    updated: number;
    failed: number;
    errors: ImportError[];
}

const page = usePage<any>();
const fileInput = ref<HTMLInputElement | null>(null);
const fileName = ref<string>('');

const form = useForm({
    file: null as File | null,
    update_existing: true,
});

const onFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        form.file = target.files[0];
        fileName.value = target.files[0].name;
    }
};

const submit = () => {
    if (!form.file) return;
    form.post(route('products.import.process'), {
        onSuccess: () => {
            form.reset('file');
            fileName.value = '';
            if (fileInput.value) fileInput.value.value = '';
        },
    });
};
</script>

<template>
    <Head title="Importar productos" />

    <AuthenticatedLayout
        title="Importar productos"
        :breadcrumbs="[
            { label: 'Productos', href: route('products.index') },
            { label: 'Importar' }
        ]"
    >
        <template #actions>
            <Link :href="route('products.index')">
                <AppButton size="sm" variant="outline">
                    <ArrowLeft class="w-4 h-4" />
                    <span>Regresar</span>
                </AppButton>
            </Link>
        </template>

        <div class="max-w-3xl space-y-6">
            <!-- Paso 1: Descargar plantilla -->
            <AppCard title="Plantilla de importación">
                <div class="flex items-center justify-between gap-4">
                    <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed max-w-xl">
                        Descarga el archivo base en formato CSV con las columnas necesarias para registrar tus productos.
                    </p>
                    <a :href="route('products.template')" download>
                        <AppButton size="sm" variant="outline">
                            <Download class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                            <span>Descargar plantilla</span>
                        </AppButton>
                    </a>
                </div>
            </AppCard>

            <!-- Paso 2: Subir archivo y opciones -->
            <AppCard title="Subir archivo CSV">
                <form @submit.prevent="submit" class="space-y-4">
                    <!-- Zona de carga de archivo -->
                    <div
                        class="border-2 border-dashed border-slate-300 dark:border-slate-700 hover:border-indigo-400 dark:hover:border-indigo-500 rounded-xl p-6 text-center cursor-pointer transition-colors bg-slate-50/50 dark:bg-slate-900/40"
                        @click="fileInput?.click()"
                    >
                        <input
                            ref="fileInput"
                            type="file"
                            accept=".csv,text/csv"
                            class="hidden"
                            @change="onFileChange"
                        />
                        <UploadCloud class="w-10 h-10 mx-auto text-indigo-500 dark:text-indigo-400 mb-2" />
                        <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">
                            {{ fileName ? fileName : 'Arrastra tu archivo CSV aquí o haz clic para seleccionarlo' }}
                        </p>
                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Archivos .csv de hasta 5 MB</p>
                    </div>
                    <p v-if="form.errors.file" class="text-xs text-red-600 dark:text-red-400 font-medium">{{ form.errors.file }}</p>

                    <!-- Opción para actualizar productos existentes -->
                    <div class="flex items-start gap-3 p-3 bg-slate-50 dark:bg-slate-800/60 rounded-lg border border-slate-100 dark:border-slate-800">
                        <input
                            id="update_existing"
                            v-model="form.update_existing"
                            type="checkbox"
                            class="rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500 mt-0.5 dark:bg-slate-900"
                        />
                        <div>
                            <label for="update_existing" class="text-xs font-semibold text-slate-800 dark:text-slate-200 cursor-pointer">
                                Actualizar productos si el SKU ya existe
                            </label>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                Si se activa, se actualizarán los datos de los productos que coincidan con un SKU existente.
                            </p>
                        </div>
                    </div>

                    <!-- Botón de procesar importación -->
                    <div class="flex justify-end pt-2">
                        <AppButton type="submit" :disabled="!form.file || form.processing" :loading="form.processing">
                            <span>Importar productos</span>
                        </AppButton>
                    </div>
                </form>
            </AppCard>

            <!-- Paso 3: Resumen de resultados -->
            <div v-if="page.props.flash?.importResult" class="space-y-4">
                <AppCard title="Resultado de la importación">
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
                        <div class="bg-slate-50 dark:bg-slate-800/80 p-3 rounded-lg border border-slate-200/80 dark:border-slate-700 text-center">
                            <span class="text-[11px] text-slate-500 dark:text-slate-400 block">Total leídos</span>
                            <span class="text-lg font-bold text-slate-900 dark:text-white">{{ page.props.flash.importResult.total }}</span>
                        </div>
                        <div class="bg-emerald-50 dark:bg-emerald-950/40 p-3 rounded-lg border border-emerald-200/80 dark:border-emerald-800 text-center">
                            <span class="text-[11px] text-emerald-700 dark:text-emerald-300 block">Nuevos</span>
                            <span class="text-lg font-bold text-emerald-700 dark:text-emerald-300">{{ page.props.flash.importResult.imported }}</span>
                        </div>
                        <div class="bg-sky-50 dark:bg-sky-950/40 p-3 rounded-lg border border-sky-200/80 dark:border-sky-800 text-center">
                            <span class="text-[11px] text-sky-700 dark:text-sky-300 block">Actualizados</span>
                            <span class="text-lg font-bold text-sky-700 dark:text-sky-300">{{ page.props.flash.importResult.updated }}</span>
                        </div>
                        <div class="bg-rose-50 dark:bg-rose-950/40 p-3 rounded-lg border border-rose-200/80 dark:border-rose-800 text-center">
                            <span class="text-[11px] text-rose-700 dark:text-rose-300 block">Con errores</span>
                            <span class="text-lg font-bold text-rose-700 dark:text-rose-300">{{ page.props.flash.importResult.failed }}</span>
                        </div>
                    </div>

                    <!-- Tabla de errores si los hubiera -->
                    <div v-if="page.props.flash.importResult.errors?.length > 0" class="space-y-2">
                        <h4 class="text-xs font-bold text-rose-700 dark:text-rose-400 flex items-center gap-1.5">
                            <AlertCircle class="w-4 h-4" />
                            <span>Errores encontrados:</span>
                        </h4>
                        <div class="max-h-60 overflow-y-auto border border-rose-200 dark:border-rose-800 rounded-lg text-xs">
                            <table class="w-full text-left">
                                <thead class="bg-rose-50/80 dark:bg-rose-950/80 text-rose-900 dark:text-rose-200 font-semibold sticky top-0">
                                    <tr>
                                        <th class="p-2.5">Fila</th>
                                        <th class="p-2.5">Campo</th>
                                        <th class="p-2.5">Motivo</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-rose-100 dark:divide-rose-900/40 bg-white dark:bg-slate-900">
                                    <tr v-for="(err, idx) in page.props.flash.importResult.errors" :key="idx">
                                        <td class="p-2.5 font-mono font-bold text-slate-800 dark:text-slate-200">Fila {{ err.row }}</td>
                                        <td class="p-2.5 font-mono text-slate-600 dark:text-slate-400">{{ err.field }}</td>
                                        <td class="p-2.5 text-rose-700 dark:text-rose-300">{{ err.message }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div v-else class="text-center py-4 text-emerald-700 dark:text-emerald-400 font-semibold text-xs flex items-center justify-center gap-2">
                        <CheckCircle2 class="w-5 h-5" />
                        <span>Se importaron todos los productos correctamente.</span>
                    </div>
                </AppCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
