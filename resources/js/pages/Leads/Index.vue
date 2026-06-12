<script setup lang="ts">
import ConfirmDialog from '@/components/stockflow/ConfirmDialog.vue';
import EmptyState from '@/components/stockflow/EmptyState.vue';
import PageHeader from '@/components/stockflow/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Pagination } from '@/components/ui/pagination';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatCurrency } from '@/composables/useFormat';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Columns2, Eye, LayoutGrid, Pencil, Plus, Search, Trash2, UserPlus, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface LeadItem {
    id: number;
    name: string;
    company: string | null;
    email: string | null;
    phone: string | null;
    source: string;
    source_label: string;
    stage: string;
    stage_label: string;
    stage_badge: string;
    estimated_value: number;
    score: number | null;
    is_converted: boolean;
    converted_at: string | null;
    owner: { id: number; name: string } | null;
    customer: { id: number; name: string } | null;
}

interface PaginatedLeads {
    data: LeadItem[];
    links: { url: string | null; label: string; active: boolean }[];
}

interface StageOption {
    value: string;
    label: string;
    badge: string;
}

interface SourceOption {
    value: string;
    label: string;
}

interface OwnerOption {
    id: number;
    name: string;
}

const props = defineProps<{
    leads: PaginatedLeads;
    kanban: Record<
        string,
        Array<{
            id: number;
            name: string;
            company: string | null;
            estimated_value: number;
            score: number | null;
            owner: { id: number; name: string } | null;
        }>
    >;
    stages: StageOption[];
    sources: SourceOption[];
    owners: OwnerOption[];
    filters: {
        search: string;
        stage: string | null;
        source: string | null;
        owner_id: number | null;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tablero', href: '/dashboard' },
    { title: 'Leads', href: '/leads' },
];

const view = ref<'table' | 'kanban'>('table');
const searchInput = ref(props.filters.search ?? '');
const stage = ref(props.filters.stage ?? '');
const source = ref(props.filters.source ?? '');
const ownerId = ref<number | null>(props.filters.owner_id ?? null);

watch(
    () => props.filters,
    (f) => {
        searchInput.value = f.search ?? '';
        stage.value = f.stage ?? '';
        source.value = f.source ?? '';
        ownerId.value = f.owner_id ?? null;
    },
);

const hasFilters = () => !!(props.filters.search || props.filters.stage || props.filters.source || props.filters.owner_id);

const applyFilters = () => {
    router.get(
        route('leads.index'),
        {
            search: searchInput.value || undefined,
            stage: stage.value || undefined,
            source: source.value || undefined,
            owner_id: ownerId.value || undefined,
        },
        { preserveScroll: true, preserveState: true },
    );
};

const clearFilters = () => {
    router.get(route('leads.index'), {}, { preserveScroll: true });
};

const confirmOpen = ref(false);
const processing = ref(false);
const target = ref<LeadItem | null>(null);

const askDelete = (lead: LeadItem) => {
    target.value = lead;
    confirmOpen.value = true;
};

const handleDelete = () => {
    if (!target.value) return;
    processing.value = true;
    router.delete(route('leads.destroy', target.value.id), {
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
    <Head title="Leads" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <PageHeader
                title="Leads"
                description="Gestiona tu embudo de prospección: nuevos, contactados, calificados, propuestas, ganados y perdidos."
            >
                <template #actions>
                    <div class="flex items-center rounded-md border border-input">
                        <Button variant="ghost" size="sm" :class="['rounded-r-none', view === 'table' ? 'bg-muted' : '']" @click="view = 'table'">
                            <LayoutGrid class="size-4" />
                            Tabla
                        </Button>
                        <Button
                            variant="ghost"
                            size="sm"
                            :class="['rounded-l-none border-l', view === 'kanban' ? 'bg-muted' : '']"
                            @click="view = 'kanban'"
                        >
                            <Columns2 class="size-4" />
                            Kanban
                        </Button>
                    </div>
                    <Button as-child>
                        <Link :href="route('leads.create')">
                            <Plus class="mr-1" />
                            Nuevo lead
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <div class="flex flex-wrap items-end gap-2">
                <div class="relative min-w-[200px] flex-1">
                    <Search class="absolute left-2.5 top-2.5 size-4 text-muted-foreground" />
                    <Input
                        v-model="searchInput"
                        placeholder="Buscar por nombre, empresa, email o teléfono..."
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
                    <label class="text-xs text-muted-foreground">Origen</label>
                    <select v-model="source" class="h-9 rounded-md border border-input bg-background px-2 text-sm" @change="applyFilters">
                        <option value="">Todos</option>
                        <option v-for="s in sources" :key="s.value" :value="s.value">{{ s.label }}</option>
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

            <div v-if="view === 'table'" class="rounded-xl border border-border/60">
                <Table v-if="leads.data.length > 0">
                    <TableHeader>
                        <TableRow>
                            <TableHead>Nombre</TableHead>
                            <TableHead>Empresa</TableHead>
                            <TableHead class="w-32">Origen</TableHead>
                            <TableHead class="w-32">Etapa</TableHead>
                            <TableHead class="text-right">Valor est.</TableHead>
                            <TableHead class="w-20 text-center">Score</TableHead>
                            <TableHead>Responsable</TableHead>
                            <TableHead class="w-32 text-right">Acciones</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="lead in leads.data" :key="lead.id">
                            <TableCell>
                                <div class="font-medium">{{ lead.name }}</div>
                                <div v-if="lead.email || lead.phone" class="text-xs text-muted-foreground">
                                    <span v-if="lead.email">{{ lead.email }}</span>
                                    <span v-if="lead.email && lead.phone"> · </span>
                                    <span v-if="lead.phone">{{ lead.phone }}</span>
                                </div>
                            </TableCell>
                            <TableCell class="text-muted-foreground">{{ lead.company ?? '—' }}</TableCell>
                            <TableCell class="text-xs">{{ lead.source_label }}</TableCell>
                            <TableCell>
                                <Badge :variant="lead.stage_badge as any">
                                    {{ lead.stage_label }}
                                </Badge>
                                <Badge v-if="lead.is_converted" variant="success" class="ml-1">Convertido</Badge>
                            </TableCell>
                            <TableCell class="text-right tabular-nums">{{ formatCurrency(lead.estimated_value) }}</TableCell>
                            <TableCell class="text-center">
                                <Badge v-if="lead.score !== null" variant="secondary">{{ lead.score }}%</Badge>
                                <span v-else class="text-muted-foreground">—</span>
                            </TableCell>
                            <TableCell class="text-muted-foreground">{{ lead.owner?.name ?? '—' }}</TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-1">
                                    <Button variant="ghost" size="icon" as-child>
                                        <Link :href="route('leads.show', lead.id)">
                                            <Eye />
                                        </Link>
                                    </Button>
                                    <Button
                                        v-if="
                                            !lead.is_converted &&
                                            (lead.stage === 'new' ||
                                                lead.stage === 'contacted' ||
                                                lead.stage === 'qualified' ||
                                                lead.stage === 'proposal')
                                        "
                                        variant="ghost"
                                        size="icon"
                                        as-child
                                    >
                                        <Link :href="route('leads.edit', lead.id)">
                                            <Pencil />
                                        </Link>
                                    </Button>
                                    <Button
                                        v-if="!lead.is_converted"
                                        variant="ghost"
                                        size="icon"
                                        class="text-destructive hover:text-destructive"
                                        @click="askDelete(lead)"
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
                    :icon="UserPlus"
                    title="Sin leads todavía"
                    description="Registra tu primer lead para empezar a construir tu embudo."
                    :action="{ label: 'Nuevo lead', href: route('leads.create') }"
                />
                <EmptyState v-else :icon="Search" title="Sin resultados" description="No encontramos leads con ese criterio." />
            </div>

            <div v-else class="flex gap-3 overflow-x-auto pb-2">
                <div
                    v-for="s in stages.filter((st) => ['new', 'contacted', 'qualified', 'proposal'].includes(st.value))"
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
                        v-for="lead in kanban[s.value] ?? []"
                        :key="lead.id"
                        :href="route('leads.show', lead.id)"
                        class="rounded-md border border-border/60 bg-card p-3 text-sm shadow-sm transition-colors hover:border-primary/40"
                    >
                        <p class="font-medium">{{ lead.name }}</p>
                        <p v-if="lead.company" class="truncate text-xs text-muted-foreground">{{ lead.company }}</p>
                        <div class="mt-2 flex items-center justify-between text-xs">
                            <span class="font-semibold tabular-nums">{{ formatCurrency(lead.estimated_value) }}</span>
                            <Badge v-if="lead.score !== null" variant="secondary">{{ lead.score }}%</Badge>
                        </div>
                        <p v-if="lead.owner" class="mt-1 text-xs text-muted-foreground">{{ lead.owner.name }}</p>
                    </Link>
                    <div
                        v-if="!kanban[s.value] || kanban[s.value].length === 0"
                        class="rounded-md border border-dashed border-border/60 p-3 text-center text-xs text-muted-foreground"
                    >
                        Arrastra un lead aquí
                    </div>
                </div>
            </div>

            <Pagination v-if="view === 'table' && leads.data.length > 0" :links="leads.links" />
        </div>

        <ConfirmDialog
            v-model:open="confirmOpen"
            title="Eliminar lead"
            :description="`¿Estás seguro de eliminar el lead «${target?.name}»? Esta acción no se puede deshacer.`"
            :processing="processing"
            @confirm="handleDelete"
        />
    </AppLayout>
</template>
