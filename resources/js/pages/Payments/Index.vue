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
import { CreditCard, Search, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface PaymentItem {
    id: number;
    folio: string;
    method: string;
    method_label: string;
    amount: number;
    reference: string | null;
    notes: string | null;
    paid_at: string;
    payable_type: 'sale' | 'purchase';
    payable_folio: string;
    payable_href: string | null;
    user: { id: number; name: string };
}

interface PaginatedPayments {
    data: PaymentItem[];
    links: { url: string | null; label: string; active: boolean }[];
}

interface Summary {
    total_amount: number;
    total_count: number;
    by_method: Array<{ method: string; method_label: string; count: number; total: number }>;
}

const props = defineProps<{
    payments: PaginatedPayments;
    summary: Summary;
    methods: Array<{ value: string; label: string }>;
    types: Array<{ value: string; label: string }>;
    filters: {
        search: string;
        method: string | null;
        type: string | null;
        from: string | null;
        to: string | null;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tablero', href: '/dashboard' },
    { title: 'Pagos', href: '/payments' },
];

const searchInput = ref(props.filters.search ?? '');
const method = ref(props.filters.method ?? '');
const type = ref(props.filters.type ?? '');
const fromInput = ref(props.filters.from ?? '');
const toInput = ref(props.filters.to ?? '');

watch(
    () => props.filters,
    (f) => {
        searchInput.value = f.search ?? '';
        method.value = f.method ?? '';
        type.value = f.type ?? '';
        fromInput.value = f.from ?? '';
        toInput.value = f.to ?? '';
    },
);

const hasFilters = () => !!(props.filters.search || props.filters.method || props.filters.type || props.filters.from || props.filters.to);

const applyFilters = () => {
    router.get(
        route('payments.index'),
        {
            search: searchInput.value || undefined,
            method: method.value || undefined,
            type: type.value || undefined,
            from: fromInput.value || undefined,
            to: toInput.value || undefined,
        },
        { preserveScroll: true, preserveState: true },
    );
};

const clearFilters = () => {
    router.get(route('payments.index'), {}, { preserveScroll: true });
};
</script>

<template>
    <Head title="Pagos" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <PageHeader
                title="Pagos"
                description="Tesorería: pagos aplicados a ventas y compras, con totales por método."
            />

            <div class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-xl border border-border/60 bg-card p-4">
                    <p class="text-xs text-muted-foreground">Total recibido</p>
                    <p class="text-2xl font-semibold tabular-nums">{{ formatCurrency(summary.total_amount) }}</p>
                    <p class="text-xs text-muted-foreground">{{ summary.total_count }} pago(s)</p>
                </div>
                <div
                    v-for="entry in summary.by_method"
                    :key="entry.method"
                    class="rounded-xl border border-border/60 bg-card p-4"
                >
                    <p class="text-xs text-muted-foreground">{{ entry.method_label }}</p>
                    <p class="text-2xl font-semibold tabular-nums">{{ formatCurrency(entry.total) }}</p>
                    <p class="text-xs text-muted-foreground">{{ entry.count }} pago(s)</p>
                </div>
            </div>

            <div class="flex flex-wrap items-end gap-2">
                <div class="relative min-w-[200px] flex-1">
                    <Search class="absolute left-2.5 top-2.5 size-4 text-muted-foreground" />
                    <Input
                        v-model="searchInput"
                        placeholder="Buscar por folio, referencia o notas..."
                        class="pl-9"
                        @keyup.enter="applyFilters"
                    />
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs text-muted-foreground">Método</label>
                    <select v-model="method" class="h-9 rounded-md border border-input bg-background px-2 text-sm" @change="applyFilters">
                        <option value="">Todos</option>
                        <option v-for="m in methods" :key="m.value" :value="m.value">{{ m.label }}</option>
                    </select>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs text-muted-foreground">Documento</label>
                    <select v-model="type" class="h-9 rounded-md border border-input bg-background px-2 text-sm" @change="applyFilters">
                        <option value="">Todos</option>
                        <option v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</option>
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

                <Button v-if="hasFilters()" variant="ghost" @click="clearFilters">
                    <X class="mr-1" />
                    Limpiar
                </Button>
            </div>

            <div class="rounded-xl border border-border/60">
                <Table v-if="payments.data.length > 0">
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-28">Folio</TableHead>
                            <TableHead class="w-28">Tipo</TableHead>
                            <TableHead>Documento</TableHead>
                            <TableHead class="w-28">Método</TableHead>
                            <TableHead>Referencia</TableHead>
                            <TableHead class="w-44">Fecha</TableHead>
                            <TableHead>Usuario</TableHead>
                            <TableHead class="w-32 text-right">Monto</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="payment in payments.data" :key="payment.id">
                            <TableCell class="font-mono text-xs">{{ payment.folio }}</TableCell>
                            <TableCell>
                                <Badge :variant="payment.payable_type === 'sale' ? 'success' : 'warning'">
                                    {{ payment.payable_type === 'sale' ? 'Venta' : 'Compra' }}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <Link
                                    v-if="payment.payable_href"
                                    :href="payment.payable_href"
                                    class="font-mono text-xs hover:underline"
                                >
                                    {{ payment.payable_folio }}
                                </Link>
                                <span v-else class="font-mono text-xs text-muted-foreground">{{ payment.payable_folio }}</span>
                            </TableCell>
                            <TableCell>{{ payment.method_label }}</TableCell>
                            <TableCell class="font-mono text-xs text-muted-foreground">
                                {{ payment.reference ?? '—' }}
                            </TableCell>
                            <TableCell class="text-xs text-muted-foreground">
                                {{ formatDateTime(payment.paid_at) }}
                            </TableCell>
                            <TableCell class="text-muted-foreground">{{ payment.user.name }}</TableCell>
                            <TableCell class="text-right font-semibold tabular-nums">
                                {{ formatCurrency(payment.amount) }}
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                <EmptyState
                    v-else-if="!hasFilters()"
                    :icon="CreditCard"
                    title="Sin pagos todavía"
                    description="Los pagos registrados en ventas o compras aparecerán aquí."
                />
                <EmptyState
                    v-else
                    :icon="Search"
                    title="Sin resultados"
                    description="No encontramos pagos con ese criterio."
                />
            </div>

            <Pagination v-if="payments.data.length > 0" :links="payments.links" />
        </div>
    </AppLayout>
</template>
