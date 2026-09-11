<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppBadge from '@/Components/UI/AppBadge.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppInput from '@/Components/UI/AppInput.vue';
import AppSelect from '@/Components/UI/AppSelect.vue';
import AppModal from '@/Components/UI/AppModal.vue';
import AppPagination from '@/Components/UI/AppPagination.vue';
import ConfirmDialog from '@/Components/UI/ConfirmDialog.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import { Plus, Edit2, Trash2, Shield, User } from 'lucide-vue-next';

interface RoleOption {
    id: number;
    name: string;
    slug: string;
}

interface UserItem {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    roles: RoleOption[];
    last_login_at: string;
    created_at: string;
}

interface Props {
    users: {
        data: UserItem[];
        links: any[];
        from: number;
        to: number;
        total: number;
    };
    roles: RoleOption[];
    filters: {
        search?: string;
        role_id?: string | number;
    };
}

const props = defineProps<Props>();
const page = usePage<any>();

const search = ref(props.filters.search || '');
const roleId = ref(props.filters.role_id || '');

const applyFilters = () => {
    router.get(
        route('users.index'),
        {
            search: search.value || undefined,
            role_id: roleId.value || undefined,
        },
        { preserveState: true, replace: true }
    );
};

let searchTimeout: any = null;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 400);
});

// Estado del modal
const modalOpen = ref(false);
const editingUser = ref<UserItem | null>(null);

const form = useForm({
    name: '',
    email: '',
    password: '',
    role_id: props.roles[0]?.id || ('' as string | number),
    is_active: true,
});

const openCreate = () => {
    editingUser.value = null;
    form.reset();
    form.role_id = props.roles[0]?.id || '';
    form.is_active = true;
    modalOpen.value = true;
};

const openEdit = (user: UserItem) => {
    editingUser.value = user;
    form.name = user.name;
    form.email = user.email;
    form.password = '';
    form.role_id = user.roles[0]?.id || '';
    form.is_active = user.is_active;
    modalOpen.value = true;
};

const saveUser = () => {
    if (editingUser.value) {
        form.put(route('users.update', editingUser.value.id), {
            onSuccess: () => modalOpen.value = false,
        });
    } else {
        form.post(route('users.store'), {
            onSuccess: () => modalOpen.value = false,
        });
    }
};

const deleteModal = ref(false);
const userToDelete = ref<UserItem | null>(null);

const confirmDelete = (user: UserItem) => {
    userToDelete.value = user;
    deleteModal.value = true;
};

const executeDelete = () => {
    if (!userToDelete.value) return;
    router.delete(route('users.destroy', userToDelete.value.id), {
        onFinish: () => deleteModal.value = false,
    });
};
</script>

