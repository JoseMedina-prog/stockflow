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

interface OppData {
    id: number;
    name: string;
    customer_id: number | null;
    lead_id: number | null;
    owner_id: number | null;
    stage: string;
    amount: number;
    probability: number;
    expected_close_date: string | null;
    notes: string | null;
}

const props = defineProps<{
    opportunity: OppData;
    stages: StageOption[];
    customers: CustomerOption[];
    users: UserOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tablero', href: '/dashboard' },
    { title: 'Oportunidades', href: '/opportunities' },
    { title: 'Editar', href: `/opportunities/${props.opportunity.id}/edit` },
];

const form = useForm({
    name: props.opportunity.name,
    customer_id: props.opportunity.customer_id ?? '',
    lead_id: props.opportunity.lead_id ?? '',
    owner_id: props.opportunity.owner_id ?? '',
    stage: props.opportunity.stage,
    amount: props.opportunity.amount,
    probability: props.opportunity.probability,
    expected_close_date: props.opportunity.expected_close_date ?? '',
    notes: props.opportunity.notes ?? '',
});

const submit = () => {
    form.put(route('opportunities.update', props.opportunity.id), { preserveScroll: true });
};
</script>

<template>
    <Head title="Editar oportunidad" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-2xl p-4">
            <div class="mb-6">
                <h1 class="text-2xl font-semibold tracking-tight">Editar oportunidad</h1>
                <p class="mt-1 text-sm text-muted-foreground">Actualiza los datos de la oportunidad.</p>
            </div>

            <form @submit.prevent="submit" class="space-y-5 rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border">
                <div class="space-y-2">
                    <Label for="name">Nombre</Label>
                    <Input id="name" v-model="form.name" type="text" required />
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="customer_id">Cliente</Label>
                        <select
                            id="customer_id"
                            v-model="form.customer_id"
                            class="h-9 w-full rounded-md border border-input bg-background px-2 text-sm"
                        >
                            <option value="">Sin cliente</option>
                            <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <Label for="owner_id">Responsable</Label>
                        <select id="owner_id" v-model="form.owner_id" class="h-9 w-full rounded-md border border-input bg-background px-2 text-sm">
                            <option value="">Sin asignar</option>
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
                    </div>
                    <div class="space-y-2">
                        <Label for="probability">Probabilidad %</Label>
                        <Input id="probability" v-model.number="form.probability" type="number" min="0" max="100" required />
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
                        class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm"
                    />
                </div>

                <div class="flex items-center justify-end gap-2">
                    <Button variant="outline" as-child>
                        <Link :href="route('opportunities.show', opportunity.id)">Cancelar</Link>
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <Loader2 v-if="form.processing" class="mr-1 animate-spin" />
                        Actualizar
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
