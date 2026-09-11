<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppInput from '@/Components/UI/AppInput.vue';
import AppSelect from '@/Components/UI/AppSelect.vue';
import AppCard from '@/Components/UI/AppCard.vue';
import { ArrowLeft, ArrowDownLeft, Calculator, RotateCcw } from 'lucide-vue-next';
import axios from 'axios';

interface ProductOption {
    id: number;
    name: string;
    sku: string;
    cost_price: number;
    selling_price: number;
    total_stock: number;
}

interface WarehouseOption {
    id: number;
    name: string;
    code: string;
}

interface ReasonOption {
    value: string;
    label: string;
    description: string;
    showPricingCalculator: boolean;
    isReturn?: boolean;
    costLabel: string;
    title: string;
    subtitle: string;
    referenceLabel: string;
    referencePlaceholder: string;
}

interface Props {
    products: ProductOption[];
    warehouses: WarehouseOption[];
}

const props = defineProps<Props>();

const reasonOptions: ReasonOption[] = [
    {
        value: 'Compra / Recepción de mercancía',
        label: 'Compra o recepción de proveedor',
        description: 'Mercancía adquirida de proveedores con costo de compra y margen para fijar precio de venta.',
        showPricingCalculator: true,
        costLabel: 'Costo unitario de compra',
        title: 'Costo de compra y margen de ganancia',
        subtitle: 'Ingresa el costo unitario de esta compra para calcular el margen y sugerir el precio de venta.',
        referenceLabel: 'Factura u orden de compra (opcional)',
        referencePlaceholder: 'Ej. Factura F-1234, Orden #89',
    },
    {
        value: 'Devolución de cliente',
        label: 'Devolución de cliente',
        description: 'Reingreso de producto vendido. Conserva el costo actual sin modificar precios del catálogo.',
        showPricingCalculator: false,
        isReturn: true,
        costLabel: 'Costo unitario actual',
        title: 'Reingreso por devolución de cliente',
        subtitle: 'El producto reingresa con su costo actual registrado.',
        referenceLabel: 'Folio de venta o ticket (opcional)',
        referencePlaceholder: 'Ej. Ticket #4501, Nota de devolución',
    },
    {
        value: 'Producción interna',
        label: 'Producción interna / Ensamble',
        description: 'Mercancía fabricada internamente con costo de materia prima e insumos.',
        showPricingCalculator: true,
        costLabel: 'Costo unitario de producción',
        title: 'Costo de producción y precio de venta',
        subtitle: 'Ingresa el costo de fabricación por unidad producida para calcular la rentabilidad.',
        referenceLabel: 'Orden de producción (opcional)',
        referencePlaceholder: 'Ej. Orden de fabricación #OP-042',
    },
    {
        value: 'Inventario inicial',
        label: 'Inventario inicial / Apertura',
        description: 'Carga inicial para registrar existencias y costos base de partida.',
        showPricingCalculator: true,
        costLabel: 'Costo unitario inicial',
        title: 'Valuación y precio de inventario inicial',
        subtitle: 'Establece el costo base unitario y el precio de venta inicial para este producto.',
        referenceLabel: 'Acta de conteo o inventario (opcional)',
        referencePlaceholder: 'Ej. Conteo físico de apertura',
    },
    {
        value: 'Otro motivo de entrada',
        label: 'Otro motivo de entrada',
        description: 'Entrada general al almacén por ajustes o transferencias especiales.',
        showPricingCalculator: true,
        costLabel: 'Costo unitario (opcional)',
        title: 'Valuación de la entrada',
        subtitle: 'Puedes especificar el costo unitario si aplica o conservar el actual.',
        referenceLabel: 'Referencia interna (opcional)',
        referencePlaceholder: 'Ej. Memorándum o folio interno',
    },
];

const urlParams = typeof window !== 'undefined' ? new URLSearchParams(window.location.search) : null;
const initialProductId = urlParams?.get('product_id')
    ? Number(urlParams.get('product_id'))
    : (props.products[0]?.id || '');

const form = useForm({
    reason: 'Compra / Recepción de mercancía',
    product_id: initialProductId as string | number,
    warehouse_id: props.warehouses[0]?.id || ('' as string | number),
    quantity: '1',
    unit_cost: '',
    margin_percentage: '30',
    new_selling_price: '',
    update_selling_price: true,
    reference: '',
    notes: '',
});

const currentStock = ref<number | null>(null);
const loadingStock = ref(false);

