<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppBadge from '@/Components/UI/AppBadge.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppInput from '@/Components/UI/AppInput.vue';
import AppModal from '@/Components/UI/AppModal.vue';
import ConfirmDialog from '@/Components/UI/ConfirmDialog.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import { Plus, Edit2, Trash2, Tag, Award, Ruler, Truck } from 'lucide-vue-next';

interface CategoryItem {
    id: number;
    name: string;
    description?: string;
    is_active: boolean;
    products_count: number;
}

interface BrandItem {
    id: number;
    name: string;
    description?: string;
    is_active: boolean;
    products_count: number;
}

interface UnitItem {
    id: number;
    name: string;
    abbreviation: string;
    products_count: number;
}

interface SupplierItem {
    id: number;
    code?: string;
    name: string;
    contact_name?: string;
    email?: string;
    phone?: string;
    address?: string;
    is_active: boolean;
    products_count: number;
}

interface Props {
    activeTab: 'categories' | 'brands' | 'units' | 'suppliers';
    categories: CategoryItem[];
    brands: BrandItem[];
    units: UnitItem[];
    suppliers: SupplierItem[];
}

const props = defineProps<Props>();
const currentTab = ref(props.activeTab || 'categories');

// Estados de los modales
const categoryModal = ref(false);
const editingCategory = ref<CategoryItem | null>(null);
const categoryForm = useForm({
    name: '',
    description: '',
    is_active: true,
});

const brandModal = ref(false);
const editingBrand = ref<BrandItem | null>(null);
const brandForm = useForm({
    name: '',
    description: '',
    is_active: true,
});

const unitModal = ref(false);
const editingUnit = ref<UnitItem | null>(null);
const unitForm = useForm({
    name: '',
    abbreviation: '',
});

const supplierModal = ref(false);
const editingSupplier = ref<SupplierItem | null>(null);
const supplierForm = useForm({
    code: '',
    name: '',
    contact_name: '',
    email: '',
    phone: '',
    address: '',
    is_active: true,
});

// Manejadores para categorías
const openCategoryCreate = () => {
    editingCategory.value = null;
    categoryForm.reset();
    categoryModal.value = true;
};
const openCategoryEdit = (cat: CategoryItem) => {
    editingCategory.value = cat;
    categoryForm.name = cat.name;
    categoryForm.description = cat.description || '';
    categoryForm.is_active = cat.is_active;
    categoryModal.value = true;
};
const saveCategory = () => {
    if (editingCategory.value) {
        categoryForm.put(route('catalogs.categories.update', editingCategory.value.id), {
            onSuccess: () => categoryModal.value = false,
        });
    } else {
        categoryForm.post(route('catalogs.categories.store'), {
            onSuccess: () => categoryModal.value = false,
        });
    }
};

// Manejadores para marcas
const openBrandCreate = () => {
    editingBrand.value = null;
    brandForm.reset();
    brandModal.value = true;
};
const openBrandEdit = (b: BrandItem) => {
    editingBrand.value = b;
    brandForm.name = b.name;
    brandForm.description = b.description || '';
    brandForm.is_active = b.is_active;
    brandModal.value = true;
};
const saveBrand = () => {
    if (editingBrand.value) {
        brandForm.put(route('catalogs.brands.update', editingBrand.value.id), {
            onSuccess: () => brandModal.value = false,
        });
    } else {
        brandForm.post(route('catalogs.brands.store'), {
            onSuccess: () => brandModal.value = false,
        });
    }
};

// Manejadores para unidades de medida
const openUnitCreate = () => {
    editingUnit.value = null;
    unitForm.reset();
    unitModal.value = true;
};
const openUnitEdit = (u: UnitItem) => {
    editingUnit.value = u;
    unitForm.name = u.name;
    unitForm.abbreviation = u.abbreviation;
    unitModal.value = true;
};
const saveUnit = () => {
    if (editingUnit.value) {
        unitForm.put(route('catalogs.units.update', editingUnit.value.id), {
            onSuccess: () => unitModal.value = false,
        });
    } else {
        unitForm.post(route('catalogs.units.store'), {
            onSuccess: () => unitModal.value = false,
        });
    }
};