<template>
    <Head title="Usuarios" />

    <AuthenticatedLayout title="Usuarios">
        <template #actions>
            <AppButton size="sm" @click="openCreate">
                <Plus class="w-4 h-4" />
                <span>Nuevo usuario</span>
            </AppButton>
        </template>

        <div class="space-y-4">
            <!-- Barra de filtros -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 p-4 shadow-xs transition-colors">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <AppInput
                        v-model="search"
                        placeholder="Buscar por nombre o correo..."
                    />

                    <AppSelect
                        v-model="roleId"
                        :options="roles"
                        placeholder="Todos los roles"
                        @update:model-value="applyFilters"
                    />
                </div>
            </div>

            <!-- Tabla de usuarios -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200/80 dark:border-slate-800 shadow-xs overflow-hidden transition-colors">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/75 dark:bg-slate-800/60 text-[11px] font-semibold text-slate-500 dark:text-slate-400 uppercase">
                                <th class="py-3 px-4">Usuario</th>
                                <th class="py-3 px-4">Correo electrónico</th>
                                <th class="py-3 px-4">Rol</th>
                                <th class="py-3 px-4 text-center">Estado</th>
                                <th class="py-3 px-4 text-right">Último acceso</th>
                                <th class="py-3 px-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
                            <tr v-for="u in users.data" :key="u.id" class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition-colors">
                                <td class="py-3.5 px-4 font-semibold text-slate-900 dark:text-white">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 flex items-center justify-center font-bold text-[11px] uppercase">
                                            {{ u.name.substring(0, 2) }}
                                        </div>
                                        <span>{{ u.name }}</span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-slate-600 dark:text-slate-300 font-mono">{{ u.email }}</td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center gap-1.5 font-semibold text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800 px-2 py-0.5 rounded text-[11px]">
                                        <Shield class="w-3 h-3" />
                                        {{ u.roles[0]?.name || 'Sin rol' }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <AppBadge :variant="u.is_active ? 'green' : 'slate'" size="sm">
                                        {{ u.is_active ? 'Activo' : 'Inactivo' }}
                                    </AppBadge>
                                </td>
                                <td class="py-3.5 px-4 text-right text-slate-500 dark:text-slate-400 text-[11px] whitespace-nowrap">{{ u.last_login_at }}</td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="inline-flex items-center gap-1">
                                        <button
                                            type="button"
                                            class="p-1.5 text-slate-500 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 rounded-md hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                                            title="Editar usuario"
                                            @click="openEdit(u)"
                                        >
                                            <Edit2 class="w-4 h-4" />
                                        </button>
                                        <button
                                            v-if="u.id !== page.props.auth.user?.id"
                                            type="button"
                                            class="p-1.5 text-slate-400 dark:text-slate-500 hover:text-rose-600 dark:hover:text-rose-400 rounded-md hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                                            title="Eliminar usuario"
                                            @click="confirmDelete(u)"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="users.data.length === 0">
                                <td colspan="6" class="p-8">
                                    <EmptyState
                                        title="No encontramos usuarios"
                                        description="Prueba cambiando los términos de búsqueda."
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-slate-100 dark:border-slate-800">
                    <AppPagination
                        :links="users.links"
                        :from="users.from"
                        :to="users.to"
                        :total="users.total"
                    />
                </div>
            </div>
        </div>

        <!-- Modal para crear o editar usuario -->
        <AppModal :show="modalOpen" :title="editingUser ? 'Editar usuario' : 'Nuevo usuario'" @close="modalOpen = false">
            <form @submit.prevent="saveUser" class="space-y-4">
                <AppInput
                    v-model="form.name"
                    label="Nombre"
                    placeholder="Ej. Juan Pérez"
                    :error="form.errors.name"
                    required
                />

                <AppInput
                    v-model="form.email"
                    type="email"
                    label="Correo electrónico"
                    placeholder="correo@ejemplo.com"
                    :error="form.errors.email"
                    required
                />

                <AppInput
                    v-model="form.password"
                    type="password"
                    :label="editingUser ? 'Nueva contraseña (opcional)' : 'Contraseña'"
                    placeholder="••••••••"
                    :error="form.errors.password"
                    :required="!editingUser"
                />

                <AppSelect
                    v-model="form.role_id"
                    :options="roles"
                    label="Rol"
                    :error="form.errors.role_id"
                    required
                />

                <div class="flex items-center gap-2 pt-1">
                    <input id="user_active" v-model="form.is_active" type="checkbox" class="rounded border-slate-300 dark:border-slate-700 text-indigo-600 focus:ring-indigo-500 dark:bg-slate-900" />
                    <label for="user_active" class="text-xs font-semibold text-slate-700 dark:text-slate-300">Usuario activo</label>
                </div>
                <p v-if="form.errors.is_active" class="text-xs text-rose-600 dark:text-rose-400">{{ form.errors.is_active }}</p>

                <div class="flex justify-end gap-2 pt-3">
                    <AppButton variant="outline" size="sm" @click="modalOpen = false">Cancelar</AppButton>
                    <AppButton type="submit" size="sm" :loading="form.processing">
                        <span>{{ editingUser ? 'Guardar cambios' : 'Guardar usuario' }}</span>
                    </AppButton>
                </div>
            </form>
        </AppModal>

        <!-- Confirmación para eliminar usuario -->
        <ConfirmDialog
            :show="deleteModal"
            title="¿Eliminar usuario?"
            :message="`¿Estás seguro de eliminar a '${userToDelete?.name}'? Esta acción no se puede deshacer.`"
            confirm-text="Eliminar"
            cancel-text="Cancelar"
            @confirm="executeDelete"
            @cancel="deleteModal = false"
        />
    </AuthenticatedLayout>
</template>
