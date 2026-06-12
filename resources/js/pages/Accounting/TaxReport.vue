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
import { ClipboardList } from 'lucide-vue-next';

defineProps<{
    rows: Array<{
        id: number;
        code: string;
        name: string;
        type: string;
        type_label: string;
        rate: number;
        charged: number;
        credited: number;
        balance: number;
    }>;
    totals: { charged: number; credited: number; balance: number };
    period: { from: string; to: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tablero', href: '/dashboard' },
    { title: 'Contabilidad', href: '/accounting' },
    { title: 'Reporte de impuestos', href: '/accounting/tax-report' },
];
</script>

<template>
    <Head title="Reporte de impuestos" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <PageHeader title="Reporte de impuestos" description="Detalle de IVA, IEPS y retenciones del periodo.">
                <template #actions>
                    <Link :href="route('taxes.index')" class="text-sm text-muted-foreground hover:underline"> Gestionar impuestos → </Link>
                </template>
            </PageHeader>

            <DateRangePicker :from="period.from" :to="period.to" route-name="accounting.tax-report" />

            <Card>
                <CardContent class="p-0">
                    <Table v-if="rows.length > 0">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Impuesto</TableHead>
                                <TableHead>Tipo</TableHead>
                                <TableHead class="text-right">Tasa</TableHead>
                                <TableHead class="text-right">Trasladado</TableHead>
                                <TableHead class="text-right">Acreditable</TableHead>
                                <TableHead class="text-right">Saldo a favor / en contra</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="row in rows" :key="row.id">
                                <TableCell>
                                    <div class="font-medium">{{ row.name }}</div>
                                    <div class="font-mono text-xs text-muted-foreground">{{ row.code }}</div>
                                </TableCell>
                                <TableCell class="text-xs text-muted-foreground">{{ row.type_label }}</TableCell>
                                <TableCell class="text-right tabular-nums">{{ row.rate * 100 }}%</TableCell>
                                <TableCell class="text-right tabular-nums">{{ formatCurrency(row.charged) }}</TableCell>
                                <TableCell class="text-right tabular-nums">{{ formatCurrency(row.credited) }}</TableCell>
                                <TableCell class="text-right font-semibold tabular-nums" :class="row.balance < 0 ? 'text-destructive' : ''">
                                    {{ formatCurrency(row.balance) }}
                                </TableCell>
                            </TableRow>
                            <TableRow class="font-semibold">
                                <TableCell colspan="3">Totales</TableCell>
                                <TableCell class="text-right tabular-nums">{{ formatCurrency(totals.charged) }}</TableCell>
                                <TableCell class="text-right tabular-nums">{{ formatCurrency(totals.credited) }}</TableCell>
                                <TableCell class="text-right tabular-nums">{{ formatCurrency(totals.balance) }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                    <EmptyState
                        v-else
                        :icon="ClipboardList"
                        title="Sin impuestos"
                        description="No hay impuestos configurados o sin movimientos en el periodo."
                    />
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
