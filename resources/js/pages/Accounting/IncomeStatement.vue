<script setup lang="ts">
import DateRangePicker from '@/components/stockflow/DateRangePicker.vue';
import EmptyState from '@/components/stockflow/EmptyState.vue';
import PageHeader from '@/components/stockflow/PageHeader.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatCurrency } from '@/composables/useFormat';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { FileSpreadsheet } from 'lucide-vue-next';

defineProps<{
    revenue: Array<{ id: number; code: string; name: string; amount: number }>;
    expense: Array<{ id: number; code: string; name: string; amount: number }>;
    totals: { revenue: number; expense: number; net_income: number };
    period: { from: string; to: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tablero', href: '/dashboard' },
    { title: 'Contabilidad', href: '/accounting' },
    { title: 'E. de resultados', href: '/accounting/income-statement' },
];
</script>

<template>
    <Head title="Estado de resultados" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <PageHeader
                title="Estado de resultados"
                description="Ingresos, egresos y utilidad del ejercicio."
            >
                <template #actions>
                    <Link :href="route('accounting.index')" class="text-sm text-muted-foreground hover:underline">
                        ← Volver al resumen
                    </Link>
                </template>
            </PageHeader>

            <DateRangePicker :from="period.from" :to="period.to" route-name="accounting.income-statement" />

            <div class="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base text-emerald-600">Ingresos</CardTitle>
                    </CardHeader>
                    <CardContent class="p-0">
                        <Table v-if="revenue.length > 0">
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Cuenta</TableHead>
                                    <TableHead class="text-right">Monto</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="r in revenue" :key="r.id">
                                    <TableCell>
                                        <div class="font-mono text-xs text-muted-foreground">{{ r.code }}</div>
                                        <div class="font-medium">{{ r.name }}</div>
                                    </TableCell>
                                    <TableCell class="text-right tabular-nums">{{ formatCurrency(r.amount) }}</TableCell>
                                </TableRow>
                                <TableRow class="font-semibold">
                                    <TableCell>Total ingresos</TableCell>
                                    <TableCell class="text-right tabular-nums">{{ formatCurrency(totals.revenue) }}</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                        <EmptyState v-else :icon="FileSpreadsheet" title="Sin ingresos" description="No hay ingresos registrados en el periodo." />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base text-rose-600">Egresos</CardTitle>
                    </CardHeader>
                    <CardContent class="p-0">
                        <Table v-if="expense.length > 0">
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Cuenta</TableHead>
                                    <TableHead class="text-right">Monto</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="e in expense" :key="e.id">
                                    <TableCell>
                                        <div class="font-mono text-xs text-muted-foreground">{{ e.code }}</div>
                                        <div class="font-medium">{{ e.name }}</div>
                                    </TableCell>
                                    <TableCell class="text-right tabular-nums">{{ formatCurrency(e.amount) }}</TableCell>
                                </TableRow>
                                <TableRow class="font-semibold">
                                    <TableCell>Total egresos</TableCell>
                                    <TableCell class="text-right tabular-nums">{{ formatCurrency(totals.expense) }}</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                        <EmptyState v-else :icon="FileSpreadsheet" title="Sin egresos" description="No hay egresos registrados en el periodo." />
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardContent class="flex flex-col items-end gap-1 p-4">
                    <div class="flex w-full max-w-md items-center justify-between text-sm">
                        <span class="text-muted-foreground">Ingresos</span>
                        <span class="tabular-nums">{{ formatCurrency(totals.revenue) }}</span>
                    </div>
                    <div class="flex w-full max-w-md items-center justify-between text-sm">
                        <span class="text-muted-foreground">(-) Egresos</span>
                        <span class="tabular-nums">{{ formatCurrency(totals.expense) }}</span>
                    </div>
                    <div class="my-2 h-px w-full max-w-md bg-border"></div>
                    <div class="flex w-full max-w-md items-center justify-between text-lg font-semibold">
                        <span>Utilidad del ejercicio</span>
                        <span class="tabular-nums" :class="totals.net_income < 0 ? 'text-destructive' : 'text-emerald-600'">
                            {{ formatCurrency(totals.net_income) }}
                        </span>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
