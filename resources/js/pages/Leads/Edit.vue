<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Loader2 } from 'lucide-vue-next';

interface UserOption { id: number; name: string; }
interface SourceOption { value: string; label: string; }
interface StageOption { value: string; label: string; }

interface LeadData {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    company: string | null;
    source: string;
    stage: string;
    estimated_value: number | null;
    score: number | null;
    owner_id: number | null;
    notes: string | null;
}

const props = defineProps<{
    lead: LeadData;
    users: UserOption[];
    sources: SourceOption[];
    stages: StageOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tablero', href: '/dashboard' },
    { title: 'Leads', href: '/leads' },
    { title: 'Editar', href: `/leads/${props.lead.id}/edit` },
];

const form = useForm({
    name: props.lead.name,
    email: props.lead.email ?? '',
    phone: props.lead.phone ?? '',
    company: props.lead.company ?? '',
    source: props.lead.source,
    stage: props.lead.stage,
    estimated_value: props.lead.estimated_value ?? '',
    score: props.lead.score ?? '',
    owner_id: props.lead.owner_id ?? '',
    notes: props.lead.notes ?? '',
});

const submit = () => {
    form.put(route('leads.update', props.lead.id), { preserveScroll: true });
};
</script>

<template>
    <Head title="Editar lead" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-2xl p-4">
            <div class="mb-6">
                <h1 class="text-2xl font-semibold tracking-tight">Editar lead</h1>
                <p class="mt-1 text-sm text-muted-foreground">Actualiza los datos del prospecto.</p>
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
                    </div>
                    <div class="space-y-2">
                        <Label for="phone">Teléfono</Label>
                        <Input id="phone" v-model="form.phone" type="tel" />
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="company">Empresa</Label>
                    <Input id="company" v-model="form.company" type="text" />
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="source">Origen</Label>
                        <select id="source" v-model="form.source" required class="h-9 w-full rounded-md border border-input bg-background px-2 text-sm">
                            <option v-for="s in sources" :key="s.value" :value="s.value">{{ s.label }}</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <Label for="stage">Etapa</Label>
                        <select id="stage" v-model="form.stage" required class="h-9 w-full rounded-md border border-input bg-background px-2 text-sm">
                            <option v-for="s in stages" :key="s.value" :value="s.value">{{ s.label }}</option>
                        </select>
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="estimated_value">Valor estimado (MXN)</Label>
                        <Input id="estimated_value" v-model="form.estimated_value" type="number" step="0.01" min="0" />
                    </div>
                    <div class="space-y-2">
                        <Label for="score">Probabilidad (0-100)</Label>
                        <Input id="score" v-model="form.score" type="number" min="0" max="100" />
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="owner_id">Responsable</Label>
                    <select id="owner_id" v-model="form.owner_id" class="h-9 w-full rounded-md border border-input bg-background px-2 text-sm">
                        <option value="">Sin asignar</option>
                        <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                    </select>
                </div>

                <div class="space-y-2">
                    <Label for="notes">Notas</Label>
                    <textarea id="notes" v-model="form.notes" rows="3" class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm" />
                </div>

                <div class="flex items-center justify-end gap-2">
                    <Button variant="outline" as-child>
                        <Link :href="route('leads.show', lead.id)">Cancelar</Link>
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
