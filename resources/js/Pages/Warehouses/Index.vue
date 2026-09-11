<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppBadge from '@/Components/UI/AppBadge.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppInput from '@/Components/UI/AppInput.vue';
import AppModal from '@/Components/UI/AppModal.vue';
import ConfirmDialog from '@/Components/UI/ConfirmDialog.vue';
import { Plus, Edit2, Trash2, Warehouse, Eye, MapPin, User, Phone, Mail } from 'lucide-vue-next';

interface WarehouseItem {
    id: number;
    code: string;
    name: string;
    description?: string;
    address?: string;
    manager_name?: string;
    phone?: string;
    email?: string;
    is_active: boolean;
    products_count: number;
    total_quantity: number;
}

interface Props {
    warehouses: WarehouseItem[];
}

const props = defineProps<Props>();

const modalOpen = ref(false);
const editingWarehouse = ref<WarehouseItem | null>(null);

const form = useForm({
    code: '',
    name: '',
    description: '',
    address: '',
    manager_name: '',
    phone: '',
    email: '',
    is_active: true,
});

const openCreate = () => {
    editingWarehouse.value = null;
    form.reset();
    form.is_active = true;
    modalOpen.value = true;
};

const openEdit = (wh: WarehouseItem) => {
    editingWarehouse.value = wh;
    form.code = wh.code;
    form.name = wh.name;
    form.description = wh.description || '';
    form.address = wh.address || '';
    form.manager_name = wh.manager_name || '';
    form.phone = wh.phone || '';
    form.email = wh.email || '';
    form.is_active = wh.is_active;
    modalOpen.value = true;
};

const saveWarehouse = () => {
    if (editingWarehouse.value) {
        form.put(route('warehouses.update', editingWarehouse.value.id), {
            onSuccess: () => modalOpen.value = false,
        });
    } else {
        form.post(route('warehouses.store'), {
            onSuccess: () => modalOpen.value = false,
        });
    }
};

const deleteModal = ref(false);
const whToDelete = ref<WarehouseItem | null>(null);
const confirmDelete = (wh: WarehouseItem) => {
    whToDelete.value = wh;
    deleteModal.value = true;
};
const executeDelete = () => {
    if (!whToDelete.value) return;
    router.delete(route('warehouses.destroy', whToDelete.value.id), {
        onFinish: () => deleteModal.value = false,
    });
};
</script>

