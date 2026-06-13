<script setup lang="ts">
import ConfirmDialog from '@/components/stockflow/ConfirmDialog.vue';
import EmptyState from '@/components/stockflow/EmptyState.vue';
import PageHeader from '@/components/stockflow/PageHeader.vue';
import StatCard from '@/components/stockflow/StatCard.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Pagination } from '@/components/ui/pagination';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatCurrency, formatDate } from '@/composables/useFormat';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Check, CircleDollarSign, Eye, FileText, Pencil, Plus, Search, Trash2, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface QuoteListItem {
    id: number;
    folio: string;
    quote_date: string;
    valid_until: string | null;
    is_expired: boolean;
    subtotal: number;
    tax: number;
    total: number;
    status: string;
    status_label: string;
    status_badge: string;
    can_be_edited: boolean;
    can_be_deleted: boolean;
    items_count: number;
    customer: { id: number; name: string } | null;
    opportunity: { id: number; name: string } | null;
    user: { id: number; name: string };
}

interface PaginatedQuotes {
    data: QuoteListItem[];
    links: { url: string | null; label: string; active: boolean }[];
}

interface StatusOption {
    value: string;
    label: string;
}

const props = defineProps<{
    quotes: PaginatedQuotes;
    summary: {
        open_count: number;
        open_value: number;
        accepted_count: number;
        accepted_value: number;
        converted_count: number;
    };
    statuses: StatusOption[];
    filters: {
        search: string;
        status: string | null;
        customer_id: number | null;
        from: string | null;
        to: string | null;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tablero', href: '/dashboard' },
    { title: 'Ventas', href: '/sales' },
    { title: 'Cotizaciones', href: '/quotes' },
];

const searchInput = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');
const from = ref(props.filters.from ?? '');
const to = ref(props.filters.to ?? '');

watch(
    () => props.filters,
    (f) => {
        searchInput.value = f.search ?? '';
        status.value = f.status ?? '';
        from.value = f.from ?? '';
        to.value = f.to ?? '';
    },
);

const hasFilters = () => !!(props.filters.search || props.filters.status || props.filters.from || props.filters.to);

const applyFilters = () => {
    router.get(
        route('quotes.index'),
        {
            search: searchInput.value || undefined,
            status: status.value || undefined,
            from: from.value || undefined,
            to: to.value || undefined,
        },
        { preserveScroll: true, preserveState: true },
    );
};

const clearFilters = () => {
    router.get(route('quotes.index'), {}, { preserveScroll: true });
};

const confirmOpen = ref(false);
const processing = ref(false);
const target = ref<QuoteListItem | null>(null);

const askDelete = (q: QuoteListItem) => {
    target.value = q;
    confirmOpen.value = true;
};

const handleDelete = () => {
    if (!target.value) return;
    processing.value = true;
    router.delete(route('quotes.destroy', target.value.id), {
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
    <Head title="Cotizaciones" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <PageHeader title="Cotizaciones" description="Crea, envía y convierte cotizaciones en ventas.">
                <template #actions>
                    <Button as-child>
                        <Link :href="route('quotes.create')">
                            <Plus class="mr-1" />
                            Nueva cotización
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <div class="grid gap-4 sm:grid-cols-3">
                <StatCard
                    title="Abiertas"
                    :value="String(summary.open_count)"
                    :description="formatCurrency(summary.open_value)"
                    :icon="FileText"
                    accent="info"
                />
                <StatCard
                    title="Aceptadas"
                    :value="String(summary.accepted_count)"
                    :description="formatCurrency(summary.accepted_value)"
                    :icon="Check"
                    accent="success"
                />
                <StatCard
                    title="Convertidas"
                    :value="String(summary.converted_count)"
                    description="Cotizaciones que ya son ventas"
                    :icon="CircleDollarSign"
                    accent="primary"
                />
            </div>

            <div class="flex flex-wrap items-end gap-2">
                <div class="relative min-w-[200px] flex-1">
                    <Search class="absolute left-2.5 top-2.5 size-4 text-muted-foreground" />
                    <Input v-model="searchInput" placeholder="Buscar por folio o cliente..." class="pl-9" @keyup.enter="applyFilters" />
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs text-muted-foreground">Estado</label>
                    <select v-model="status" class="h-9 rounded-md border border-input bg-background px-2 text-sm" @change="applyFilters">
                        <option value="">Todos</option>
                        <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                    </select>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs text-muted-foreground">Desde</label>
                    <Input v-model="from" type="date" @change="applyFilters" />
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs text-muted-foreground">Hasta</label>
                    <Input v-model="to" type="date" @change="applyFilters" />
                </div>

                <Button v-if="hasFilters()" variant="ghost" @click="clearFilters">
                    <X class="mr-1" />
                    Limpiar
                </Button>
            </div>

            <div class="rounded-xl border border-border/60">
                <Table v-if="quotes.data.length > 0">
                    <TableHeader>
                        <TableRow>
                            <TableHead>Folio</TableHead>
                            <TableHead>Cliente</TableHead>
                            <TableHead>Fecha</TableHead>
                            <TableHead>Vigencia</TableHead>
                            <TableHead class="text-center">Items</TableHead>
                            <TableHead class="text-right">Total</TableHead>
                            <TableHead>Estado</TableHead>
                            <TableHead class="w-32 text-right">Acciones</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="q in quotes.data" :key="q.id">
                            <TableCell>
                                <Link :href="route('quotes.show', q.id)" class="font-mono text-xs hover:underline">
                                    {{ q.folio }}
                                </Link>
                            </TableCell>
                            <TableCell>
                                <span v-if="q.customer" class="font-medium">{{ q.customer.name }}</span>
                                <span v-else class="text-muted-foreground">—</span>
                                <div v-if="q.opportunity" class="text-xs text-muted-foreground">Desde oportunidad: {{ q.opportunity.name }}</div>
                            </TableCell>
                            <TableCell class="text-xs text-muted-foreground">{{ formatDate(q.quote_date) }}</TableCell>
                            <TableCell class="text-xs">
                                <span v-if="q.valid_until" :class="q.is_expired ? 'text-destructive' : 'text-muted-foreground'">
                                    {{ formatDate(q.valid_until) }}
                                    <span v-if="q.is_expired">(vencida)</span>
                                </span>
                                <span v-else class="text-muted-foreground">—</span>
                            </TableCell>
                            <TableCell class="text-center tabular-nums">{{ q.items_count }}</TableCell>
                            <TableCell class="text-right font-semibold tabular-nums">{{ formatCurrency(q.total) }}</TableCell>
                            <TableCell>
                                <Badge :variant="q.status_badge as any">{{ q.status_label }}</Badge>
                            </TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-1">
                                    <Button variant="ghost" size="icon" as-child>
                                        <Link :href="route('quotes.show', q.id)">
                                            <Eye />
                                        </Link>
                                    </Button>
                                    <Button v-if="q.can_be_edited" variant="ghost" size="icon" as-child>
                                        <Link :href="route('quotes.edit', q.id)">
                                            <Pencil />
                                        </Link>
                                    </Button>
                                    <Button
                                        v-if="q.can_be_deleted"
                                        variant="ghost"
                                        size="icon"
                                        class="text-destructive hover:text-destructive"
                                        @click="askDelete(q)"
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
                    :icon="FileText"
                    title="Sin cotizaciones"
                    description="Empieza creando tu primera cotización."
                    :action="{ label: 'Nueva cotización', href: route('quotes.create') }"
                />
                <EmptyState v-else :icon="Search" title="Sin resultados" description="No encontramos cotizaciones con ese criterio." />
            </div>

            <Pagination v-if="quotes.data.length > 0" :links="quotes.links" />
        </div>

        <ConfirmDialog
            v-model:open="confirmOpen"
            title="Eliminar cotización"
            :description="`¿Estás seguro de eliminar la cotización «${target?.folio}»? Esta acción no se puede deshacer.`"
            :processing="processing"
            @confirm="handleDelete"
        />
    </AppLayout>
</template>
