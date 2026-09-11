<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import AppButton from '@/Components/UI/AppButton.vue';
import AppInput from '@/Components/UI/AppInput.vue';

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Recuperar contraseña" />

        <div class="mb-6">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white tracking-tight">Recuperar contraseña</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                Escribe tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña.
            </p>
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

            <div class="pt-2">
                <AppButton type="submit" class="w-full" :loading="form.processing">
                    Enviar enlace
                </AppButton>
            </div>

            <div class="text-center text-xs text-slate-500 dark:text-slate-400 pt-2">
                <Link :href="route('login')" class="font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 transition-colors">
                    &larr; Volver a iniciar sesión
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
