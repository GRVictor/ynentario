<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppInput from '@/Components/UI/AppInput.vue';
import AppSelect from '@/Components/UI/AppSelect.vue';
import AppCard from '@/Components/UI/AppCard.vue';
import AppBadge from '@/Components/UI/AppBadge.vue';
import { ArrowLeft, ArrowUpRight, AlertTriangle } from 'lucide-vue-next';
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
    quantity: '1',
    reason: 'Venta / Envío a cliente',
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
    } catch {
        currentStock.value = 0;
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

const submit = () => {
    form.post(route('movements.exit.store'));
};
</script>

<template>
    <Head title="Registrar salida" />

    <AuthenticatedLayout
        title="Registrar salida"
        :breadcrumbs="[
            { label: 'Movimientos', href: route('movements.index') },
            { label: 'Registrar salida' }
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

                    <!-- Calculadora de existencias en vivo para la salida -->
                    <div class="p-4 rounded-xl bg-rose-50/70 dark:bg-rose-950/40 border border-rose-200/80 dark:border-rose-800 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                        <div class="flex items-center gap-2">
                            <span class="text-slate-600 dark:text-slate-300 font-medium">Disponible en almacén:</span>
                            <strong class="font-mono text-sm text-slate-900 dark:text-white">
                                {{ loadingStock ? '...' : (currentStock !== null ? `${currentStock} pzas` : '0 pzas') }}
                            </strong>
                        </div>
                        <span class="text-rose-600 dark:text-rose-400 font-bold text-base hidden sm:inline">-</span>
                        <div class="flex items-center gap-2">
                            <span class="text-slate-600 dark:text-slate-300 font-medium">Salida:</span>
                            <strong class="font-mono text-sm text-rose-600 dark:text-rose-400 font-bold">
                                -{{ Number(form.quantity || 0) }} pzas
                            </strong>
                        </div>
                        <span class="text-rose-600 dark:text-rose-400 font-bold text-base hidden sm:inline">=</span>
                        <div
                            :class="[
                                'flex items-center gap-2 p-1.5 px-3 rounded-lg border shadow-2xs',
                                (Number(form.quantity || 0) > (currentStock || 0))
                                    ? 'bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300 border-rose-400'
                                    : 'bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 border-rose-300 dark:border-rose-700'
                            ]"
                        >
                            <span class="font-semibold">Restante:</span>
                            <strong class="font-mono text-base font-bold">
                                {{ (currentStock || 0) - Number(form.quantity || 0) }} pzas
                            </strong>
                        </div>
                    </div>

                    <!-- Advertencia clara si la cantidad supera la disponible -->
                    <div
                        v-if="(currentStock !== null) && Number(form.quantity || 0) > currentStock"
                        class="p-3 rounded-xl bg-rose-100 dark:bg-rose-950/80 border border-rose-300 dark:border-rose-800 text-rose-800 dark:text-rose-200 text-xs flex items-center gap-2 font-medium"
                    >
                        <AlertTriangle class="w-4 h-4 shrink-0 text-rose-600 dark:text-rose-400" />
                        <span>No hay existencias suficientes en este almacén (máximo disponible: {{ currentStock }} pzas).</span>
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
                            placeholder="Ej. Remisión, factura o ticket"
                            :error="form.errors.reference"
                        />
                    </div>

                    <AppSelect
                        v-model="form.reason"
                        label="Motivo"
                        :error="form.errors.reason"
                        required
                    >
                        <option value="Venta / Envío a cliente">Venta o entrega a cliente</option>
                        <option value="Consumo interno departamental">Consumo interno</option>
                        <option value="Merma o producto dañado">Merma o daño</option>
                        <option value="Devolución a proveedor">Devolución a proveedor</option>
                        <option value="Caducidad o desecho">Caducidad o desecho</option>
                        <option value="Otro motivo de salida">Otro motivo</option>
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
                    variant="danger"
                    :disabled="(currentStock || 0) <= 0 || Number(form.quantity) > (currentStock || 0)"
                    :loading="form.processing"
                >
                    <ArrowUpRight class="w-4 h-4" />
                    <span>Registrar salida</span>
                </AppButton>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
