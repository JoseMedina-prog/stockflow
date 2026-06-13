<script setup lang="ts">
import ConfirmDialog from '@/components/stockflow/ConfirmDialog.vue';
import EmptyState from '@/components/stockflow/EmptyState.vue';
import PageHeader from '@/components/stockflow/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Pagination } from '@/components/ui/pagination';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { AlertTriangle, Calendar, Check, CheckSquare, Eye, ListChecks, Pencil, Plus, Search, Trash2, User, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface TaskItem {
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
    taskable_type: string | null;
    taskable_href: string | null;
}

interface PaginatedTasks {
    data: TaskItem[];
    links: { url: string | null; label: string; active: boolean }[];
}

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

const props = defineProps<{
    tasks: PaginatedTasks;
    summary: {
        open_count: number;
        mine_open: number;
        mine_overdue: number;
        mine_today: number;
    };
    priorities: PriorityOption[];
    statuses: StatusOption[];
    users: UserOption[];
    current_user_id: number;
    filters: {
        search: string;
        priority: string | null;
        status: string | null;
        assignee_id: number | null;
        mine: boolean;
        overdue: boolean;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tablero', href: '/dashboard' },
    { title: 'Ventas', href: '/sales' },
    { title: 'Tareas', href: '/tasks' },
];

const searchInput = ref(props.filters.search ?? '');
const priority = ref(props.filters.priority ?? '');
const status = ref(props.filters.status ?? '');
const assigneeId = ref<number | null>(props.filters.assignee_id ?? null);
const mine = ref(props.filters.mine);
const overdue = ref(props.filters.overdue);

watch(
    () => props.filters,
    (f) => {
        searchInput.value = f.search ?? '';
        priority.value = f.priority ?? '';
        status.value = f.status ?? '';
        assigneeId.value = f.assignee_id ?? null;
        mine.value = f.mine;
        overdue.value = f.overdue;
    },
);

const hasFilters = () =>
    !!(
        props.filters.search ||
        props.filters.priority ||
        props.filters.status ||
        props.filters.assignee_id ||
        props.filters.mine ||
        props.filters.overdue
    );

const applyFilters = () => {
    router.get(
        route('tasks.index'),
        {
            search: searchInput.value || undefined,
            priority: priority.value || undefined,
            status: status.value || undefined,
            assignee_id: assigneeId.value || undefined,
            mine: mine.value || undefined,
            overdue: overdue.value || undefined,
        },
        { preserveScroll: true, preserveState: true },
    );
};

const clearFilters = () => {
    router.get(route('tasks.index'), {}, { preserveScroll: true });
};

const toggleMine = () => {
    mine.value = !mine.value;
    applyFilters();
};

const toggleOverdue = () => {
    overdue.value = !overdue.value;
    applyFilters();
};

const toggleComplete = (task: TaskItem) => {
    router.post(route('tasks.complete', task.id), {}, { preserveScroll: true });
};

const confirmOpen = ref(false);
const processing = ref(false);
const target = ref<TaskItem | null>(null);

const askDelete = (task: TaskItem) => {
    target.value = task;
    confirmOpen.value = true;
};

const handleDelete = () => {
    if (!target.value) return;
    processing.value = true;
    router.delete(route('tasks.destroy', target.value.id), {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
            confirmOpen.value = false;
            target.value = null;
        },
    });
};
</script>

