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
    { title: 'Proveedores', href: '/suppliers' },
    { title: 'Nuevo', href: '/suppliers/create' },
];

const form = useForm({
    name: '',
    contact_name: '',
    email: '',
    phone: '',
    tax_id: '',
    address: '',
    notes: '',
    is_active: true as boolean,
});

const submit = () => {
    form.post(route('suppliers.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Nuevo proveedor" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-2xl p-4">
            <div class="mb-6">
                <h1 class="text-2xl font-semibold tracking-tight">Nuevo proveedor</h1>
                <p class="mt-1 text-sm text-muted-foreground">Registra un proveedor en el sistema.</p>
            </div>

            <form @submit.prevent="submit" class="space-y-5 rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border">
                <div class="space-y-2">
                    <Label for="name">Nombre / Razón social</Label>
                    <Input id="name" v-model="form.name" type="text" required autofocus />
                    <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="contact_name">Persona de contacto</Label>
                        <Input id="contact_name" v-model="form.contact_name" type="text" />
                        <p v-if="form.errors.contact_name" class="text-sm text-destructive">{{ form.errors.contact_name }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="tax_id">RFC</Label>
                        <Input id="tax_id" v-model="form.tax_id" type="text" maxlength="20" placeholder="XAXX010101000" />
                        <p v-if="form.errors.tax_id" class="text-sm text-destructive">{{ form.errors.tax_id }}</p>
                    </div>
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

                <div class="space-y-2">
                    <Label for="notes">Notas</Label>
                    <textarea
                        id="notes"
                        v-model="form.notes"
                        rows="3"
                        class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        placeholder="Notas internas (condiciones de pago, contacto alterno, etc.)"
                    />
                    <p v-if="form.errors.notes" class="text-sm text-destructive">{{ form.errors.notes }}</p>
                </div>

                <label class="flex items-center gap-2 text-sm">
                    <input
                        v-model="form.is_active"
                        type="checkbox"
                        class="size-4 rounded border-input text-primary focus:ring-1 focus:ring-ring"
                    />
                    <span>Proveedor activo</span>
                </label>

                <div class="flex items-center justify-end gap-2">
                    <Button variant="outline" as-child>
                        <Link :href="route('suppliers.index')">Cancelar</Link>
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
