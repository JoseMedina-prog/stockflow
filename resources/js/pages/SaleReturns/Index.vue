<script setup lang="ts">
import EmptyState from '@/components/stockflow/EmptyState.vue';
import PageHeader from '@/components/stockflow/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Pagination } from '@/components/ui/pagination';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { formatCurrency, formatDateTime } from '@/composables/useFormat';
import { Eye, Search, Undo2, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface ReturnItem {
    id: number;
    folio: string;
    status: string;
    status_label: string;
    status_badge: string;
    total: number;
    items_count: number;
    reason: string;
    created_at: string;
    sale: { id: number; folio: string } | null;
    customer: { id: number; name: string } | null;
    user: { id: number; name: string };
    approver: { id: number; name: string } | null;
}

interface PaginatedReturns {
    data: ReturnItem[];
    links: { url: string | null; label: string; active: boolean }[];
}

interface StatusOption {
    value: string;
    label: string;
}

const props = defineProps<{
    returns: PaginatedReturns;
    statuses: StatusOption[];
    filters: {
        search: string;
        status: string | null;
        from: string | null;
        to: string | null;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tablero', href: '/dashboard' },
    { title: 'Devoluciones', href: '/returns' },
];

const searchInput = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');
const fromInput = ref(props.filters.from ?? '');
const toInput = ref(props.filters.to ?? '');

watch(
    () => props.filters,
    (f) => {
        searchInput.value = f.search ?? '';
        status.value = f.status ?? '';
        fromInput.value = f.from ?? '';
        toInput.value = f.to ?? '';
    },
);

const hasFilters = () => !!(props.filters.search || props.filters.status || props.filters.from || props.filters.to);

const applyFilters = () => {
    router.get(
        route('sale-returns.index'),
        {
            search: searchInput.value || undefined,
            status: status.value || undefined,
            from: fromInput.value || undefined,
            to: toInput.value || undefined,
        },
        { preserveScroll: true, preserveState: true },
    );
};

const clearFilters = () => {
    router.get(route('sale-returns.index'), {}, { preserveScroll: true });
};
</script>

<template>
    <Head title="Devoluciones" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <PageHeader
                title="Devoluciones"
                description="Gestiona las devoluciones de productos a clientes."
            />

            <div class="flex flex-wrap items-end gap-2">
                <div class="relative min-w-[200px] flex-1">
                    <Search class="absolute left-2.5 top-2.5 size-4 text-muted-foreground" />
                    <Input
                        v-model="searchInput"
                        placeholder="Buscar por folio, cliente o motivo..."
                        class="pl-9"
                        @keyup.enter="applyFilters"
                    />
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs text-muted-foreground">Estado</label>
                    <select
                        v-model="status"
                        class="h-9 rounded-md border border-input bg-background px-2 text-sm"
                        @change="applyFilters"
                    >
                        <option value="">Todos</option>
                        <option v-for="st in statuses" :key="st.value" :value="st.value">
                            {{ st.label }}
                        </option>
                    </select>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs text-muted-foreground">Desde</label>
                    <Input v-model="fromInput" type="date" @change="applyFilters" />
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs text-muted-foreground">Hasta</label>
                    <Input v-model="toInput" type="date" @change="applyFilters" />
                </div>

                <button
                    v-if="hasFilters()"
                    class="inline-flex h-9 items-center rounded-md px-3 text-sm text-muted-foreground hover:bg-accent"
                    @click="clearFilters"
                >
                    <X class="mr-1" />
                    Limpiar
                </button>
            </div>

            <div class="rounded-xl border border-border/60">
                <Table v-if="returns.data.length > 0">
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-32">Folio</TableHead>
                            <TableHead>Venta</TableHead>
                            <TableHead>Cliente</TableHead>
                            <TableHead class="w-32">Estado</TableHead>
                            <TableHead>Motivo</TableHead>
                            <TableHead class="w-44">Fecha</TableHead>
                            <TableHead class="w-32 text-right">Total</TableHead>
                            <TableHead class="w-16 text-right">Ver</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="returnItem in returns.data" :key="returnItem.id">
                            <TableCell class="font-mono text-xs">{{ returnItem.folio }}</TableCell>
                            <TableCell>
                                <Link
                                    v-if="returnItem.sale"
                                    :href="route('sales.show', returnItem.sale.id)"
                                    class="font-mono text-xs hover:underline"
                                >
                                    {{ returnItem.sale.folio }}
                                </Link>
                                <span v-else class="text-muted-foreground">—</span>
                            </TableCell>
                            <TableCell>{{ returnItem.customer?.name ?? 'Consumidor final' }}</TableCell>
                            <TableCell>
                                <Badge :variant="returnItem.status_badge as any">{{ returnItem.status_label }}</Badge>
                            </TableCell>
                            <TableCell class="max-w-xs truncate text-muted-foreground">
                                {{ returnItem.reason }}
                            </TableCell>
                            <TableCell class="text-xs text-muted-foreground">
                                {{ formatDateTime(returnItem.created_at) }}
                            </TableCell>
                            <TableCell class="text-right font-semibold tabular-nums">
                                {{ formatCurrency(returnItem.total) }}
                            </TableCell>
                            <TableCell class="text-right">
                                <Link :href="route('sale-returns.show', returnItem.id)" class="inline-flex size-8 items-center justify-center rounded-md hover:bg-accent">
                                    <Eye class="size-4" />
                                </Link>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                <EmptyState
                    v-else-if="!hasFilters()"
                    :icon="Undo2"
                    title="Sin devoluciones todavía"
                    description="Las devoluciones creadas desde las ventas aparecerán aquí."
                />
                <EmptyState
                    v-else
                    :icon="Search"
                    title="Sin resultados"
                    description="No encontramos devoluciones con ese criterio."
                />
            </div>

            <Pagination v-if="returns.data.length > 0" :links="returns.links" />
        </div>
    </AppLayout>
</template>
