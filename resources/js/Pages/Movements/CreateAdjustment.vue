<script setup lang="ts">
import { ref, watch, computed, onMounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppInput from '@/Components/UI/AppInput.vue';
import AppSelect from '@/Components/UI/AppSelect.vue';
import AppCard from '@/Components/UI/AppCard.vue';
import AppBadge from '@/Components/UI/AppBadge.vue';
import { ArrowLeft, SlidersHorizontal } from 'lucide-vue-next';
import axios from 'axios';

interface ProductOption {
    id: number;
    name: string;
    sku: string;
}

interface WarehouseOption {
    id: number;
    name: string;
    code: string;
}

interface Props {
    products: ProductOption[];
    warehouses: WarehouseOption[];
}

const props = defineProps<Props>();

const urlParams = typeof window !== 'undefined' ? new URLSearchParams(window.location.search) : null;
const initialProductId = urlParams?.get('product_id')
    ? Number(urlParams.get('product_id'))
    : (props.products[0]?.id || '');

const form = useForm({
    product_id: initialProductId as string | number,
    warehouse_id: props.warehouses[0]?.id || ('' as string | number),
    real_quantity: '0',
    reason: 'Ajuste tras conteo físico cíclico',
    reference: '',
    notes: '',
});

const currentStock = ref<number | null>(null);
const loadingStock = ref(false);

const fetchStock = async () => {
    if (!form.product_id || !form.warehouse_id) return;
    loadingStock.value = true;
    try {
        const response = await axios.get(route('movements.stock'), {
            params: {
                product_id: form.product_id,
                warehouse_id: form.warehouse_id,
            },
        });
        currentStock.value = response.data.quantity;
        form.real_quantity = String(response.data.quantity);
    } catch {
        currentStock.value = 0;
        form.real_quantity = '0';
    } finally {
        loadingStock.value = false;
    }
};

watch(() => [form.product_id, form.warehouse_id], () => {
    fetchStock();
});

onMounted(() => {
    fetchStock();
});

const difference = computed(() => {
    if (currentStock.value === null) return 0;
    return Number(form.real_quantity || 0) - currentStock.value;
});

const submit = () => {
    form.post(route('movements.adjustment.store'));
};
</script>

<template>
    <Head title="Ajustar inventario" />

    <AuthenticatedLayout
        title="Ajustar inventario"
        :breadcrumbs="[
            { label: 'Movimientos', href: route('movements.index') },
            { label: 'Ajustar inventario' }
        ]"
    >
        <template #actions>
            <Link :href="route('movements.index')">
                <AppButton size="sm" variant="outline">
                    <ArrowLeft class="w-4 h-4" />
                    <span>Regresar</span>
                </AppButton>
            </Link>
        </template>

        <form @submit.prevent="submit" class="max-w-2xl space-y-6">
            <AppCard title="Datos del ajuste">
                <div class="space-y-4">
                    <AppSelect
                        v-model="form.product_id"
                        label="Producto"
                        placeholder="Seleccionar producto"
                        :error="form.errors.product_id"
                        required
                    >
                        <option v-for="p in products" :key="p.id" :value="p.id">
                            {{ p.sku }} - {{ p.name }}
                        </option>
                    </AppSelect>

                    <AppSelect
                        v-model="form.warehouse_id"
                        label="Almacén"
                        placeholder="Seleccionar almacén"
                        :error="form.errors.warehouse_id"
                        required
                    >
                        <option v-for="w in warehouses" :key="w.id" :value="w.id">
                            {{ w.code }} - {{ w.name }}
                        </option>
                    </AppSelect>

                    <!-- Comparación de existencias: actual vs conteo físico -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-4 bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 rounded-xl">
                        <div>
                            <span class="text-[11px] text-slate-500 dark:text-slate-400 font-medium block">Existencia en sistema</span>
                            <span class="text-lg font-bold text-slate-900 dark:text-white">
                                {{ loadingStock ? '...' : (currentStock !== null ? `${currentStock} pzas` : '0 pzas') }}
                            </span>
                        </div>
                        <div>
                            <span class="text-[11px] text-slate-500 dark:text-slate-400 font-medium block">Conteo físico</span>
                            <span class="text-lg font-bold text-indigo-600 dark:text-indigo-400">
                                {{ form.real_quantity }} pzas
                            </span>
                        </div>
                        <div>
                            <span class="text-[11px] text-slate-500 dark:text-slate-400 font-medium block">Diferencia</span>
                            <span
                                :class="[
                                    'text-lg font-bold',
                                    difference > 0 && 'text-emerald-600 dark:text-emerald-400',
                                    difference < 0 && 'text-rose-600 dark:text-rose-400',
                                    difference === 0 && 'text-slate-500 dark:text-slate-400'
                                ]"
                            >
                                {{ difference > 0 ? `+${difference}` : difference }} pzas
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <AppInput
                            v-model="form.real_quantity"
                            type="number"
                            step="0.01"
                            min="0"
                            label="Cantidad física actual"
                            placeholder="0.00"
                            :error="form.errors.real_quantity"
                            required
                        />

                        <AppInput
                            v-model="form.reference"
                            label="Referencia (opcional)"
                            placeholder="Ej. Conteo cíclico, folio"
                            :error="form.errors.reference"
                        />
                    </div>

                    <AppSelect
                        v-model="form.reason"
                        label="Motivo"
                        :error="form.errors.reason"
                        required
                    >
                        <option value="Ajuste tras conteo físico cíclico">Conteo físico</option>
                        <option value="Diferencia de inventario anual">Inventario anual</option>
                        <option value="Corrección de captura de movimiento">Corrección de captura</option>
                        <option value="Mermas detectadas en auditoría">Merma o daño</option>
                        <option value="Sobrante no identificado">Sobrante</option>
                        <option value="Otro motivo de ajuste">Otro motivo</option>
                    </AppSelect>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Notas (opcional)</label>
                        <textarea
                            v-model="form.notes"
                            rows="2"
                            class="w-full text-sm border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 rounded-lg p-2.5 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-indigo-500"
                            placeholder="Observaciones adicionales..."
                        ></textarea>
                    </div>
                </div>
            </AppCard>

            <div class="flex items-center justify-end gap-3">
                <Link :href="route('movements.index')">
                    <AppButton variant="outline">Cancelar</AppButton>
                </Link>
                <AppButton
                    type="submit"
                    :disabled="difference === 0"
                    :loading="form.processing"
                >
                    <SlidersHorizontal class="w-4 h-4" />
                    <span>Guardar ajuste</span>
                </AppButton>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
