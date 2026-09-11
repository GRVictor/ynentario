<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppInput from '@/Components/UI/AppInput.vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Crear cuenta" />

        <div class="mb-6">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">Crear cuenta</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Ingresa tus datos para registrarte.</p>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <AppInput
                    v-model="form.name"
                    label="Nombre"
                    placeholder="Ej. Juan Pérez"
                    :error="form.errors.name"
                    required
                    autofocus
                />
            </div>

            <div>
                <AppInput
                    v-model="form.email"
                    type="email"
                    label="Correo electrónico"
                    placeholder="tu@correo.com"
                    :error="form.errors.email"
                    required
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

            <div>
                <AppInput
                    v-model="form.password_confirmation"
                    type="password"
                    label="Confirmar contraseña"
                    placeholder="••••••••"
                    :error="form.errors.password_confirmation"
                    required
                />
            </div>

            <div class="pt-2">
                <AppButton type="submit" class="w-full" :loading="form.processing">
                    Crear cuenta
                </AppButton>
            </div>

            <div class="text-center text-xs text-slate-500 dark:text-slate-400 pt-2">
                ¿Ya tienes cuenta?
                <Link :href="route('login')" class="font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 ml-1 transition-colors">
                    Iniciar sesión
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