<template>
    <Head title="Almacenes" />

    <AuthenticatedLayout title="Almacenes">
        <template #actions>
            <AppButton size="sm" @click="openCreate">
                <Plus class="w-4 h-4" />
                <span>Nuevo almacén</span>
            </AppButton>
        </template>

        <div class="space-y-6">
            <!-- Cuadrícula de almacenes -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <div
                    v-for="wh in warehouses"
                    :key="wh.id"
                    class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-xs flex flex-col justify-between hover:border-slate-300 dark:hover:border-slate-700 transition-colors"
                >
                    <div>
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-10 h-10 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                                    <Warehouse class="w-5 h-5" />
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ wh.name }}</h3>
                                    <span class="text-[11px] font-mono text-slate-500 dark:text-slate-400 font-semibold">{{ wh.code }}</span>
                                </div>
                            </div>
                            <AppBadge :variant="wh.is_active ? 'green' : 'slate'" size="sm">
                                {{ wh.is_active ? 'Activo' : 'Inactivo' }}
                            </AppBadge>
                        </div>

                        <p v-if="wh.description" class="text-xs text-slate-600 dark:text-slate-300 mb-4 line-clamp-2">
                            {{ wh.description }}
                        </p>

                        <div class="space-y-1.5 text-xs text-slate-500 dark:text-slate-400 border-t border-slate-100 dark:border-slate-800 pt-3">
                            <div v-if="wh.manager_name" class="flex items-center gap-2">
                                <User class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                                <span>Responsable: <strong class="text-slate-700 dark:text-slate-200">{{ wh.manager_name }}</strong></span>
                            </div>
                            <div v-if="wh.address" class="flex items-center gap-2">
                                <MapPin class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                                <span class="truncate">{{ wh.address }}</span>
                            </div>
                            <div v-if="wh.phone" class="flex items-center gap-2">
                                <Phone class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                                <span>{{ wh.phone }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-slate-400 dark:text-slate-500 block font-medium uppercase">Existencias</span>
                            <span class="text-base font-bold text-slate-900 dark:text-white">
                                {{ wh.total_quantity }} <small class="text-xs font-normal text-slate-500 dark:text-slate-400">en {{ wh.products_count }} productos</small>
                            </span>
                        </div>

                        <div class="flex items-center gap-1">
                            <Link
                                :href="route('warehouses.show', wh.id)"
                                class="p-1.5 text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 rounded-md hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                                title="Ver existencias"
                            >
                                <Eye class="w-4 h-4" />
                            </Link>
                            <button
                                type="button"
                                class="p-1.5 text-slate-500 dark:text-slate-400 hover:text-amber-600 dark:hover:text-amber-400 rounded-md hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                                title="Editar almacén"
                                @click="openEdit(wh)"
                            >
                                <Edit2 class="w-4 h-4" />
                            </button>
                            <button
                                type="button"
                                class="p-1.5 text-slate-400 dark:text-slate-500 hover:text-rose-600 dark:hover:text-rose-400 rounded-md hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                                title="Eliminar almacén"
                                @click="confirmDelete(wh)"
                            >
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para crear o editar almacén -->
        <AppModal :show="modalOpen" :title="editingWarehouse ? 'Editar almacén' : 'Nuevo almacén'" @close="modalOpen = false">
            <form @submit.prevent="saveWarehouse" class="space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <AppInput
                        v-model="form.code"
                        label="Código"
                        placeholder="Ej. ALM-SUR"
                        :error="form.errors.code"
                        required
                    />
                    <AppInput
                        v-model="form.name"
                        label="Nombre"
                        placeholder="Ej. Bodega Sur"
                        :error="form.errors.name"
                        required
                    />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Descripción (opcional)</label>
                    <textarea v-model="form.description" rows="2" class="w-full text-sm border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 rounded-lg p-2.5 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-indigo-500"></textarea>
                </div>

                <AppInput
                    v-model="form.address"
                    label="Dirección"
                    placeholder="Calle, número, colonia, ciudad"
                    :error="form.errors.address"
                />

                <div class="grid grid-cols-2 gap-3">
                    <AppInput
                        v-model="form.manager_name"
                        label="Responsable"
                        placeholder="Nombre completo"
                        :error="form.errors.manager_name"
                    />
                    <AppInput
                        v-model="form.phone"
                        label="Teléfono"
                        placeholder="55 1234 5678"
                        :error="form.errors.phone"
                    />
                </div>

                <AppInput
                    v-model="form.email"
                    type="email"
                    label="Correo electrónico"
                    placeholder="almacen@ejemplo.com"
                    :error="form.errors.email"
                />

                <div class="flex items-center gap-2 pt-1">
                    <input id="wh_active" v-model="form.is_active" type="checkbox" class="rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500 dark:bg-slate-900" />
                    <label for="wh_active" class="text-xs font-semibold text-slate-700 dark:text-slate-300">Almacén activo</label>
                </div>

                <div class="flex justify-end gap-2 pt-3">
                    <AppButton variant="outline" size="sm" @click="modalOpen = false">Cancelar</AppButton>
                    <AppButton type="submit" size="sm" :loading="form.processing">
                        <span>{{ editingWarehouse ? 'Guardar cambios' : 'Guardar almacén' }}</span>
                    </AppButton>
                </div>
            </form>
        </AppModal>

        <!-- Diálogo de confirmación para eliminar o desactivar -->
        <ConfirmDialog
            :show="deleteModal"
            title="¿Eliminar almacén?"
            :message="`¿Estás seguro de eliminar el almacén '${whToDelete?.name}'? Si tiene existencias o movimientos, se marcará como inactivo para proteger su historial.`"
            confirm-text="Eliminar"
            cancel-text="Cancelar"
            @confirm="executeDelete"
            @cancel="deleteModal = false"
        />
    </AuthenticatedLayout>
</template>
