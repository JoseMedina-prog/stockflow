<script setup lang="ts">
import DateRangePicker from '@/components/stockflow/DateRangePicker.vue';
import EmptyState from '@/components/stockflow/EmptyState.vue';
import PageHeader from '@/components/stockflow/PageHeader.vue';
import StatCard from '@/components/stockflow/StatCard.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatCurrency, formatDate } from '@/composables/useFormat';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { BookOpen, CircleDollarSign, ClipboardList, FileSpreadsheet, Landmark, Scale, Wallet } from 'lucide-vue-next';

interface TaxSummary {
    id: number;
    code: string;
    name: string;
    type: string;
    type_label: string;
    rate: number;
    account: { code: string; name: string } | null;
    charged: number;
    credited: number;
    balance: number;
}

interface RecentEntry {
    id: number;
    folio: string;
    entry_date: string;
    concept: string;
    status: string;
    status_label: string;
    total_debit: number;
    total_credit: number;
    lines_count: number;
    source_href: string | null;
}

const props = defineProps<{
    summary: {
        assets: number;
        liabilities: number;
        equity: number;
        revenue: number;
        expense: number;
        net_income: number;
        equity_total: number;
        balanced: boolean;
    };
    taxSummary: TaxSummary[];
    recentEntries: RecentEntry[];
    period: { from: string; to: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tablero', href: '/dashboard' },
    { title: 'Contabilidad', href: '/accounting' },
];
</script>

<template>
    <Head title="Contabilidad" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <PageHeader
                title="Contabilidad"
                description="Resumen contable del periodo, catálogo de cuentas e impuestos."
            >
                <template #actions>
                    <Button variant="outline" as-child>
                        <Link :href="route('accounting.ledger')">
                            <BookOpen class="mr-1" />
                            Libro mayor
                        </Link>
                    </Button>
                    <Button variant="outline" as-child>
                        <Link :href="route('accounting.trial-balance')">
                            <Scale class="mr-1" />
                            B. de prueba
                        </Link>
                    </Button>
                    <Button variant="outline" as-child>
                        <Link :href="route('accounting.income-statement')">
                            <FileSpreadsheet class="mr-1" />
                            E. de resultados
                        </Link>
                    </Button>
                    <Button as-child>
                        <Link :href="route('accounting.balance-sheet')">
                            <Landmark class="mr-1" />
                            Balance general
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <div class="flex flex-wrap items-end gap-2">
                <DateRangePicker
                    :from="period.from"
                    :to="period.to"
                    route-name="accounting.index"
                />
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard
                    title="Activos"
                    :value="formatCurrency(summary.assets)"
                    description="Cuentas de activo"
                    :icon="Wallet"
                    accent="info"
                />
                <StatCard
                    title="Pasivos"
                    :value="formatCurrency(summary.liabilities)"
                    description="Cuentas de pasivo"
                    :icon="CircleDollarSign"
                    accent="warning"
                />
                <StatCard
                    title="Capital contable"
                    :value="formatCurrency(summary.equity_total)"
                    description="Capital + Utilidad del ejercicio"
                    :icon="Landmark"
                    accent="primary"
                />
                <StatCard
                    title="Resultado del ejercicio"
                    :value="formatCurrency(summary.net_income)"
                    :description="`Ingresos ${formatCurrency(summary.revenue)} - Egresos ${formatCurrency(summary.expense)}`"
                    :icon="FileSpreadsheet"
                    :accent="summary.net_income >= 0 ? 'success' : 'destructive'"
                />
            </div>

            <Card v-if="summary.balanced" class="border-emerald-200 bg-emerald-50 dark:border-emerald-900/50 dark:bg-emerald-950/30">
                <CardContent class="flex items-center gap-2 py-3 text-sm text-emerald-700 dark:text-emerald-300">
                    <Badge variant="success">Cuadrado</Badge>
                    La ecuación contable (Activo = Pasivo + Capital) está balanceada en el periodo.
                </CardContent>
            </Card>
            <Card v-else class="border-amber-200 bg-amber-50 dark:border-amber-900/50 dark:bg-amber-950/30">
                <CardContent class="flex items-center gap-2 py-3 text-sm text-amber-700 dark:text-amber-300">
                    <Badge variant="warning">Diferencia</Badge>
                    La ecuación contable no cuadra. Revisa los asientos del periodo.
                </CardContent>
            </Card>

            <div class="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Impuestos del periodo</CardTitle>
                        <CardDescription>IVA, IEPS, retenciones y otros configurados.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <EmptyState
                            v-if="taxSummary.length === 0"
                            :icon="ClipboardList"
                            title="Sin impuestos configurados"
                            description="Configura tus impuestos para empezar a registrar operaciones gravadas."
                            :action="{ label: 'Ir a Impuestos', href: route('taxes.index') }"
                        />
                        <Table v-else>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Impuesto</TableHead>
                                    <TableHead class="text-right">Tasa</TableHead>
                                    <TableHead class="text-right">Trasladado</TableHead>
                                    <TableHead class="text-right">Acreditable</TableHead>
                                    <TableHead class="text-right">Saldo</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="t in taxSummary" :key="t.id">
                                    <TableCell>
                                        <div class="font-medium">{{ t.name }}</div>
                                        <div class="font-mono text-xs text-muted-foreground">{{ t.code }}</div>
                                    </TableCell>
                                    <TableCell class="text-right tabular-nums">{{ t.rate * 100 }}%</TableCell>
                                    <TableCell class="text-right tabular-nums">{{ formatCurrency(t.charged) }}</TableCell>
                                    <TableCell class="text-right tabular-nums">{{ formatCurrency(t.credited) }}</TableCell>
                                    <TableCell class="text-right font-semibold tabular-nums">
                                        <span :class="t.balance < 0 ? 'text-destructive' : ''">
                                            {{ formatCurrency(t.balance) }}
                                        </span>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Últimos asientos contables</CardTitle>
                        <CardDescription>Polizas registradas recientemente.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <EmptyState
                            v-if="recentEntries.length === 0"
                            :icon="BookOpen"
                            title="Sin asientos"
                            description="Las ventas, compras y pagos generarán asientos automáticamente."
                        />
                        <div v-else class="space-y-2">
                            <Link
                                v-for="e in recentEntries"
                                :key="e.id"
                                :href="e.source_href ?? '#'"
                                class="flex items-center justify-between gap-3 rounded-md border border-border/60 p-3 text-sm transition-colors hover:border-primary/40"
                            >
                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-medium">{{ e.concept }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        <span class="font-mono">{{ e.folio }}</span>
                                        · {{ formatDate(e.entry_date) }}
                                        · {{ e.lines_count }} línea(s)
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold tabular-nums">{{ formatCurrency(e.total_debit) }}</p>
                                    <Badge :variant="e.status === 'posted' ? 'success' : 'secondary'">{{ e.status_label }}</Badge>
                                </div>
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
