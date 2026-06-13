<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Loader2 } from 'lucide-vue-next';

interface PriorityOption {
    value: string;
    label: string;
}
interface StatusOption {
    value: string;
    label: string;
}
interface UserOption {
    id: number;
    name: string;
}
interface TaskableOption {
    type: string;
    id: number;
    label: string;
}

interface TaskData {
    id: number;
    title: string;
    description: string | null;
    due_date: string | null;
    due_time: string | null;
    priority: string;
    status: string;
    assigned_to: number;
    taskable_type: string | null;
    taskable_id: number | null;
}

const props = defineProps<{
    task: TaskData;
    users: UserOption[];
    priorities: PriorityOption[];
    statuses: StatusOption[];
    taskable_options: TaskableOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tablero', href: '/dashboard' },
    { title: 'Ventas', href: '/sales' },
    { title: 'Tareas', href: '/tasks' },
    { title: 'Editar', href: `/tasks/${props.task.id}/edit` },
];

const form = useForm({
    title: props.task.title,
    description: props.task.description ?? '',
    due_date: props.task.due_date ?? '',
    due_time: props.task.due_time ?? '',
    priority: props.task.priority,
    status: props.task.status,
    assigned_to: props.task.assigned_to,
    taskable_type: props.task.taskable_type ?? '',
    taskable_id: props.task.taskable_id ?? '',
});

const submit = () => {
    form.put(route('tasks.update', props.task.id), { preserveScroll: true });
};
</script>

<template>
    <Head title="Editar tarea" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-2xl p-4">
            <div class="mb-6">
                <h1 class="text-2xl font-semibold tracking-tight">Editar tarea</h1>
                <p class="mt-1 text-sm text-muted-foreground">Modifica los datos de la tarea.</p>
            </div>

            <form @submit.prevent="submit" class="space-y-5 rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border">
                <div class="space-y-2">
                    <Label for="title">Título</Label>
                    <Input id="title" v-model="form.title" type="text" required autofocus />
                </div>

                <div class="space-y-2">
                    <Label for="description">Descripción</Label>
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="3"
                        class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm"
                    />
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="due_date">Fecha de vencimiento</Label>
                        <Input id="due_date" v-model="form.due_date" type="date" />
                    </div>
                    <div class="space-y-2">
                        <Label for="due_time">Hora</Label>
                        <Input id="due_time" v-model="form.due_time" type="time" />
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-3">
                    <div class="space-y-2">
                        <Label for="priority">Prioridad</Label>
                        <select
                            id="priority"
                            v-model="form.priority"
                            required
                            class="h-9 w-full rounded-md border border-input bg-background px-2 text-sm"
                        >
                            <option v-for="p in priorities" :key="p.value" :value="p.value">{{ p.label }}</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <Label for="status">Estado</Label>
                        <select
                            id="status"
                            v-model="form.status"
                            required
                            class="h-9 w-full rounded-md border border-input bg-background px-2 text-sm"
                        >
                            <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <Label for="assigned_to">Asignado a</Label>
                        <select
                            id="assigned_to"
                            v-model="form.assigned_to"
                            required
                            class="h-9 w-full rounded-md border border-input bg-background px-2 text-sm"
                        >
                            <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                        </select>
                    </div>
                </div>

                <div class="rounded-lg border border-border/60 bg-muted/20 p-3">
                    <p class="mb-2 text-xs font-medium text-muted-foreground">Relacionar con (opcional)</p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="taskable_type">Tipo</Label>
                            <select
                                id="taskable_type"
                                v-model="form.taskable_type"
                                class="h-9 w-full rounded-md border border-input bg-background px-2 text-sm"
                            >
                                <option value="">Sin relación</option>
                                <option value="customer">Cliente</option>
                                <option value="lead">Prospecto</option>
                                <option value="opportunity">Oportunidad</option>
                                <option value="sale">Venta</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <Label for="taskable_id">ID</Label>
                            <Input id="taskable_id" v-model="form.taskable_id" type="number" min="1" />
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2">
                    <Button variant="outline" as-child>
                        <Link :href="route('tasks.show', task.id)">Cancelar</Link>
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
