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

const props = defineProps<{
    users: UserOption[];
    sources: SourceOption[];
    stages: StageOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tablero', href: '/dashboard' },
    { title: 'Leads', href: '/leads' },
    { title: 'Nuevo', href: '/leads/create' },
];

const form = useForm({
    name: '',
    email: '',
    phone: '',
    company: '',
    source: 'other',
    stage: 'new',
    estimated_value: '' as string | number,
    score: '' as string | number,
    owner_id: '' as string | number,
    notes: '',
});

const submit = () => {
    form.post(route('leads.store'), { preserveScroll: true });
};
</script>

<template>
    <Head title="Nuevo lead" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-2xl p-4">
            <div class="mb-6">
                <h1 class="text-2xl font-semibold tracking-tight">Nuevo lead</h1>
                <p class="mt-1 text-sm text-muted-foreground">Registra un prospecto en el embudo de ventas.</p>
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
                    <Label for="company">Empresa</Label>
                    <Input id="company" v-model="form.company" type="text" />
                    <p v-if="form.errors.company" class="text-sm text-destructive">{{ form.errors.company }}</p>
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
                        <p v-if="form.errors.estimated_value" class="text-sm text-destructive">{{ form.errors.estimated_value }}</p>
                    </div>
                    <div class="space-y-2">
                        <Label for="score">Probabilidad (0-100)</Label>
                        <Input id="score" v-model="form.score" type="number" min="0" max="100" />
                        <p v-if="form.errors.score" class="text-sm text-destructive">{{ form.errors.score }}</p>
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="owner_id">Responsable</Label>
                    <select id="owner_id" v-model="form.owner_id" class="h-9 w-full rounded-md border border-input bg-background px-2 text-sm">
                        <option value="">Sin asignar (tú)</option>
                        <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                    </select>
                </div>

                <div class="space-y-2">
                    <Label for="notes">Notas</Label>
                    <textarea
                        id="notes"
                        v-model="form.notes"
                        rows="3"
                        class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        placeholder="Contexto, intereses, próximo paso..."
                    />
                    <p v-if="form.errors.notes" class="text-sm text-destructive">{{ form.errors.notes }}</p>
                </div>

                <div class="flex items-center justify-end gap-2">
                    <Button variant="outline" as-child>
                        <Link :href="route('leads.index')">Cancelar</Link>
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <Loader2 v-if="form.processing" class="mr-1 animate-spin" />
                        Crear lead
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
