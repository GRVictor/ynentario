<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppInput from '@/Components/UI/AppInput.vue';
import AppSelect from '@/Components/UI/AppSelect.vue';
import AppCard from '@/Components/UI/AppCard.vue';
import AppBadge from '@/Components/UI/AppBadge.vue';
import { ArrowLeft, ArrowLeftRight, AlertTriangle } from 'lucide-vue-next';
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
    destination_warehouse_id: props.warehouses[1]?.id || ('' as string | number),
    quantity: '1',
    reason: 'Reabastecimiento de sucursal',
    reference: '',
    notes: '',
});

const currentStock = ref<number | null>(null);
const loadingStock = ref(false);
const destinationStock = ref<number | null>(null);
const loadingDestStock = ref(false);

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
    } catch {
        currentStock.value = 0;
    } finally {
        loadingStock.value = false;
    }
};

const fetchDestStock = async () => {
    if (!form.product_id || !form.destination_warehouse_id) return;
    loadingDestStock.value = true;
    try {
        const response = await axios.get(route('movements.stock'), {
            params: {
                product_id: form.product_id,
                warehouse_id: form.destination_warehouse_id,
            },
        });
        destinationStock.value = response.data.quantity;
    } catch {
        destinationStock.value = 0;
    } finally {
        loadingDestStock.value = false;
    }
};

watch(() => [form.product_id, form.warehouse_id], () => {
    fetchStock();
});

watch(() => [form.product_id, form.destination_warehouse_id], () => {
    fetchDestStock();
});

onMounted(() => {
    fetchStock();
    fetchDestStock();
});

const submit = () => {
    form.post(route('movements.transfer.store'));
};
</script>

<template>
    <Head title="Transferir mercancía" />

    <AuthenticatedLayout
        title="Transferir mercancía"
        :breadcrumbs="[
            { label: 'Movimientos', href: route('movements.index') },
            { label: 'Transferir mercancía' }
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
            <AppCard title="Datos del movimiento">
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

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <AppSelect
                            v-model="form.warehouse_id"
                            label="Almacén de origen"
                            placeholder="Seleccionar almacén"
                            :error="form.errors.warehouse_id"
                            required
                        >
                            <option v-for="w in warehouses" :key="w.id" :value="w.id">
                                {{ w.code }} - {{ w.name }}
                            </option>
                        </AppSelect>

                        <AppSelect
                            v-model="form.destination_warehouse_id"
                            label="Almacén de destino"
                            placeholder="Seleccionar almacén"
                            :error="form.errors.destination_warehouse_id"
                            required
                        >
                            <option v-for="w in warehouses" :key="w.id" :value="w.id">
                                {{ w.code }} - {{ w.name }}
                            </option>
                        </AppSelect>
                    </div>

                    <!-- Caja de ruta de transferencia de existencias en vivo -->
                    <div class="p-4 rounded-xl bg-sky-50/70 dark:bg-sky-950/40 border border-sky-200/80 dark:border-sky-800 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                        <div class="flex flex-col gap-0.5">
                            <span class="text-slate-600 dark:text-slate-300 font-medium">Origen (salida):</span>
                            <span class="font-mono text-sm text-slate-900 dark:text-white font-bold">
                                {{ loadingStock ? '...' : (currentStock !== null ? `${currentStock} pzas` : '0 pzas') }} &rarr;
                                <span class="text-rose-600 dark:text-rose-400 font-bold">
                                    {{ (currentStock || 0) - Number(form.quantity || 0) }} pzas
                                </span>
                            </span>
                        </div>
                        <span class="text-sky-600 dark:text-sky-400 font-bold text-lg hidden sm:inline">&rarr;</span>
                        <div class="flex flex-col gap-0.5 text-right sm:text-left">
                            <span class="text-slate-600 dark:text-slate-300 font-medium">Destino (entrada):</span>
                            <span class="font-mono text-sm text-slate-900 dark:text-white font-bold">
                                {{ loadingDestStock ? '...' : (destinationStock !== null ? `${destinationStock} pzas` : '0 pzas') }} &rarr;
                                <span class="text-emerald-600 dark:text-emerald-400 font-bold">
                                    {{ (destinationStock || 0) + Number(form.quantity || 0) }} pzas
                                </span>
                            </span>
                        </div>
                    </div>

                    <!-- Advertencia: se seleccionó el mismo almacén -->
                    <div
                        v-if="form.warehouse_id && form.destination_warehouse_id && form.warehouse_id === form.destination_warehouse_id"
                        class="p-3 rounded-xl bg-rose-100 dark:bg-rose-950/80 border border-rose-300 dark:border-rose-800 text-rose-800 dark:text-rose-200 text-xs flex items-center gap-2 font-medium"
                    >
                        <AlertTriangle class="w-4 h-4 shrink-0 text-rose-600 dark:text-rose-400" />
                        <span>El almacén de origen y de destino deben ser diferentes.</span>
                    </div>

                    <!-- Advertencia: cantidad mayor a la disponible -->
                    <div
                        v-if="(currentStock !== null) && Number(form.quantity || 0) > currentStock"
                        class="p-3 rounded-xl bg-rose-100 dark:bg-rose-950/80 border border-rose-300 dark:border-rose-800 text-rose-800 dark:text-rose-200 text-xs flex items-center gap-2 font-medium"
                    >
                        <AlertTriangle class="w-4 h-4 shrink-0 text-rose-600 dark:text-rose-400" />
                        <span>No hay existencias suficientes en el almacén de origen (máximo disponible: {{ currentStock }} pzas).</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <AppInput
                            v-model="form.quantity"
                            type="number"
                            step="0.01"
                            min="0.01"
                            :max="currentStock ?? undefined"
                            label="Cantidad"
                            placeholder="0.00"
                            :error="form.errors.quantity"
                            required
                        />

                        <AppInput
                            v-model="form.reference"
                            label="Referencia (opcional)"
                            placeholder="Ej. Guía o remisión"
                            :error="form.errors.reference"
                        />
                    </div>

                    <AppSelect
                        v-model="form.reason"
                        label="Motivo"
                        :error="form.errors.reason"
                        required
                    >
                        <option value="Reabastecimiento de sucursal">Reabastecimiento</option>
                        <option value="Balanceo de inventario entre zonas">Reubicación de inventario</option>
                        <option value="Solicitud urgente por pedido cliente">Pedido urgente</option>
                        <option value="Consolidación de almacén">Consolidación</option>
                        <option value="Otro motivo de traslado">Otro motivo</option>
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
                    :disabled="(currentStock || 0) <= 0 || Number(form.quantity) > (currentStock || 0) || form.warehouse_id === form.destination_warehouse_id"
                    :loading="form.processing"
                >
                    <ArrowLeftRight class="w-4 h-4" />
                    <span>Transferir mercancía</span>
                </AppButton>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