const currentReasonConfig = computed(() => {
    return reasonOptions.find(r => r.value === form.reason) || reasonOptions[0];
});

const selectedProduct = computed(() => {
    return props.products.find(p => p.id === Number(form.product_id));
});

const formatCurrency = (val: number): string => {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(val);
};

const syncProductPricing = (product?: ProductOption) => {
    if (!product) return;
    const cost = Number(product.cost_price || 0);
    const selling = Number(product.selling_price || 0);

    form.unit_cost = cost > 0 ? cost.toFixed(2) : '';

    if (cost > 0 && selling > cost) {
        const margin = ((selling - cost) / cost) * 100;
        form.margin_percentage = margin.toFixed(1).replace(/\.0$/, '');
        form.new_selling_price = selling.toFixed(2);
    } else if (cost > 0) {
        form.margin_percentage = '30';
        form.new_selling_price = (cost * 1.30).toFixed(2);
    } else {
        form.margin_percentage = '30';
        form.new_selling_price = selling > 0 ? selling.toFixed(2) : '';
    }
};

const handleCostOrMarginChange = () => {
    const cost = parseFloat(form.unit_cost) || 0;
    const margin = parseFloat(form.margin_percentage) || 0;
    if (cost > 0) {
        form.new_selling_price = (cost * (1 + margin / 100)).toFixed(2);
    }
};

const handleSellingPriceChange = () => {
    const cost = parseFloat(form.unit_cost) || 0;
    const selling = parseFloat(form.new_selling_price) || 0;
    if (cost > 0 && selling > 0) {
        const margin = ((selling - cost) / cost) * 100;
        form.margin_percentage = margin.toFixed(1).replace(/\.0$/, '');
    }
};

const estimatedNewAverageCost = computed(() => {
    const prod = selectedProduct.value;
    if (!prod) return null;
    const currentTotalStock = prod.total_stock || 0;
    const currentCost = prod.cost_price || 0;
    const entryQty = parseFloat(form.quantity) || 0;
    const entryCost = parseFloat(form.unit_cost) || 0;

    if (entryQty <= 0 || entryCost <= 0) return null;
    if (currentTotalStock <= 0) return entryCost;

    return ((currentTotalStock * currentCost) + (entryQty * entryCost)) / (currentTotalStock + entryQty);
});

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

watch(() => form.product_id, (newId) => {
    const prod = props.products.find(p => p.id === Number(newId));
    if (form.reason !== 'Devolución de cliente') {
        syncProductPricing(prod);
    } else if (prod) {
        form.unit_cost = prod.cost_price > 0 ? prod.cost_price.toFixed(2) : '';
    }
    fetchStock();
});

watch(() => form.warehouse_id, () => {
    fetchStock();
});

watch(() => form.reason, (newReason) => {
    if (newReason === 'Devolución de cliente') {
        form.update_selling_price = false;
        if (selectedProduct.value) {
            form.unit_cost = selectedProduct.value.cost_price > 0
                ? selectedProduct.value.cost_price.toFixed(2)
                : '';
        }
    } else if (newReason === 'Compra / Recepción de mercancía') {
        form.update_selling_price = true;
        if (selectedProduct.value) {
            syncProductPricing(selectedProduct.value);
        }
    }
});

onMounted(() => {
    if (selectedProduct.value) {
        syncProductPricing(selectedProduct.value);
    }
    fetchStock();
});

const submit = () => {
    form.post(route('movements.entry.store'));
};
</script>

