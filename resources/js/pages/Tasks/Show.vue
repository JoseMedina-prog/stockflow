<script setup lang="ts">
import PageHeader from '@/components/stockflow/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { formatDateTime } from '@/composables/useFormat';
import { usePermissions } from '@/composables/usePermissions';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Check, CheckCircle, Link2, Pencil, RotateCcw } from 'lucide-vue-next';

interface TaskData {
    id: number;
    title: string;
    description: string | null;
    due_date: string | null;
    due_time: string | null;
    priority: string;
    priority_label: string;
    priority_badge: string;
    status: string;
    status_label: string;
    status_badge: string;
    is_overdue: boolean;
    is_due_today: boolean;
    is_open: boolean;
    is_final: boolean;
    completed_at: string | null;
    assignee: { id: number; name: string } | null;
    creator: { id: number; name: string } | null;
    completer: { id: number; name: string } | null;
    taskable_type: string | null;
    taskable_href: string | null;
    taskable_label: string | null;
    created_at: string;
}

const props = defineProps<{ task: TaskData }>();

const { can } = usePermissions();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tablero', href: '/dashboard' },
    { title: 'Ventas', href: '/sales' },
    { title: 'Tareas', href: '/tasks' },
    { title: props.task.title, href: `/tasks/${props.task.id}` },
];

const toggleComplete = () => {
    router.post(route('tasks.complete', props.task.id), {}, { preserveScroll: true });
};
</script>

<template>
    <Head :title="`Tarea · ${task.title}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <PageHeader :title="task.title" :description="`Creada el ${formatDateTime(task.created_at)}.`">
                    <template #actions>
                        <Button variant="outline" as-child>
                            <Link :href="route('tasks.index')">
                                <ArrowLeft class="mr-1" />
                                Volver
                            </Link>
                        </Button>
                        <Button v-if="can('tasks.update')" variant="outline" as-child>
                            <Link :href="route('tasks.edit', task.id)">
                                <Pencil class="mr-1" />
                                Editar
                            </Link>
                        </Button>
                        <Button v-if="can('tasks.update')" :variant="task.status === 'completed' ? 'outline' : 'default'" @click="toggleComplete">
                            <RotateCcw v-if="task.status === 'completed'" class="mr-1" />
                            <Check v-else class="mr-1" />
                            {{ task.status === 'completed' ? 'Reabrir' : 'Completar' }}
                        </Button>
                    </template>
                </PageHeader>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <Card class="lg:col-span-1">
                    <CardHeader>
                        <CardTitle class="text-base">Estado</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3 text-sm">
                        <div>
                            <p class="text-xs text-muted-foreground">Estado</p>
                            <Badge :variant="task.status_badge as any" class="mt-1">{{ task.status_label }}</Badge>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Prioridad</p>
                            <Badge :variant="task.priority_badge as any" class="mt-1">{{ task.priority_label }}</Badge>
                        </div>
                        <Separator />
                        <div v-if="task.due_date">
                            <p class="text-xs text-muted-foreground">Vencimiento</p>
                            <p class="font-medium" :class="task.is_overdue ? 'text-destructive' : ''">
                                {{ task.due_date }}<span v-if="task.due_time"> · {{ task.due_time }}</span>
                            </p>
                            <p v-if="task.is_overdue" class="text-xs text-destructive">Vencida</p>
                            <p v-else-if="task.is_due_today" class="text-xs text-amber-600 dark:text-amber-400">Vence hoy</p>
                        </div>
                        <div v-if="task.taskable_label">
                            <Separator />
                            <p class="text-xs text-muted-foreground">Relacionada con</p>
                            <Link
                                v-if="task.taskable_href"
                                :href="task.taskable_href"
                                class="inline-flex items-center gap-1.5 text-sm font-medium text-primary hover:underline"
                            >
                                <Link2 class="size-3.5" />
                                {{ task.taskable_type }} · {{ task.taskable_label }}
                            </Link>
                        </div>
                        <Separator />
                        <div>
                            <p class="text-xs text-muted-foreground">Asignado a</p>
                            <p class="font-medium">{{ task.assignee?.name ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Creada por</p>
                            <p class="font-medium">{{ task.creator?.name ?? '—' }}</p>
                        </div>
                        <div v-if="task.completed_at">
                            <Separator />
                            <p class="text-xs text-muted-foreground">Completada</p>
                            <p class="flex items-center gap-1.5 font-medium text-emerald-600 dark:text-emerald-400">
                                <CheckCircle class="size-3.5" />
                                {{ formatDateTime(task.completed_at) }}
                            </p>
                            <p v-if="task.completer" class="text-xs text-muted-foreground">por {{ task.completer.name }}</p>
                        </div>
                    </CardContent>
                </Card>

                <Card class="lg:col-span-2">
                    <CardHeader>
                        <CardTitle class="text-base">Descripción</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p v-if="task.description" class="whitespace-pre-line text-sm">{{ task.description }}</p>
                        <p v-else class="text-sm text-muted-foreground">Sin descripción.</p>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