// Manejadores para proveedores
const openSupplierCreate = () => {
    editingSupplier.value = null;
    supplierForm.reset();
    supplierModal.value = true;
};
const openSupplierEdit = (s: SupplierItem) => {
    editingSupplier.value = s;
    supplierForm.code = s.code || '';
    supplierForm.name = s.name;
    supplierForm.contact_name = s.contact_name || '';
    supplierForm.email = s.email || '';
    supplierForm.phone = s.phone || '';
    supplierForm.address = s.address || '';
    supplierForm.is_active = s.is_active;
    supplierModal.value = true;
};
const saveSupplier = () => {
    if (editingSupplier.value) {
        supplierForm.put(route('catalogs.suppliers.update', editingSupplier.value.id), {
            onSuccess: () => supplierModal.value = false,
        });
    } else {
        supplierForm.post(route('catalogs.suppliers.store'), {
            onSuccess: () => supplierModal.value = false,
        });
    }
};

// Manejador genérico de eliminación
const deleteModal = ref(false);
const deleteUrl = ref('');
const deleteTitle = ref('');
const deleteMessage = ref('');
const confirmDelete = (url: string, title: string, message: string) => {
    deleteUrl.value = url;
    deleteTitle.value = title;
    deleteMessage.value = message;
    deleteModal.value = true;
};
const executeDelete = () => {
    router.delete(deleteUrl.value, {
        onFinish: () => deleteModal.value = false,
    });
};
</script>