<template>
    <Head title="Tareas" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <PageHeader title="Tareas" description="Listado de pendientes, vencidos y por responsable.">
                <template #actions>
                    <Button as-child>
                        <Link :href="route('tasks.create')">
                            <Plus class="mr-1" />
                            Nueva tarea
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <div class="grid gap-3 sm:grid-cols-4">
                <button
                    type="button"
                    class="rounded-xl border border-border/60 bg-card p-4 text-left transition-colors hover:border-primary/40"
                    :class="mine ? 'ring-2 ring-primary' : ''"
                    @click="toggleMine"
                >
                    <p class="flex items-center gap-1.5 text-xs text-muted-foreground">
                        <User class="size-3.5" />
                        Mis pendientes
                    </p>
                    <p class="text-2xl font-semibold tabular-nums">{{ summary.mine_open }}</p>
                </button>
                <button
                    type="button"
                    class="rounded-xl border p-4 text-left transition-colors"
                    :class="
                        overdue
                            ? 'border-destructive/60 bg-destructive/5 ring-2 ring-destructive'
                            : 'border-border/60 bg-card hover:border-destructive/40'
                    "
                    @click="toggleOverdue"
                >
                    <p class="flex items-center gap-1.5 text-xs text-muted-foreground">
                        <AlertTriangle class="size-3.5" />
                        Vencidas
                    </p>
                    <p class="text-2xl font-semibold tabular-nums text-destructive">{{ summary.mine_overdue }}</p>
                </button>
                <div class="rounded-xl border border-border/60 bg-card p-4">
                    <p class="flex items-center gap-1.5 text-xs text-muted-foreground">
                        <Calendar class="size-3.5" />
                        Vencen hoy
                    </p>
                    <p class="text-2xl font-semibold tabular-nums text-amber-600 dark:text-amber-400">{{ summary.mine_today }}</p>
                </div>
                <div class="rounded-xl border border-border/60 bg-card p-4">
                    <p class="flex items-center gap-1.5 text-xs text-muted-foreground">
                        <ListChecks class="size-3.5" />
                        Abiertas totales
                    </p>
                    <p class="text-2xl font-semibold tabular-nums">{{ summary.open_count }}</p>
                </div>
            </div>

            <div class="flex flex-wrap items-end gap-2">
                <div class="relative min-w-[200px] flex-1">
                    <Search class="absolute left-2.5 top-2.5 size-4 text-muted-foreground" />
                    <Input v-model="searchInput" placeholder="Buscar por título o descripción..." class="pl-9" @keyup.enter="applyFilters" />
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs text-muted-foreground">Prioridad</label>
                    <select v-model="priority" class="h-9 rounded-md border border-input bg-background px-2 text-sm" @change="applyFilters">
                        <option value="">Todas</option>
                        <option v-for="p in priorities" :key="p.value" :value="p.value">{{ p.label }}</option>
                    </select>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs text-muted-foreground">Estado</label>
                    <select v-model="status" class="h-9 rounded-md border border-input bg-background px-2 text-sm" @change="applyFilters">
                        <option value="">Todos</option>
                        <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                    </select>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs text-muted-foreground">Asignado a</label>
                    <select v-model="assigneeId" class="h-9 rounded-md border border-input bg-background px-2 text-sm" @change="applyFilters">
                        <option :value="null">Todos</option>
                        <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                    </select>
                </div>

                <Button v-if="hasFilters()" variant="ghost" @click="clearFilters">
                    <X class="mr-1" />
                    Limpiar
                </Button>
            </div>

            <div class="rounded-xl border border-border/60">
                <Table v-if="tasks.data.length > 0">
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-12"></TableHead>
                            <TableHead>Tarea</TableHead>
                            <TableHead class="w-28">Prioridad</TableHead>
                            <TableHead class="w-32">Estado</TableHead>
                            <TableHead class="w-32">Vence</TableHead>
                            <TableHead>Asignado a</TableHead>
                            <TableHead>Relacionado con</TableHead>
                            <TableHead class="w-28 text-right">Acciones</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="task in tasks.data" :key="task.id" :class="task.is_overdue ? 'bg-destructive/5' : ''">
                            <TableCell>
                                <button
                                    v-if="!task.is_final"
                                    class="flex size-7 items-center justify-center rounded-md border border-input hover:border-primary hover:bg-accent"
                                    :class="task.status === 'completed' ? 'border-primary bg-primary text-primary-foreground' : ''"
                                    @click="toggleComplete(task)"
                                >
                                    <Check v-if="task.status === 'completed'" class="size-4" />
                                </button>
                            </TableCell>
                            <TableCell>
                                <Link :href="route('tasks.show', task.id)" class="font-medium hover:underline">
                                    {{ task.title }}
                                </Link>
                                <p v-if="task.description" class="line-clamp-1 text-xs text-muted-foreground">{{ task.description }}</p>
                            </TableCell>
                            <TableCell>
                                <Badge :variant="task.priority_badge as any">{{ task.priority_label }}</Badge>
                            </TableCell>
                            <TableCell>
                                <Badge :variant="task.status_badge as any">{{ task.status_label }}</Badge>
                            </TableCell>
                            <TableCell :class="task.is_overdue ? 'font-medium text-destructive' : 'text-muted-foreground'">
                                <div v-if="task.due_date">
                                    <div>{{ task.due_date }}</div>
                                    <div v-if="task.is_due_today" class="text-xs text-amber-600 dark:text-amber-400">Hoy</div>
                                    <div v-else-if="task.is_overdue" class="text-xs">Vencida</div>
                                </div>
                                <span v-else>—</span>
                            </TableCell>
                            <TableCell class="text-muted-foreground">{{ task.assignee?.name ?? '—' }}</TableCell>
                            <TableCell>
                                <Link v-if="task.taskable_href" :href="task.taskable_href" class="text-xs text-primary hover:underline">
                                    {{ task.taskable_type }}
                                </Link>
                                <span v-else class="text-xs text-muted-foreground">—</span>
                            </TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-1">
                                    <Button variant="ghost" size="icon" as-child>
                                        <Link :href="route('tasks.show', task.id)">
                                            <Eye />
                                        </Link>
                                    </Button>
                                    <Button variant="ghost" size="icon" as-child>
                                        <Link :href="route('tasks.edit', task.id)">
                                            <Pencil />
                                        </Link>
                                    </Button>
                                    <Button variant="ghost" size="icon" class="text-destructive hover:text-destructive" @click="askDelete(task)">
                                        <Trash2 />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                <EmptyState
                    v-else-if="!hasFilters()"
                    :icon="CheckSquare"
                    title="Sin tareas"
                    description="No tienes tareas pendientes. Crea una para empezar."
                    :action="{ label: 'Nueva tarea', href: route('tasks.create') }"
                />
                <EmptyState v-else :icon="Search" title="Sin resultados" description="No encontramos tareas con ese criterio." />
            </div>

            <Pagination v-if="tasks.data.length > 0" :links="tasks.links" />
        </div>

        <ConfirmDialog
            v-model:open="confirmOpen"
            title="Eliminar tarea"
            :description="`¿Estás seguro de eliminar la tarea «${target?.title}»? Esta acción no se puede deshacer.`"
            :processing="processing"
            @confirm="handleDelete"
        />
    </AppLayout>
</template>
