<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppInput from '@/Components/UI/AppInput.vue';

defineProps<{
    canResetPassword?: boolean;
    status?: string;
}>();

const form = useForm({
    email: 'admin@ynentario.local',
    password: 'password',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => {
            form.reset('password');
        },
    });
};

const setDemoCredentials = (email: string) => {
    form.email = email;
    form.password = 'password';
};
</script>

<template>
    <GuestLayout>
        <Head title="Iniciar sesión" />

        <div class="mb-6">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">Iniciar sesión</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Ingresa con tu correo y contraseña.</p>
        </div>

        <div v-if="status" class="mb-4 text-xs font-medium text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 p-3 rounded-lg">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <AppInput
                    v-model="form.email"
                    type="email"
                    label="Correo electrónico"
                    placeholder="tu@correo.com"
                    :error="form.errors.email"
                    required
                    autofocus
                />
            </div>

            <div>
                <AppInput
                    v-model="form.password"
                    type="password"
                    label="Contraseña"
                    placeholder="••••••••"
                    :error="form.errors.password"
                    required
                />
            </div>

            <div class="flex items-center justify-between text-xs pt-1">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input
                        v-model="form.remember"
                        type="checkbox"
                        class="rounded border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-brand-600 focus:ring-brand-500"
                    />
                    <span class="text-slate-600 dark:text-slate-300">Recordarme</span>
                </label>

                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="font-medium text-brand-600 dark:text-brand-400 hover:text-brand-800 dark:hover:text-brand-300 transition-colors"
                >
                    ¿Olvidaste tu contraseña?
                </Link>
            </div>

            <div class="pt-2">
                <AppButton variant="brand" type="submit" class="w-full" :loading="form.processing">
                    Ingresar
                </AppButton>
            </div>
        </form>

        <!-- Caja de credenciales rápidas de prueba -->
        <div class="mt-6 pt-5 border-t border-slate-100 dark:border-slate-800">
            <span class="text-[11px] font-semibold text-slate-400 dark:text-slate-500 block uppercase tracking-wider mb-2 text-center">
                Cuentas de prueba (haz clic para autocompletar)
            </span>
            <div class="grid grid-cols-2 gap-2 text-xs">
                <button
                    type="button"
                    class="p-2 text-left bg-slate-50 dark:bg-slate-800/60 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 transition-colors"
                    @click="setDemoCredentials('admin@ynentario.local')"
                >
                    <span class="font-bold text-slate-800 dark:text-slate-200 block text-[11px]">Administrador</span>
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 font-mono">admin@ynentario.local</span>
                </button>

                <button
                    type="button"
                    class="p-2 text-left bg-slate-50 dark:bg-slate-800/60 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 transition-colors"
                    @click="setDemoCredentials('supervisor@ynentario.local')"
                >
                    <span class="font-bold text-slate-800 dark:text-slate-200 block text-[11px]">Supervisor</span>
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 font-mono">supervisor@ynentario...</span>
                </button>

                <button
                    type="button"
                    class="p-2 text-left bg-slate-50 dark:bg-slate-800/60 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 transition-colors"
                    @click="setDemoCredentials('operador@ynentario.local')"
                >
                    <span class="font-bold text-slate-800 dark:text-slate-200 block text-[11px]">Operador</span>
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 font-mono">operador@ynentario...</span>
                </button>

                <button
                    type="button"
                    class="p-2 text-left bg-slate-50 dark:bg-slate-800/60 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 transition-colors"
                    @click="setDemoCredentials('consulta@ynentario.local')"
                >
                    <span class="font-bold text-slate-800 dark:text-slate-200 block text-[11px]">Consulta</span>
                    <span class="text-[10px] text-slate-500 dark:text-slate-400 font-mono">consulta@ynentario...</span>
                </button>
            </div>
            <p class="text-center text-[11px] text-slate-400 dark:text-slate-500 mt-2">Contraseña: <code class="font-mono text-slate-600 dark:text-slate-300">password</code></p>
        </div>

        <div class="mt-6 text-center text-xs text-slate-500 dark:text-slate-400">
            ¿No tienes cuenta?
            <Link :href="route('register')" class="font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 ml-1 transition-colors">
                Registrarse
            </Link>
        </div>
    </GuestLayout>
</template>