<template>
    <Head title="Catálogos" />

    <AuthenticatedLayout title="Catálogos">
        <template #actions>
            <AppButton v-if="currentTab === 'categories'" size="sm" @click="openCategoryCreate">
                <Plus class="w-4 h-4" />
                <span>Nueva categoría</span>
            </AppButton>
            <AppButton v-else-if="currentTab === 'brands'" size="sm" @click="openBrandCreate">
                <Plus class="w-4 h-4" />
                <span>Nueva marca</span>
            </AppButton>
            <AppButton v-else-if="currentTab === 'units'" size="sm" @click="openUnitCreate">
                <Plus class="w-4 h-4" />
                <span>Nueva unidad</span>
            </AppButton>
            <AppButton v-else-if="currentTab === 'suppliers'" size="sm" @click="openSupplierCreate">
                <Plus class="w-4 h-4" />
                <span>Nuevo proveedor</span>
            </AppButton>
        </template>

        <div class="space-y-6">
            <!-- Barra de pestañas de navegación -->
            <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-3 text-xs font-semibold overflow-x-auto">
                <button
                    type="button"
                    @click="currentTab = 'categories'"
                    :class="[
                        'px-4 py-2 rounded-lg transition-colors flex items-center gap-2 shrink-0 text-xs font-semibold',
                        currentTab === 'categories'
                            ? 'bg-indigo-600 text-white shadow-xs'
                            : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white',
                    ]"
                >
                    <Tag class="w-4 h-4" />
                    <span>Categorías ({{ categories.length }})</span>
                </button>

                <button
                    type="button"
                    @click="currentTab = 'brands'"
                    :class="[
                        'px-4 py-2 rounded-lg transition-colors flex items-center gap-2 shrink-0 text-xs font-semibold',
                        currentTab === 'brands'
                            ? 'bg-indigo-600 text-white shadow-xs'
                            : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white',
                    ]"
                >
                    <Award class="w-4 h-4" />
                    <span>Marcas ({{ brands.length }})</span>
                </button>

                <button
                    type="button"
                    @click="currentTab = 'units'"
                    :class="[
                        'px-4 py-2 rounded-lg transition-colors flex items-center gap-2 shrink-0 text-xs font-semibold',
                        currentTab === 'units'
                            ? 'bg-indigo-600 text-white shadow-xs'
                            : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white',
                    ]"
                >
                    <Ruler class="w-4 h-4" />
                    <span>Unidades de medida ({{ units.length }})</span>
                </button>

                <button
                    type="button"
                    @click="currentTab = 'suppliers'"
                    :class="[
                        'px-4 py-2 rounded-lg transition-colors flex items-center gap-2 shrink-0 text-xs font-semibold',
                        currentTab === 'suppliers'
                            ? 'bg-indigo-600 text-white shadow-xs'
                            : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white',
                    ]"
                >
                    <Truck class="w-4 h-4" />
                    <span>Proveedores ({{ suppliers.length }})</span>
                </button>
            </div>

            <!-- Pestaña 1: Tabla de categorías -->
            <div v-if="currentTab === 'categories'" class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden transition-colors">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/60 text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase">
                            <th class="py-3 px-4">Nombre</th>
                            <th class="py-3 px-4">Descripción</th>
                            <th class="py-3 px-4 text-center">Productos</th>
                            <th class="py-3 px-4 text-center">Estado</th>
                            <th class="py-3 px-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                        <tr v-for="cat in categories" :key="cat.id" class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition-colors">
                            <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">{{ cat.name }}</td>
                            <td class="py-3 px-4 text-slate-500 dark:text-slate-400">{{ cat.description || 'Sin descripción' }}</td>
                            <td class="py-3 px-4 text-center font-bold text-slate-800 dark:text-slate-200">{{ cat.products_count }}</td>
                            <td class="py-3 px-4 text-center">
                                <AppBadge :variant="cat.is_active ? 'green' : 'slate'" size="sm">
                                    {{ cat.is_active ? 'Activa' : 'Inactiva' }}
                                </AppBadge>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <div class="inline-flex items-center gap-1">
                                    <button class="p-1.5 text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-md transition-colors" @click="openCategoryEdit(cat)">
                                        <Edit2 class="w-4 h-4" />
                                    </button>
                                    <button class="p-1.5 text-slate-400 dark:text-slate-500 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-md transition-colors" @click="confirmDelete(route('catalogs.categories.destroy', cat.id), '¿Eliminar categoría?', `¿Deseas eliminar la categoría '${cat.name}'? Si tiene productos asociados se marcará como inactiva.`)">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pestaña 2: Tabla de marcas -->
            <div v-if="currentTab === 'brands'" class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden transition-colors">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/60 text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase">
                            <th class="py-3 px-4">Nombre</th>
                            <th class="py-3 px-4">Descripción</th>
                            <th class="py-3 px-4 text-center">Productos</th>
                            <th class="py-3 px-4 text-center">Estado</th>
                            <th class="py-3 px-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                        <tr v-for="brand in brands" :key="brand.id" class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition-colors">
                            <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">{{ brand.name }}</td>
                            <td class="py-3 px-4 text-slate-500 dark:text-slate-400">{{ brand.description || 'Sin descripción' }}</td>
                            <td class="py-3 px-4 text-center font-bold text-slate-800 dark:text-slate-200">{{ brand.products_count }}</td>
                            <td class="py-3 px-4 text-center">
                                <AppBadge :variant="brand.is_active ? 'green' : 'slate'" size="sm">
                                    {{ brand.is_active ? 'Activa' : 'Inactiva' }}
                                </AppBadge>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <div class="inline-flex items-center gap-1">
                                    <button class="p-1.5 text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-md transition-colors" @click="openBrandEdit(brand)">
                                        <Edit2 class="w-4 h-4" />
                                    </button>
                                    <button class="p-1.5 text-slate-400 dark:text-slate-500 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-md transition-colors" @click="confirmDelete(route('catalogs.brands.destroy', brand.id), '¿Eliminar marca?', `¿Deseas eliminar la marca '${brand.name}'? Si tiene productos asociados se marcará como inactiva.`)">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pestaña 3: Tabla de unidades de medida -->
            <div v-if="currentTab === 'units'" class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden transition-colors">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/60 text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase">
                            <th class="py-3 px-4">Nombre</th>
                            <th class="py-3 px-4 font-mono">Abreviatura</th>
                            <th class="py-3 px-4 text-center">Productos</th>
                            <th class="py-3 px-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                        <tr v-for="u in units" :key="u.id" class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition-colors">
                            <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">{{ u.name }}</td>
                            <td class="py-3 px-4 font-mono font-bold text-slate-700 dark:text-slate-300">{{ u.abbreviation }}</td>
                            <td class="py-3 px-4 text-center font-bold text-slate-800 dark:text-slate-200">{{ u.products_count }}</td>
                            <td class="py-3 px-4 text-right">
                                <div class="inline-flex items-center gap-1">
                                    <button class="p-1.5 text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-md transition-colors" @click="openUnitEdit(u)">
                                        <Edit2 class="w-4 h-4" />
                                    </button>
                                    <button class="p-1.5 text-slate-400 dark:text-slate-500 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-md transition-colors" @click="confirmDelete(route('catalogs.units.destroy', u.id), '¿Eliminar unidad?', `¿Deseas eliminar la unidad '${u.name}'?`)">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pestaña 4: Tabla de proveedores -->
            <div v-if="currentTab === 'suppliers'" class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden transition-colors">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/60 text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase">
                            <th class="py-3 px-4">Proveedor</th>
                            <th class="py-3 px-4">Contacto</th>
                            <th class="py-3 px-4">Correo / Teléfono</th>
                            <th class="py-3 px-4 text-center">Productos</th>
                            <th class="py-3 px-4 text-center">Estado</th>
                            <th class="py-3 px-4 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                        <tr v-for="s in suppliers" :key="s.id" class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition-colors">
                            <td class="py-3 px-4">
                                <div class="font-semibold text-slate-900 dark:text-white">{{ s.name }}</div>
                                <span v-if="s.code" class="text-[10px] font-mono text-slate-400 dark:text-slate-500">{{ s.code }}</span>
                            </td>
                            <td class="py-3 px-4 text-slate-600 dark:text-slate-300">{{ s.contact_name || 'Sin contacto' }}</td>
                            <td class="py-3 px-4 text-slate-600 dark:text-slate-300">
                                <div>{{ s.email || 'Sin correo' }}</div>
                                <span class="text-[10px] text-slate-400 dark:text-slate-500 font-mono">{{ s.phone }}</span>
                            </td>
                            <td class="py-3 px-4 text-center font-bold text-slate-800 dark:text-slate-200">{{ s.products_count }}</td>
                            <td class="py-3 px-4 text-center">
                                <AppBadge :variant="s.is_active ? 'green' : 'slate'" size="sm">
                                    {{ s.is_active ? 'Activo' : 'Inactivo' }}
                                </AppBadge>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <div class="inline-flex items-center gap-1">
                                    <button class="p-1.5 text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-md transition-colors" @click="openSupplierEdit(s)">
                                        <Edit2 class="w-4 h-4" />
                                    </button>
                                    <button class="p-1.5 text-slate-400 dark:text-slate-500 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-md transition-colors" @click="confirmDelete(route('catalogs.suppliers.destroy', s.id), '¿Eliminar proveedor?', `¿Deseas eliminar al proveedor '${s.name}'? Si tiene productos asociados se marcará como inactivo.`)">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal de categoría -->
        <AppModal :show="categoryModal" :title="editingCategory ? 'Editar categoría' : 'Nueva categoría'" @close="categoryModal = false">
            <form @submit.prevent="saveCategory" class="space-y-4">
                <AppInput v-model="categoryForm.name" label="Nombre" placeholder="Ej. Herramientas" :error="categoryForm.errors.name" required />
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Descripción (opcional)</label>
                    <textarea v-model="categoryForm.description" rows="2" class="w-full text-sm border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 rounded-lg p-2.5 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-indigo-500"></textarea>
                </div>
                <div class="flex items-center gap-2">
                    <input id="cat_active" v-model="categoryForm.is_active" type="checkbox" class="rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500 dark:bg-slate-900" />
                    <label for="cat_active" class="text-xs font-semibold text-slate-700 dark:text-slate-300">Categoría activa</label>
                </div>
                <div class="flex justify-end gap-2 pt-3">
                    <AppButton variant="outline" size="sm" @click="categoryModal = false">Cancelar</AppButton>
                    <AppButton type="submit" size="sm" :loading="categoryForm.processing">
                        <span>{{ editingCategory ? 'Guardar cambios' : 'Guardar categoría' }}</span>
                    </AppButton>
                </div>
            </form>
        </AppModal>

        <!-- Modal de marca -->
        <AppModal :show="brandModal" :title="editingBrand ? 'Editar marca' : 'Nueva marca'" @close="brandModal = false">
            <form @submit.prevent="saveBrand" class="space-y-4">
                <AppInput v-model="brandForm.name" label="Nombre" placeholder="Ej. Truper" :error="brandForm.errors.name" required />
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Descripción (opcional)</label>
                    <textarea v-model="brandForm.description" rows="2" class="w-full text-sm border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 rounded-lg p-2.5 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-indigo-500"></textarea>
                </div>
                <div class="flex items-center gap-2">
                    <input id="brand_active" v-model="brandForm.is_active" type="checkbox" class="rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500 dark:bg-slate-900" />
                    <label for="brand_active" class="text-xs font-semibold text-slate-700 dark:text-slate-300">Marca activa</label>
                </div>
                <div class="flex justify-end gap-2 pt-3">
                    <AppButton variant="outline" size="sm" @click="brandModal = false">Cancelar</AppButton>
                    <AppButton type="submit" size="sm" :loading="brandForm.processing">
                        <span>{{ editingBrand ? 'Guardar cambios' : 'Guardar marca' }}</span>
                    </AppButton>
                </div>
            </form>
        </AppModal>

        <!-- Modal de unidad -->
        <AppModal :show="unitModal" :title="editingUnit ? 'Editar unidad' : 'Nueva unidad'" @close="unitModal = false">
            <form @submit.prevent="saveUnit" class="space-y-4">
                <AppInput v-model="unitForm.name" label="Nombre" placeholder="Ej. Pieza, Caja, Kilogramo" :error="unitForm.errors.name" required />
                <AppInput v-model="unitForm.abbreviation" label="Abreviatura" placeholder="Ej. pza, cja, kg" :error="unitForm.errors.abbreviation" required />
                <div class="flex justify-end gap-2 pt-3">
                    <AppButton variant="outline" size="sm" @click="unitModal = false">Cancelar</AppButton>
                    <AppButton type="submit" size="sm" :loading="unitForm.processing">
                        <span>{{ editingUnit ? 'Guardar cambios' : 'Guardar unidad' }}</span>
                    </AppButton>
                </div>
            </form>
        </AppModal>

        <!-- Modal de proveedor -->
        <AppModal :show="supplierModal" :title="editingSupplier ? 'Editar proveedor' : 'Nuevo proveedor'" @close="supplierModal = false">
            <form @submit.prevent="saveSupplier" class="space-y-4">
                <AppInput v-model="supplierForm.name" label="Nombre o razón social" placeholder="Ej. Distribuidora Central S.A." :error="supplierForm.errors.name" required />
                <div class="grid grid-cols-2 gap-3">
                    <AppInput v-model="supplierForm.code" label="Código" placeholder="Ej. PROV-001" :error="supplierForm.errors.code" />
                    <AppInput v-model="supplierForm.contact_name" label="Contacto" placeholder="Nombre de la persona" :error="supplierForm.errors.contact_name" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <AppInput v-model="supplierForm.email" type="email" label="Correo electrónico" placeholder="contacto@proveedor.com" :error="supplierForm.errors.email" />
                    <AppInput v-model="supplierForm.phone" label="Teléfono" placeholder="55 1234 5678" :error="supplierForm.errors.phone" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Dirección (opcional)</label>
                    <textarea v-model="supplierForm.address" rows="2" class="w-full text-sm border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 rounded-lg p-2.5 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:ring-2 focus:ring-indigo-500"></textarea>
                </div>
                <div class="flex items-center gap-2">
                    <input id="supp_active" v-model="supplierForm.is_active" type="checkbox" class="rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500 dark:bg-slate-900" />
                    <label for="supp_active" class="text-xs font-semibold text-slate-700 dark:text-slate-300">Proveedor activo</label>
                </div>
                <div class="flex justify-end gap-2 pt-3">
                    <AppButton variant="outline" size="sm" @click="supplierModal = false">Cancelar</AppButton>
                    <AppButton type="submit" size="sm" :loading="supplierForm.processing">
                        <span>{{ editingSupplier ? 'Guardar cambios' : 'Guardar proveedor' }}</span>
                    </AppButton>
                </div>
            </form>
        </AppModal>

        <!-- Diálogo de confirmación de eliminación -->
        <ConfirmDialog
            :show="deleteModal"
            :title="deleteTitle"
            :message="deleteMessage"
            confirm-text="Eliminar"
            cancel-text="Cancelar"
            @confirm="executeDelete"
            @cancel="deleteModal = false"
        />
    </AuthenticatedLayout>
</template>
