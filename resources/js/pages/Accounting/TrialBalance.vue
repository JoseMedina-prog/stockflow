<script setup lang="ts">
import DateRangePicker from '@/components/stockflow/DateRangePicker.vue';
import EmptyState from '@/components/stockflow/EmptyState.vue';
import PageHeader from '@/components/stockflow/PageHeader.vue';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatCurrency } from '@/composables/useFormat';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Scale } from 'lucide-vue-next';

defineProps<{
    rows: Array<{ id: number; code: string; name: string; type_label: string; debit: number; credit: number }>;
    totals: { debit: number; credit: number };
    period: { from: string; to: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tablero', href: '/dashboard' },
    { title: 'Contabilidad', href: '/accounting' },
    { title: 'B. de prueba', href: '/accounting/trial-balance' },
];
</script>

<template>
    <Head title="Balance de prueba" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <PageHeader title="Balance de prueba" description="Saldos de las cuentas de balance (Activo, Pasivo, Capital) en el periodo.">
                <template #actions>
                    <Link :href="route('accounting.index')" class="text-sm text-muted-foreground hover:underline"> ← Volver al resumen </Link>
                </template>
            </PageHeader>

            <DateRangePicker :from="period.from" :to="period.to" route-name="accounting.trial-balance" />

            <Card>
                <CardContent class="p-0">
                    <Table v-if="rows.length > 0">
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-24">Código</TableHead>
                                <TableHead>Cuenta</TableHead>
                                <TableHead>Tipo</TableHead>
                                <TableHead class="text-right">Debe</TableHead>
                                <TableHead class="text-right">Haber</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="row in rows" :key="row.id">
                                <TableCell class="font-mono text-xs">{{ row.code }}</TableCell>
                                <TableCell class="font-medium">{{ row.name }}</TableCell>
                                <TableCell class="text-xs text-muted-foreground">{{ row.type_label }}</TableCell>
                                <TableCell class="text-right tabular-nums">{{ row.debit > 0 ? formatCurrency(row.debit) : '—' }}</TableCell>
                                <TableCell class="text-right tabular-nums">{{ row.credit > 0 ? formatCurrency(row.credit) : '—' }}</TableCell>
                            </TableRow>
                            <TableRow class="font-semibold">
                                <TableCell colspan="3">Totales</TableCell>
                                <TableCell class="text-right tabular-nums">{{ formatCurrency(totals.debit) }}</TableCell>
                                <TableCell class="text-right tabular-nums">{{ formatCurrency(totals.credit) }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                    <EmptyState v-else :icon="Scale" title="Sin saldos" description="No hay cuentas con movimientos en el periodo." />
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
