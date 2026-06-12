<script setup lang="ts">
import DateRangePicker from '@/components/stockflow/DateRangePicker.vue';
import EmptyState from '@/components/stockflow/EmptyState.vue';
import PageHeader from '@/components/stockflow/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Pagination } from '@/components/ui/pagination';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatCurrency, formatDate } from '@/composables/useFormat';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { BookOpen, Filter } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface LedgerLine {
    id: number;
    entry_date: string;
    entry_folio: string;
    entry_concept: string;
    account: {
        id: number;
        code: string;
        name: string;
        type: string;
        type_label: string;
    };
    description: string | null;
    debit: number;
    credit: number;
    source_href: string | null;
}

interface AccountOption {
    id: number;
    code: string;
    name: string;
    type: string;
    type_label: string;
}

const props = defineProps<{
    lines: { data: LedgerLine[]; links: { url: string | null; label: string; active: boolean }[] };
    accounts: AccountOption[];
    selectedAccountId: number | null;
    period: { from: string; to: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tablero', href: '/dashboard' },
    { title: 'Contabilidad', href: '/accounting' },
    { title: 'Libro mayor', href: '/accounting/ledger' },
];

const accountId = ref<string>(props.selectedAccountId ? String(props.selectedAccountId) : '');

watch(accountId, (val) => {
    router.get(route('accounting.ledger'), { account_id: val || undefined }, { preserveScroll: true, preserveState: true });
});
</script>

<template>
    <Head title="Libro mayor" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <PageHeader title="Libro mayor" description="Detalle de movimientos por cuenta contable.">
                <template #actions>
                    <Link :href="route('accounting.index')" class="text-sm text-muted-foreground hover:underline"> ← Volver al resumen </Link>
                </template>
            </PageHeader>

            <div class="flex flex-wrap items-end gap-2">
                <DateRangePicker :from="period.from" :to="period.to" route-name="accounting.ledger" :extra="{ account_id: selectedAccountId }" />
                <div class="flex flex-col gap-1">
                    <label class="flex items-center gap-1 text-xs text-muted-foreground">
                        <Filter class="size-3" />
                        Cuenta
                    </label>
                    <select v-model="accountId" class="h-9 min-w-[260px] rounded-md border border-input bg-background px-2 text-sm">
                        <option value="">Todas las cuentas</option>
                        <option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.code }} — {{ a.name }}</option>
                    </select>
                </div>
            </div>

            <div class="rounded-xl border border-border/60">
                <Table v-if="lines.data.length > 0">
                    <TableHeader>
                        <TableRow>
                            <TableHead>Fecha</TableHead>
                            <TableHead>Póliza</TableHead>
                            <TableHead>Concepto</TableHead>
                            <TableHead>Cuenta</TableHead>
                            <TableHead class="text-right">Debe</TableHead>
                            <TableHead class="text-right">Haber</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="line in lines.data" :key="line.id">
                            <TableCell class="text-xs text-muted-foreground">{{ formatDate(line.entry_date) }}</TableCell>
                            <TableCell>
                                <Link v-if="line.source_href" :href="line.source_href" class="font-mono text-xs hover:underline">
                                    {{ line.entry_folio }}
                                </Link>
                                <span v-else class="font-mono text-xs">{{ line.entry_folio }}</span>
                            </TableCell>
                            <TableCell>
                                <p class="text-sm">{{ line.entry_concept }}</p>
                                <p v-if="line.description" class="text-xs text-muted-foreground">{{ line.description }}</p>
                            </TableCell>
                            <TableCell>
                                <div class="text-sm font-medium">{{ line.account.code }} — {{ line.account.name }}</div>
                                <Badge variant="secondary" class="mt-1">{{ line.account.type_label }}</Badge>
                            </TableCell>
                            <TableCell class="text-right tabular-nums">{{ line.debit > 0 ? formatCurrency(line.debit) : '—' }}</TableCell>
                            <TableCell class="text-right tabular-nums">{{ line.credit > 0 ? formatCurrency(line.credit) : '—' }}</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                <EmptyState
                    v-else
                    :icon="BookOpen"
                    title="Sin movimientos"
                    description="No hay líneas contables en el periodo o cuenta seleccionada."
                />
            </div>

            <Pagination v-if="lines.data.length > 0" :links="lines.links" />
        </div>
    </AppLayout>
</template>
