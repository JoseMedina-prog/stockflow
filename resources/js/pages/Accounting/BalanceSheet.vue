<script setup lang="ts">
import DateRangePicker from '@/components/stockflow/DateRangePicker.vue';
import PageHeader from '@/components/stockflow/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatCurrency } from '@/composables/useFormat';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    assets: Array<{ id: number; code: string; name: string; balance: number }>;
    liabilities: Array<{ id: number; code: string; name: string; balance: number }>;
    equity: Array<{ id: number; code: string; name: string; balance: number }>;
    netIncome: number;
    totals: { assets: number; liabilities: number; equity: number; liabilities_plus_equity: number; balanced: boolean };
    period: { from: string; to: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tablero', href: '/dashboard' },
    { title: 'Contabilidad', href: '/accounting' },
    { title: 'Balance general', href: '/accounting/balance-sheet' },
];
</script>

<template>
    <Head title="Balance general" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <PageHeader title="Balance general" description="Estado de situación financiera al cierre del periodo.">
                <template #actions>
                    <Link :href="route('accounting.index')" class="text-sm text-muted-foreground hover:underline"> ← Volver al resumen </Link>
                </template>
            </PageHeader>

            <DateRangePicker :from="period.from" :to="period.to" route-name="accounting.balance-sheet" />

            <div class="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            Activo
                            <Badge variant="secondary">{{ formatCurrency(totals.assets) }}</Badge>
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="p-0">
                        <Table v-if="assets.length > 0">
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Cuenta</TableHead>
                                    <TableHead class="text-right">Saldo</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="a in assets" :key="a.id">
                                    <TableCell>
                                        <div class="font-mono text-xs text-muted-foreground">{{ a.code }}</div>
                                        <div class="font-medium">{{ a.name }}</div>
                                    </TableCell>
                                    <TableCell class="text-right tabular-nums">{{ formatCurrency(a.balance) }}</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            Pasivo + Capital
                            <Badge variant="secondary">{{ formatCurrency(totals.liabilities_plus_equity) }}</Badge>
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="p-0">
                        <Table v-if="liabilities.length > 0 || equity.length > 0">
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Cuenta</TableHead>
                                    <TableHead class="text-right">Saldo</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="l in liabilities" :key="l.id">
                                    <TableCell>
                                        <div class="font-mono text-xs text-muted-foreground">{{ l.code }}</div>
                                        <div class="font-medium">{{ l.name }}</div>
                                    </TableCell>
                                    <TableCell class="text-right tabular-nums">{{ formatCurrency(l.balance) }}</TableCell>
                                </TableRow>
                                <TableRow v-for="e in equity" :key="e.id">
                                    <TableCell>
                                        <div class="font-mono text-xs text-muted-foreground">{{ e.code }}</div>
                                        <div class="font-medium">{{ e.name }}</div>
                                    </TableCell>
                                    <TableCell class="text-right tabular-nums">{{ formatCurrency(e.balance) }}</TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell>
                                        <div class="text-xs text-muted-foreground">Utilidad del ejercicio</div>
                                        <div class="font-medium">Resultado del periodo</div>
                                    </TableCell>
                                    <TableCell
                                        class="text-right font-semibold tabular-nums"
                                        :class="netIncome < 0 ? 'text-destructive' : 'text-emerald-600'"
                                    >
                                        {{ formatCurrency(netIncome) }}
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>

            <Card v-if="totals.balanced" class="border-emerald-200 bg-emerald-50 dark:border-emerald-900/50 dark:bg-emerald-950/30">
                <CardContent class="flex items-center gap-2 py-3 text-sm text-emerald-700 dark:text-emerald-300">
                    <Badge variant="success">Cuadrado</Badge>
                    Activo = Pasivo + Capital. La contabilidad cuadra al {{ formatCurrency(totals.assets) }}.
                </CardContent>
            </Card>
            <Card v-else class="border-amber-200 bg-amber-50 dark:border-amber-900/50 dark:bg-amber-950/30">
                <CardContent class="flex items-center gap-2 py-3 text-sm text-amber-700 dark:text-amber-300">
                    <Badge variant="warning">Diferencia</Badge>
                    Activo ({{ formatCurrency(totals.assets) }}) ≠ Pasivo + Capital ({{ formatCurrency(totals.liabilities_plus_equity) }}).
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
