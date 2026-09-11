<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppInput from '@/Components/UI/AppInput.vue';
import AppSelect from '@/Components/UI/AppSelect.vue';
import AppCard from '@/Components/UI/AppCard.vue';
import { ArrowLeft, Save } from 'lucide-vue-next';

interface OptionItem {
    id: number;
    name: string;
    abbreviation?: string;
}

interface Props {
    categories: OptionItem[];
    brands: OptionItem[];
    units: OptionItem[];
    suppliers: OptionItem[];
}

const props = defineProps<Props>();

const form = useForm({
    sku: '',
    name: '',
    internal_code: '',
    barcode: '',
    description: '',
    category_id: '' as string | number,
    brand_id: '' as string | number,
    unit_id: props.units[0]?.id || ('' as string | number),
    supplier_id: '' as string | number,
    cost_price: '0.00',
    selling_price: '0.00',
    min_stock: '0',
    max_stock: '',
    reorder_point: '0',
    default_location: '',
    notes: '',
    is_active: true,
});

const submit = () => {
    form.post(route('products.store'));
};
</script>

<template>
    <Head title="Nuevo Producto" />

    <AuthenticatedLayout
        title="Nuevo producto"
        :breadcrumbs="[
            { label: 'Productos', href: route('products.index') },
            { label: 'Nuevo' }
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

        <form @submit.prevent="submit" class="max-w-4xl space-y-6">
            <!-- Sección 1: Identificación y nombres -->
            <AppCard title="Identificación del producto">
                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <AppInput
                            v-model="form.sku"
                            label="SKU / Código único"
                            placeholder="Ej. LAP-001"
                            :error="form.errors.sku"
                            required
                        />

                        <AppInput
                            v-model="form.barcode"
                            label="Código de barras (opcional)"
                            placeholder="Ej. 750123456789"
                            :error="form.errors.barcode"
                        />
                    </div>

                    <AppInput
                        v-model="form.name"
                        label="Nombre del producto"
                        placeholder="Ej. Laptop Dell Latitude 5420"
                        :error="form.errors.name"
                        required
                    />

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                            Descripción (opcional)
                        </label>
                        <textarea
                            v-model="form.description"
                            rows="3"
                            placeholder="Características, modelo o especificaciones..."
                            class="w-full text-xs rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 shadow-2xs transition-colors"
                        ></textarea>
                    </div>
                </div>
            </AppCard>

            <!-- Sección 2: Clasificación -->
            <AppCard title="Clasificación y catálogo">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <AppSelect
                        v-model="form.category_id"
                        :options="categories"
                        label="Categoría"
                        placeholder="Seleccionar..."
                        :error="form.errors.category_id"
                        required
                    />

                    <AppSelect
                        v-model="form.brand_id"
                        :options="brands"
                        label="Marca (opcional)"
                        placeholder="Seleccionar..."
                        :error="form.errors.brand_id"
                    />

                    <AppSelect
                        v-model="form.unit_id"
                        :options="units"
                        label="Unidad de medida"
                        placeholder="Seleccionar..."
                        :error="form.errors.unit_id"
                        required
                    />

                    <AppSelect
                        v-model="form.supplier_id"
                        :options="suppliers"
                        label="Proveedor habitual (opcional)"
                        placeholder="Seleccionar..."
                        :error="form.errors.supplier_id"
                    />
                </div>
            </AppCard>

            <!-- Sección 3: Costos, precios y existencias -->
            <AppCard title="Precios y niveles de existencias">
                <div class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <AppInput
                            v-model="form.cost_price"
                            type="number"
                            step="0.01"
                            min="0"
                            label="Precio de costo ($)"
                            placeholder="0.00"
                            :error="form.errors.cost_price"
                            required
                        />

                        <AppInput
                            v-model="form.selling_price"
                            type="number"
                            step="0.01"
                            min="0"
                            label="Precio de venta ($)"
                            placeholder="0.00"
                            :error="form.errors.selling_price"
                            required
                        />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <AppInput
                            v-model="form.min_stock"
                            type="number"
                            min="0"
                            label="Stock mínimo"
                            placeholder="0"
                            :error="form.errors.min_stock"
                            required
                        />

                        <AppInput
                            v-model="form.reorder_point"
                            type="number"
                            min="0"
                            label="Punto de reorden"
                            placeholder="0"
                            :error="form.errors.reorder_point"
                            required
                        />

                        <AppInput
                            v-model="form.max_stock"
                            type="number"
                            min="0"
                            label="Stock máximo (opcional)"
                            placeholder="Sin límite"
                            :error="form.errors.max_stock"
                        />
                    </div>

                    <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center gap-2">
                        <input
                            id="prod_active"
                            v-model="form.is_active"
                            type="checkbox"
                            class="rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500 dark:bg-slate-900"
                        />
                        <label for="prod_active" class="text-xs font-semibold text-slate-700 dark:text-slate-300">
                            Producto activo para movimientos y catálogo
                        </label>
                    </div>
                </div>
            </AppCard>

            <!-- Botón de guardar -->
            <div class="flex items-center justify-end gap-3 pt-2">
                <Link :href="route('products.index')">
                    <AppButton variant="outline">Cancelar</AppButton>
                </Link>
                <AppButton type="submit" :loading="form.processing">
                    <Save class="w-4 h-4" />
                    <span>Guardar producto</span>
                </AppButton>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