<template>
    <Head title="Registrar entrada" />

    <AuthenticatedLayout
        title="Registrar entrada"
        :breadcrumbs="[
            { label: 'Movimientos', href: route('movements.index') },
            { label: 'Registrar entrada' }
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
                    <!-- Motivo de la entrada (al inicio para guiar el flujo) -->
                    <div>
                        <AppSelect
                            v-model="form.reason"
                            label="Motivo de la entrada"
                            :error="form.errors.reason"
                            required
                        >
                            <option v-for="opt in reasonOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </option>
                        </AppSelect>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                            {{ currentReasonConfig.description }}
                        </p>
                    </div>

                    <!-- Producto y Almacén de destino -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                            label="Almacén de destino"
                            placeholder="Seleccionar almacén"
                            :error="form.errors.warehouse_id"
                            required
                        >
                            <option v-for="w in warehouses" :key="w.id" :value="w.id">
                                {{ w.code }} - {{ w.name }}
                            </option>
                        </AppSelect>
                    </div>

                    <!-- Calculadora de existencias en tiempo real -->
                    <div class="p-4 rounded-xl bg-emerald-50/70 dark:bg-emerald-950/40 border border-emerald-200/80 dark:border-emerald-800 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                        <div class="flex items-center gap-2">
                            <span class="text-slate-600 dark:text-slate-300 font-medium">Existencia actual:</span>
                            <strong class="font-mono text-sm text-slate-900 dark:text-white">
                                {{ loadingStock ? '...' : (currentStock !== null ? `${currentStock} pzas` : '0 pzas') }}
                            </strong>
                        </div>
                        <span class="text-emerald-600 dark:text-emerald-400 font-bold text-base hidden sm:inline">+</span>
                        <div class="flex items-center gap-2">
                            <span class="text-slate-600 dark:text-slate-300 font-medium">Entrada:</span>
                            <strong class="font-mono text-sm text-emerald-600 dark:text-emerald-400 font-bold">
                                +{{ Number(form.quantity || 0) }} pzas
                            </strong>
                        </div>
                        <span class="text-emerald-600 dark:text-emerald-400 font-bold text-base hidden sm:inline">=</span>
                        <div class="flex items-center gap-2 p-1.5 px-3 rounded-lg bg-white dark:bg-slate-900 border border-emerald-300 dark:border-emerald-700 shadow-2xs">
                            <span class="font-semibold text-slate-700 dark:text-slate-300">Nueva existencia:</span>
                            <strong class="font-mono text-base text-emerald-600 dark:text-emerald-400 font-bold">
                                {{ (currentStock || 0) + Number(form.quantity || 0) }} pzas
                            </strong>
                        </div>
                    </div>

                    <!-- Cantidad y Referencia contextual -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <AppInput
                            v-model="form.quantity"
                            type="number"
                            step="0.01"
                            min="0.01"
                            label="Cantidad"
                            placeholder="0.00"
                            :error="form.errors.quantity"
                            required
                        />

                        <AppInput
                            v-model="form.reference"
                            :label="currentReasonConfig.referenceLabel"
                            :placeholder="currentReasonConfig.referencePlaceholder"
                            :error="form.errors.reference"
                        />
                    </div>

                    <!-- CONDICIONAL 1: Devolución de cliente -->
                    <div
                        v-if="currentReasonConfig.isReturn"
                        class="p-4 rounded-xl bg-blue-50/70 dark:bg-blue-950/40 border border-blue-200/80 dark:border-blue-900/60 flex items-start gap-3.5"
                    >
                        <div class="p-2 rounded-lg bg-blue-100 dark:bg-blue-900/60 text-blue-600 dark:text-blue-400 shrink-0 mt-0.5">
                            <RotateCcw class="w-5 h-5" />
                        </div>
                        <div class="space-y-1.5 text-xs text-blue-900 dark:text-blue-200">
                            <h5 class="font-bold text-sm text-blue-950 dark:text-blue-100">
                                Reingreso de mercancía por devolución
                            </h5>
                            <p class="text-blue-800/90 dark:text-blue-300 leading-relaxed">
                                Las piezas devueltas reingresan al inventario al <strong>costo actual registrado</strong> ({{ formatCurrency(selectedProduct?.cost_price || 0) }} c/u). 
                                No se aplican costos de compra a proveedor ni se alteran los precios de venta del catálogo.
                            </p>
                            <div class="pt-2 flex flex-wrap items-center gap-4 text-xs font-mono">
                                <div>
                                    <span class="text-blue-600 dark:text-blue-400 font-sans">Costo unitario actual: </span>
                                    <strong>{{ formatCurrency(selectedProduct?.cost_price || 0) }}</strong>
                                </div>
                                <div>
                                    <span class="text-blue-600 dark:text-blue-400 font-sans">Valor total devuelto: </span>
                                    <strong>{{ formatCurrency((Number(form.quantity) || 0) * (selectedProduct?.cost_price || 0)) }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- CONDICIONAL 2: Compra, Producción, Inventario Inicial u Otro -->
                    <div
                        v-else-if="currentReasonConfig.showPricingCalculator"
                        class="pt-4 border-t border-slate-100 dark:border-slate-800 space-y-4"
                    >
                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-2">
                                <Calculator class="w-4 h-4 text-indigo-500" />
                                {{ currentReasonConfig.title }}
                            </h4>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                {{ currentReasonConfig.subtitle }}
                            </p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <AppInput
                                    v-model="form.unit_cost"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    :label="currentReasonConfig.costLabel"
                                    placeholder="0.00"
                                    :error="form.errors.unit_cost"
                                    @input="handleCostOrMarginChange"
                                />
                                <span v-if="selectedProduct && selectedProduct.cost_price > 0" class="text-[11px] text-slate-400 mt-1 block">
                                    Costo anterior: {{ formatCurrency(selectedProduct.cost_price) }}
                                </span>
                            </div>

                            <div>
                                <AppInput
                                    v-model="form.margin_percentage"
                                    type="number"
                                    step="0.1"
                                    min="0"
                                    label="Margen deseado (%)"
                                    placeholder="30"
                                    @input="handleCostOrMarginChange"
                                />
                                <span class="text-[11px] text-slate-400 mt-1 block">
                                    Porcentaje sobre el costo
                                </span>
                            </div>

                            <div>
                                <AppInput
                                    v-model="form.new_selling_price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    label="Precio de venta sugerido"
                                    placeholder="0.00"
                                    :error="form.errors.new_selling_price"
                                    @input="handleSellingPriceChange"
                                />
                                <span v-if="selectedProduct && selectedProduct.selling_price > 0" class="text-[11px] text-slate-400 mt-1 block">
                                    Precio actual: {{ formatCurrency(selectedProduct.selling_price) }}
                                </span>
                            </div>
                        </div>

                        <!-- Casilla para actualizar precio en catálogo -->
                        <div class="flex items-center justify-between p-3.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/80">
                            <label class="flex items-center gap-2.5 cursor-pointer select-none">
                                <input
                                    type="checkbox"
                                    v-model="form.update_selling_price"
                                    class="rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500 bg-white dark:bg-slate-900"
                                />
                                <div>
                                    <span class="text-xs font-semibold text-slate-800 dark:text-slate-200">
                                        Actualizar precio de venta del producto
                                    </span>
                                    <span class="block text-[11px] text-slate-500 dark:text-slate-400">
                                        Se guardará el nuevo precio de {{ formatCurrency(parseFloat(form.new_selling_price) || 0) }} en el catálogo.
                                    </span>
                                </div>
                            </label>
                            <span class="text-xs font-bold font-mono text-indigo-600 dark:text-indigo-400 shrink-0">
                                {{ formatCurrency(parseFloat(form.new_selling_price) || 0) }}
                            </span>
                        </div>

                        <!-- Resumen del registro en tiempo real -->
                        <div
                            v-if="parseFloat(form.unit_cost) > 0"
                            class="p-4 rounded-xl bg-indigo-50/70 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-900/60 space-y-2 text-xs"
                        >
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <div>
                                    <span class="text-slate-500 dark:text-slate-400 block text-[11px]">
                                        {{ form.reason === 'Producción interna' ? 'Costo del lote' : 'Inversión total' }}
                                    </span>
                                    <strong class="font-mono text-sm text-slate-900 dark:text-white">
                                        {{ formatCurrency((parseFloat(form.quantity) || 0) * (parseFloat(form.unit_cost) || 0)) }}
                                    </strong>
                                </div>

                                <div>
                                    <span class="text-slate-500 dark:text-slate-400 block text-[11px]">Ganancia por pieza</span>
                                    <strong class="font-mono text-sm text-emerald-600 dark:text-emerald-400">
                                        +{{ formatCurrency(Math.max(0, (parseFloat(form.new_selling_price) || 0) - (parseFloat(form.unit_cost) || 0))) }}
                                    </strong>
                                </div>

                                <div>
                                    <span class="text-slate-500 dark:text-slate-400 block text-[11px]">Margen aplicado</span>
                                    <strong class="font-mono text-sm text-indigo-600 dark:text-indigo-400">
                                        {{ form.margin_percentage }}%
                                    </strong>
                                </div>

                                <div>
                                    <span class="text-slate-500 dark:text-slate-400 block text-[11px]">Nuevo costo promedio</span>
                                    <strong class="font-mono text-sm text-slate-900 dark:text-white">
                                        {{ estimatedNewAverageCost !== null ? formatCurrency(estimatedNewAverageCost) : '-' }}
                                    </strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notas -->
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
                <AppButton type="submit" :loading="form.processing">
                    <ArrowDownLeft class="w-4 h-4" />
                    <span>Registrar entrada</span>
                </AppButton>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
