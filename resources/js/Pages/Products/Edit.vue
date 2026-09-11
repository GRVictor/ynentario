<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppInput from '@/Components/UI/AppInput.vue';
import AppSelect from '@/Components/UI/AppSelect.vue';
import AppCard from '@/Components/UI/AppCard.vue';
import { ArrowLeft, Save } from 'lucide-vue-next';

interface ProductData {
    id: number;
    sku: string;
    name: string;
    internal_code?: string;
    barcode?: string;
    description?: string;
    category_id?: number;
    brand_id?: number;
    unit_id: number;
    supplier_id?: number;
    cost_price: number;
    selling_price: number;
    min_stock: number;
    max_stock?: number;
    reorder_point: number;
    default_location?: string;
    notes?: string;
    is_active: boolean;
}

interface OptionItem {
    id: number;
    name: string;
}

interface Props {
    product: ProductData;
    categories: OptionItem[];
    brands: OptionItem[];
    units: OptionItem[];
    suppliers: OptionItem[];
}

const props = defineProps<Props>();

const form = useForm({
    sku: props.product.sku,
    name: props.product.name,
    internal_code: props.product.internal_code || '',
    barcode: props.product.barcode || '',
    description: props.product.description || '',
    category_id: props.product.category_id || '',
    brand_id: props.product.brand_id || '',
    unit_id: props.product.unit_id,
    supplier_id: props.product.supplier_id || '',
    cost_price: props.product.cost_price,
    selling_price: props.product.selling_price,
    min_stock: props.product.min_stock,
    max_stock: props.product.max_stock || '',
    reorder_point: props.product.reorder_point,
    default_location: props.product.default_location || '',
    notes: props.product.notes || '',
    is_active: props.product.is_active,
});

const submit = () => {
    form.put(route('products.update', props.product.id));
};
</script>

<template>
    <Head :title="`Editar ${product.name}`" />

    <AuthenticatedLayout
        title="Editar producto"
        :breadcrumbs="[
            { label: 'Productos', href: route('products.index') },
            { label: product.sku, href: route('products.show', product.id) },
            { label: 'Editar' }
        ]"
    >
        <template #actions>
            <Link :href="route('products.show', product.id)">
                <AppButton size="sm" variant="outline">
                    <ArrowLeft class="w-4 h-4" />
                    <span>Regresar</span>
                </AppButton>
            </Link>
        </template>

        <form @submit.prevent="submit" class="max-w-4xl space-y-6">
            <AppCard title="Datos del producto">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <AppInput
                            v-model="form.sku"
                            label="SKU"
                            :error="form.errors.sku"
                            required
                        />
                    </div>

                    <div>
                        <AppInput
                            v-model="form.barcode"
                            label="Código de barras"
                            :error="form.errors.barcode"
                        />
                    </div>

                    <div>
                        <AppInput
                            v-model="form.internal_code"
                            label="Código interno"
                            :error="form.errors.internal_code"
                        />
                    </div>

                    <div class="sm:col-span-2 lg:col-span-3">
                        <AppInput
                            v-model="form.name"
                            label="Nombre"
                            :error="form.errors.name"
                            required
                        />
                    </div>

                    <div class="sm:col-span-2 lg:col-span-3">
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Descripción</label>
                        <textarea
                            v-model="form.description"
                            rows="3"
                            class="block w-full rounded-lg text-sm border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 p-3 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        ></textarea>
                    </div>

                    <div class="flex items-center gap-2 mt-2">
                        <input
                            id="is_active"
                            v-model="form.is_active"
                            type="checkbox"
                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4"
                        />
                        <label for="is_active" class="text-xs font-semibold text-slate-700 dark:text-slate-300">Producto activo</label>
                    </div>
                </div>
            </AppCard>

            <AppCard title="Clasificación">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <AppSelect
                            v-model="form.category_id"
                            :options="categories"
                            label="Categoría"
                            :error="form.errors.category_id"
                        />
                    </div>

                    <div>
                        <AppSelect
                            v-model="form.brand_id"
                            :options="brands"
                            label="Marca"
                            :error="form.errors.brand_id"
                        />
                    </div>

                    <div>
                        <AppSelect
                            v-model="form.unit_id"
                            :options="units"
                            label="Unidad"
                            :error="form.errors.unit_id"
                            required
                        />
                    </div>

                    <div>
                        <AppSelect
                            v-model="form.supplier_id"
                            :options="suppliers"
                            label="Proveedor"
                            :error="form.errors.supplier_id"
                        />
                    </div>
                </div>
            </AppCard>

            <AppCard title="Precios e inventario">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <AppInput
                            v-model="form.cost_price"
                            type="number"
                            step="0.01"
                            min="0"
                            label="Precio de costo"
                            :error="form.errors.cost_price"
                            required
                        />
                    </div>

                    <div>
                        <AppInput
                            v-model="form.selling_price"
                            type="number"
                            step="0.01"
                            min="0"
                            label="Precio de venta"
                            :error="form.errors.selling_price"
                            required
                        />
                    </div>

                    <div>
                        <AppInput
                            v-model="form.min_stock"
                            type="number"
                            step="1"
                            min="0"
                            label="Stock mínimo"
                            :error="form.errors.min_stock"
                            required
                        />
                    </div>

                    <div>
                        <AppInput
                            v-model="form.reorder_point"
                            type="number"
                            step="1"
                            min="0"
                            label="Punto de reorden"
                            :error="form.errors.reorder_point"
                            required
                        />
                    </div>

                    <div>
                        <AppInput
                            v-model="form.max_stock"
                            type="number"
                            step="1"
                            min="0"
                            label="Stock máximo"
                            :error="form.errors.max_stock"
                        />
                    </div>

                    <div>
                        <AppInput
                            v-model="form.default_location"
                            label="Ubicación en almacén"
                            :error="form.errors.default_location"
                        />
                    </div>
                </div>
            </AppCard>

            <div class="flex items-center justify-end gap-3 pt-2">
                <Link :href="route('products.show', product.id)">
                    <AppButton variant="outline">Cancelar</AppButton>
                </Link>
                <AppButton type="submit" :loading="form.processing">
                    <Save class="w-4 h-4" />
                    <span>Guardar cambios</span>
                </AppButton>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
