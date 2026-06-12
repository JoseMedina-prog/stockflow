<script setup lang="ts">
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
    status?: string;
}>();

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};
</script>

<template>
    <AuthLayout title="Verifica tu correo" description="Hemos enviado un enlace de verificación a tu correo. Revisa tu bandeja de entrada.">
        <Head title="Verificar correo" />

        <div
            v-if="status === 'verification-link-sent'"
            class="mb-4 rounded-md border border-emerald-500/50 bg-emerald-500/10 px-3 py-2 text-center text-sm font-medium text-emerald-700 dark:text-emerald-300"
        >
            Se ha enviado un nuevo enlace de verificación al correo que registraste.
        </div>

        <form @submit.prevent="submit" class="space-y-5 text-center">
            <Button :disabled="form.processing" class="w-full">
                <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                Reenviar correo de verificación
            </Button>

            <TextLink :href="route('logout')" method="post" as="button" class="mx-auto block text-sm">Cerrar sesión</TextLink>
        </form>
    </AuthLayout>
</template>
