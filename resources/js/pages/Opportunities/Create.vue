<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Loader2 } from 'lucide-vue-next';

interface StageOption {
    value: string;
    label: string;
}
interface CustomerOption {
    id: number;
    name: string;
}
interface UserOption {
    id: number;
    name: string;
}

const props = defineProps<{
    stages: StageOption[];
    customers: CustomerOption[];
    users: UserOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tablero', href: '/dashboard' },
    { title: 'Oportunidades', href: '/opportunities' },
    { title: 'Nueva', href: '/opportunities/create' },
];

const form = useForm({
    name: '',
    customer_id: '' as string | number,
    lead_id: '' as string | number,
    owner_id: '' as string | number,
    stage: 'prospecting',
    amount: '' as string | number,
    probability: 20,
    expected_close_date: '' as string,
    notes: '',
});

const submit = () => {
    form.post(route('opportunities.store'), { preserveScroll: true });
};
</script>

<template>
    <Head title="Nueva oportunidad" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-2xl p-4">
            <div class="mb-6">
                <h1 class="text-2xl font-semibold tracking-tight">Nueva oportunidad</h1>
                <p class="mt-1 text-sm text-muted-foreground">Registra una nueva oportunidad en el pipeline de ventas.</p>
            </div>

            <form @submit.prevent="submit" class="space-y-5 rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border">
                <div class="space-y-2">
                    <Label for="name">Nombre de la oportunidad</Label>
                    <Input id="name" v-model="form.name" type="text" required autofocus placeholder="Ej: Renovación de contrato ACME" />
                    <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="customer_id">Cliente</Label>
                        <select
                            id="customer_id"
                            v-model="form.customer_id"
                            class="h-9 w-full rounded-md border border-input bg-background px-2 text-sm"
                        >
                            <option value="">Sin cliente (oportunidad abierta)</option>
                            <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <Label for="owner_id">Responsable</Label>
                        <select id="owner_id" v-model="form.owner_id" class="h-9 w-full rounded-md border border-input bg-background px-2 text-sm">
                            <option value="">Yo (sin asignar)</option>
                            <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                        </select>
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-3">
                    <div class="space-y-2">
                        <Label for="stage">Etapa</Label>
                        <select id="stage" v-model="form.stage" required class="h-9 w-full rounded-md border border-input bg-background px-2 text-sm">
                            <option v-for="s in stages" :key="s.value" :value="s.value">{{ s.label }}</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <Label for="amount">Monto (MXN)</Label>
                        <Input id="amount" v-model="form.amount" type="number" step="0.01" min="0" required />
                        <p v-if="form.errors.amount" class="text-sm text-destructive">{{ form.errors.amount }}</p>
                    </div>
                    <div class="space-y-2">
                        <Label for="probability">Probabilidad %</Label>
                        <Input id="probability" v-model.number="form.probability" type="number" min="0" max="100" required />
                        <p v-if="form.errors.probability" class="text-sm text-destructive">{{ form.errors.probability }}</p>
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="expected_close_date">Fecha estimada de cierre</Label>
                    <Input id="expected_close_date" v-model="form.expected_close_date" type="date" />
                </div>

                <div class="space-y-2">
                    <Label for="notes">Notas</Label>
                    <textarea
                        id="notes"
                        v-model="form.notes"
                        rows="3"
                        class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        placeholder="Contexto, próximos pasos, competidores..."
                    />
                </div>

                <div class="flex items-center justify-end gap-2">
                    <Button variant="outline" as-child>
                        <Link :href="route('opportunities.index')">Cancelar</Link>
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <Loader2 v-if="form.processing" class="mr-1 animate-spin" />
                        Crear oportunidad
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
