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
import { formatCurrency } from '@/composables/useFormat';
import { Eye, KanbanSquare, LayoutGrid, Pencil, Plus, Search, Target, Trash2, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface KanbanCard {
    id: number;
    name: string;
    amount: number;
    weighted_amount: number;
    probability: number;
    expected_close_date: string | null;
    is_overdue: boolean;
    customer: { id: number; name: string } | null;
    lead: { id: number; name: string } | null;
    owner: { id: number; name: string } | null;
}

interface OpItem {
    id: number;
    name: string;
    amount: number;
    weighted_amount: number;
    probability: number;
    stage: string;
    stage_label: string;
    stage_badge: string;
    expected_close_date: string | null;
    is_overdue: boolean;
    is_final: boolean;
    customer: { id: number; name: string } | null;
    lead: { id: number; name: string } | null;
    owner: { id: number; name: string } | null;
}

interface Paginated {
    data: OpItem[];
    links: { url: string | null; label: string; active: boolean }[];
}

interface StageOption {
    value: string;
    label: string;
    badge: string;
    open: boolean;
}

interface CustomerOption { id: number; name: string; }
interface OwnerOption { id: number; name: string; }
interface Summary {
    open_count: number;
    open_value: number;
    weighted_value: number;
    won_count: number;
    won_value: number;
    lost_count: number;
}

const props = defineProps<{
    kanban: Record<string, KanbanCard[]>;
    opportunities: Paginated;
    stages: StageOption[];
    customers: CustomerOption[];
    owners: OwnerOption[];
    summary: Summary;
    filters: {
        search: string;
        stage: string | null;
        owner_id: number | null;
        customer_id: number | null;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tablero', href: '/dashboard' },
    { title: 'Oportunidades', href: '/opportunities' },
];

const view = ref<'kanban' | 'table'>('kanban');
const searchInput = ref(props.filters.search ?? '');
const stage = ref(props.filters.stage ?? '');
const ownerId = ref<number | null>(props.filters.owner_id ?? null);
const customerId = ref<number | null>(props.filters.customer_id ?? null);

watch(
    () => props.filters,
    (f) => {
        searchInput.value = f.search ?? '';
        stage.value = f.stage ?? '';
        ownerId.value = f.owner_id ?? null;
        customerId.value = f.customer_id ?? null;
    },
);

const hasFilters = () => !!(props.filters.search || props.filters.stage || props.filters.owner_id || props.filters.customer_id);

const applyFilters = () => {
    router.get(
        route('opportunities.index'),
        {
            search: searchInput.value || undefined,
            stage: stage.value || undefined,
            owner_id: ownerId.value || undefined,
            customer_id: customerId.value || undefined,
        },
        { preserveScroll: true, preserveState: true },
    );
};

const clearFilters = () => {
    router.get(route('opportunities.index'), {}, { preserveScroll: true });
};

const confirmOpen = ref(false);
const processing = ref(false);
const target = ref<OpItem | null>(null);

const askDelete = (op: OpItem) => {
    target.value = op;
    confirmOpen.value = true;
};

const handleDelete = () => {
    if (!target.value) return;
    processing.value = true;
    router.delete(route('opportunities.destroy', target.value.id), {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
            confirmOpen.value = false;
            target.value = null;
        },
    });
};

const openStages = computed(() => props.stages.filter((s) => s.open));
</script>

<template>
    <Head title="Oportunidades" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <PageHeader
                title="Pipeline de ventas"
                description="Embudo de oportunidades: prospección, calificación, propuesta, negociación, cierre."
            >
                <template #actions>
                    <div class="flex items-center rounded-md border border-input">
                        <Button
                            variant="ghost"
                            size="sm"
                            :class="['rounded-r-none', view === 'kanban' ? 'bg-muted' : '']"
                            @click="view = 'kanban'"
                        >
                            <KanbanSquare class="size-4" />
                            Kanban
                        </Button>
                        <Button
                            variant="ghost"
                            size="sm"
                            :class="['rounded-l-none border-l', view === 'table' ? 'bg-muted' : '']"
                            @click="view = 'table'"
                        >
                            <LayoutGrid class="size-4" />
                            Tabla
                        </Button>
                    </div>
                    <Button as-child>
                        <Link :href="route('opportunities.create')">
                            <Plus class="mr-1" />
                            Nueva oportunidad
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-6">
                <div class="rounded-xl border border-border/60 bg-card p-4">
                    <p class="text-xs text-muted-foreground">Abiertas</p>
                    <p class="text-2xl font-semibold tabular-nums">{{ summary.open_count }}</p>
                </div>
                <div class="rounded-xl border border-border/60 bg-card p-4">
                    <p class="text-xs text-muted-foreground">Valor abierto</p>
                    <p class="text-2xl font-semibold tabular-nums">{{ formatCurrency(summary.open_value) }}</p>
                </div>
                <div class="rounded-xl border border-border/60 bg-card p-4">
                    <p class="text-xs text-muted-foreground">Ponderado</p>
                    <p class="text-2xl font-semibold tabular-nums text-emerald-600 dark:text-emerald-400">
                        {{ formatCurrency(summary.weighted_value) }}
                    </p>
                </div>
                <div class="rounded-xl border border-border/60 bg-card p-4">
                    <p class="text-xs text-muted-foreground">Ganadas</p>
                    <p class="text-2xl font-semibold tabular-nums">{{ summary.won_count }}</p>
                </div>
                <div class="rounded-xl border border-border/60 bg-card p-4">
                    <p class="text-xs text-muted-foreground">Valor ganado</p>
                    <p class="text-2xl font-semibold tabular-nums text-emerald-600 dark:text-emerald-400">
                        {{ formatCurrency(summary.won_value) }}
                    </p>
                </div>
                <div class="rounded-xl border border-border/60 bg-card p-4">
                    <p class="text-xs text-muted-foreground">Perdidas</p>
                    <p class="text-2xl font-semibold tabular-nums text-muted-foreground">{{ summary.lost_count }}</p>
                </div>
            </div>

            <div class="flex flex-wrap items-end gap-2">
                <div class="relative min-w-[200px] flex-1">
                    <Search class="absolute left-2.5 top-2.5 size-4 text-muted-foreground" />
                    <Input
                        v-model="searchInput"
                        placeholder="Buscar por nombre, notas o cliente..."
                        class="pl-9"
                        @keyup.enter="applyFilters"
                    />
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs text-muted-foreground">Etapa</label>
                    <select v-model="stage" class="h-9 rounded-md border border-input bg-background px-2 text-sm" @change="applyFilters">
                        <option value="">Todas</option>
                        <option v-for="s in stages" :key="s.value" :value="s.value">{{ s.label }}</option>
                    </select>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs text-muted-foreground">Cliente</label>
                    <select v-model="customerId" class="h-9 rounded-md border border-input bg-background px-2 text-sm" @change="applyFilters">
                        <option :value="null">Todos</option>
                        <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs text-muted-foreground">Responsable</label>
                    <select v-model="ownerId" class="h-9 rounded-md border border-input bg-background px-2 text-sm" @change="applyFilters">
                        <option :value="null">Todos</option>
                        <option v-for="o in owners" :key="o.id" :value="o.id">{{ o.name }}</option>
                    </select>
                </div>

                <Button v-if="hasFilters()" variant="ghost" @click="clearFilters">
                    <X class="mr-1" />
                    Limpiar
                </Button>
            </div>

            <div v-if="view === 'kanban'" class="flex gap-3 overflow-x-auto pb-2">
                <div
                    v-for="s in openStages"
                    :key="s.value"
                    class="flex w-72 shrink-0 flex-col gap-2 rounded-lg bg-muted/40 p-3"
                >
                    <div class="flex items-center justify-between px-1">
                        <div class="flex items-center gap-2">
                            <Badge :variant="s.badge as any">{{ s.label }}</Badge>
                            <span class="text-xs text-muted-foreground">
                                {{ (kanban[s.value] ?? []).length }}
                            </span>
                        </div>
                    </div>
                    <Link
                        v-for="card in kanban[s.value] ?? []"
                        :key="card.id"
                        :href="route('opportunities.show', card.id)"
                        class="rounded-md border border-border/60 bg-card p-3 text-sm shadow-sm transition-colors hover:border-primary/40"
                    >
                        <p class="font-medium">{{ card.name }}</p>
                        <p v-if="card.customer" class="truncate text-xs text-muted-foreground">
                            {{ card.customer.name }}
                        </p>
                        <div class="mt-2 flex items-center justify-between text-xs">
                            <span class="font-semibold tabular-nums">{{ formatCurrency(card.amount) }}</span>
                            <span class="text-muted-foreground">{{ card.probability }}%</span>
                        </div>
                        <div class="mt-1 flex items-center justify-between text-xs text-muted-foreground">
                            <span v-if="card.expected_close_date" :class="card.is_overdue ? 'text-destructive font-medium' : ''">
                                {{ card.expected_close_date }}
                                <span v-if="card.is_overdue" class="text-destructive">· vencida</span>
                            </span>
                            <span v-if="card.owner">{{ card.owner.name }}</span>
                        </div>
                    </Link>
                    <div
                        v-if="!kanban[s.value] || kanban[s.value].length === 0"
                        class="rounded-md border border-dashed border-border/60 p-3 text-center text-xs text-muted-foreground"
                    >
                        Sin oportunidades
                    </div>
                </div>
            </div>

            <div v-else class="rounded-xl border border-border/60">
                <Table v-if="opportunities.data.length > 0">
                    <TableHeader>
                        <TableRow>
                            <TableHead>Nombre</TableHead>
                            <TableHead>Cliente</TableHead>
                            <TableHead class="w-32">Etapa</TableHead>
                            <TableHead class="text-right">Monto</TableHead>
                            <TableHead class="text-center">Prob.</TableHead>
                            <TableHead>Fecha est.</TableHead>
                            <TableHead>Responsable</TableHead>
                            <TableHead class="w-32 text-right">Acciones</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="op in opportunities.data" :key="op.id">
                            <TableCell>
                                <div class="font-medium">{{ op.name }}</div>
                            </TableCell>
                            <TableCell class="text-muted-foreground">{{ op.customer?.name ?? '—' }}</TableCell>
                            <TableCell>
                                <Badge :variant="op.stage_badge as any">{{ op.stage_label }}</Badge>
                            </TableCell>
                            <TableCell class="text-right tabular-nums">{{ formatCurrency(op.amount) }}</TableCell>
                            <TableCell class="text-center">{{ op.probability }}%</TableCell>
                            <TableCell :class="op.is_overdue ? 'text-destructive' : 'text-muted-foreground'">
                                {{ op.expected_close_date ?? '—' }}
                            </TableCell>
                            <TableCell class="text-muted-foreground">{{ op.owner?.name ?? '—' }}</TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-1">
                                    <Button variant="ghost" size="icon" as-child>
                                        <Link :href="route('opportunities.show', op.id)">
                                            <Eye />
                                        </Link>
                                    </Button>
                                    <Button v-if="!op.is_final" variant="ghost" size="icon" as-child>
                                        <Link :href="route('opportunities.edit', op.id)">
                                            <Pencil />
                                        </Link>
                                    </Button>
                                    <Button
                                        v-if="!op.is_final"
                                        variant="ghost"
                                        size="icon"
                                        class="text-destructive hover:text-destructive"
                                        @click="askDelete(op)"
                                    >
                                        <Trash2 />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                <EmptyState
                    v-else-if="!hasFilters()"
                    :icon="Target"
                    title="Sin oportunidades todavía"
                    description="Registra tu primera oportunidad para empezar a llenar el pipeline."
                    :action="{ label: 'Nueva oportunidad', href: route('opportunities.create') }"
                />
                <EmptyState
                    v-else
                    :icon="Search"
                    title="Sin resultados"
                    description="No encontramos oportunidades con ese criterio."
                />
            </div>

            <Pagination v-if="view === 'table' && opportunities.data.length > 0" :links="opportunities.links" />
        </div>

        <ConfirmDialog
            v-model:open="confirmOpen"
            title="Eliminar oportunidad"
            :description="`¿Estás seguro de eliminar la oportunidad «${target?.name}»? Esta acción no se puede deshacer.`"
            :processing="processing"
            @confirm="handleDelete"
        />
    </AppLayout>
</template>
