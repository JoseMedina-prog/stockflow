<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Loader2 } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tablero', href: '/dashboard' },
    { title: 'Clientes', href: '/customers' },
    { title: 'Nuevo', href: '/customers/create' },
];

const form = useForm({
    name: '',
    email: '',
    phone: '',
    address: '',
});

const submit = () => {
    form.post(route('customers.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Nuevo cliente" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-2xl p-4">
            <div class="mb-6">
                <h1 class="text-2xl font-semibold tracking-tight">Nuevo cliente</h1>
                <p class="mt-1 text-sm text-muted-foreground">Registra un cliente en el sistema.</p>
            </div>

            <form @submit.prevent="submit" class="space-y-5 rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border">
                <div class="space-y-2">
                    <Label for="name">Nombre</Label>
                    <Input id="name" v-model="form.name" type="text" required autofocus />
                    <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="email">Correo</Label>
                        <Input id="email" v-model="form.email" type="email" />
                        <p v-if="form.errors.email" class="text-sm text-destructive">{{ form.errors.email }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="phone">Teléfono</Label>
                        <Input id="phone" v-model="form.phone" type="tel" />
                        <p v-if="form.errors.phone" class="text-sm text-destructive">{{ form.errors.phone }}</p>
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="address">Dirección</Label>
                    <textarea
                        id="address"
                        v-model="form.address"
                        rows="2"
                        class="flex min-h-[60px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        placeholder="Dirección opcional"
                    />
                    <p v-if="form.errors.address" class="text-sm text-destructive">{{ form.errors.address }}</p>
                </div>

                <div class="flex items-center justify-end gap-2">
                    <Button variant="outline" as-child>
                        <Link :href="route('customers.index')">Cancelar</Link>
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <Loader2 v-if="form.processing" class="mr-1 animate-spin" />
                        Guardar
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
