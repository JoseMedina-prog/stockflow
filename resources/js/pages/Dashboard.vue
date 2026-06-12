<script setup lang="ts">
import CashflowChart from '@/components/stockflow/CashflowChart.vue';
import EmptyState from '@/components/stockflow/EmptyState.vue';
import Sparkline from '@/components/stockflow/Sparkline.vue';
import StatCard from '@/components/stockflow/StatCard.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { formatCurrency, formatDateTime } from '@/composables/useFormat';
import {
    AlertTriangle,
    ArrowDown,
    ArrowRight,
    ArrowUp,
    DollarSign,
    Package,
    ShoppingBag,
    ShoppingCart,
    TrendingUp,
    Users,
} from 'lucide-vue-next';

type RecentSale = { id: number; sale_date: string; total: number; customer: string | null };
type LowStockProduct = {
    id: number;
    name: string;
    sku: string;
    stock: number;
    min_stock: number;
    category: string | null;
};
type AccountReceivable = {
    id: number;
    total: number;
    paid_amount: number;
    balance: number;
    sale_date: string;
    days_overdue: number;
    customer: { id: number; name: string } | null;
};
type AccountPayable = {
    id: number;
    folio: string;
    total: number;
    paid_amount: number;
    balance: number;
    purchase_date: string;
    days_overdue: number;
    supplier: { id: number; name: string };
};
type TopCustomer = { id: number; name: string; total_spent: number; sales_count: number };
type TopProduct = { id: number; name: string; sku: string; total_quantity: number; total_revenue: number };
type ChartPoint = { date: string; label: string; sales: number; purchases: number; cashflow: number };
type MyTask = {
    id: number;
    title: string;
    due_date: string | null;
    priority: string;
    priority_label: string;
    priority_badge: string;
    status: string;
    status_label: string;
    status_badge: string;
    is_overdue: boolean;
    is_due_today: boolean;
};

const props = withDefaults(
    defineProps<{
        stats: {
            sales_today_total: number;
            sales_today_count: number;
            sales_month_total: number;
            sales_month_count: number;
            purchases_today_total: number;
            purchases_today_count: number;
            purchases_month_total: number;
            total_products: number;
            low_stock_count: number;
            total_customers: number;
            receivable_total: number;
            receivable_count: number;
            payable_total: number;
            payable_count: number;
        };
        chart?: ChartPoint[];
        recentSales?: RecentSale[];
        lowStockProducts?: LowStockProduct[];
        accountsReceivable?: AccountReceivable[];
        accountsPayable?: AccountPayable[];
        topCustomers?: TopCustomer[];
        topProducts?: TopProduct[];
        myTasks?: MyTask[];
        myTasksSummary?: { open: number; overdue: number; today: number };
    }>(),
    {
        chart: () => [],
        recentSales: () => [],
        lowStockProducts: () => [],
        accountsReceivable: () => [],
        accountsPayable: () => [],
        topCustomers: () => [],
        topProducts: () => [],
        myTasks: () => [],
        myTasksSummary: () => ({ open: 0, overdue: 0, today: 0 }),
    },
);

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Tablero', href: '/dashboard' }];

const page = usePage();
const userName = computed(() => {
    const user = page.props.auth?.user as { name?: string } | undefined;
    return user?.name?.split(' ')[0] ?? 'usuario';
});

const greeting = computed(() => {
    const hour = new Date().getHours();
    if (hour < 12) return 'Buenos días';
    if (hour < 19) return 'Buenas tardes';
    return 'Buenas noches';
});

const todayLabel = computed(() =>
    new Intl.DateTimeFormat('es-MX', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date()),
);

const sparklineSales = computed(() => props.chart.map((d) => d.sales));
const sparklinePurchases = computed(() => props.chart.map((d) => d.purchases));
const sparklineCashflow = computed(() => props.chart.map((d) => d.cashflow));

const cashflowTotal = computed(() => props.chart.reduce((acc, d) => acc + d.cashflow, 0));
</script>

<template>
    <Head title="Tablero" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <div class="flex flex-col gap-1">
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ greeting }}, <span class="text-primary">{{ userName }}</span>
                </h1>
                <p class="text-sm text-muted-foreground capitalize">{{ todayLabel }}</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6">
                <StatCard
                    title="Ventas de hoy"
                    :value="formatCurrency(stats.sales_today_total)"
                    :description="`${stats.sales_today_count} ${stats.sales_today_count === 1 ? 'venta' : 'ventas'}`"
                    :icon="ShoppingCart"
                    accent="primary"
                >
                    <template #footer>
                        <div class="flex items-end justify-between gap-3">
                            <Sparkline :data="sparklineSales" :height="32" />
                            <span class="text-xs font-medium text-muted-foreground">7 días</span>
                        </div>
                    </template>
                </StatCard>

                <StatCard
                    title="Compras de hoy"
                    :value="formatCurrency(stats.purchases_today_total)"
                    :description="`${stats.purchases_today_count} ${stats.purchases_today_count === 1 ? 'compra' : 'compras'}`"
                    :icon="ShoppingBag"
                    accent="info"
                >
                    <template #footer>
                        <div class="flex items-end justify-between gap-3">
                            <Sparkline :data="sparklinePurchases" :height="32" stroke-class="text-red-500" />
                            <span class="text-xs font-medium text-muted-foreground">7 días</span>
                        </div>
                    </template>
                </StatCard>

                <StatCard
                    title="Por cobrar"
                    :value="formatCurrency(stats.receivable_total)"
                    :description="`${stats.receivable_count} ${stats.receivable_count === 1 ? 'venta pendiente' : 'ventas pendientes'}`"
                    :icon="ArrowUp"
                    :accent="stats.receivable_total > 0 ? 'warning' : 'success'"
                />
                <StatCard
                    title="Por pagar"
                    :value="formatCurrency(stats.payable_total)"
                    :description="`${stats.payable_count} ${stats.payable_count === 1 ? 'compra pendiente' : 'compras pendientes'}`"
                    :icon="ArrowDown"
                    :accent="stats.payable_total > 0 ? 'destructive' : 'success'"
                />
                <StatCard
                    title="Stock bajo"
                    :value="stats.low_stock_count"
                    :description="stats.low_stock_count > 0 ? 'Requieren atención' : 'Todo en orden'"
                    :icon="AlertTriangle"
                    :accent="stats.low_stock_count > 0 ? 'warning' : 'success'"
                />
                <StatCard
                    title="Ingresos del mes"
                    :value="formatCurrency(stats.sales_month_total)"
                    :description="`${stats.sales_month_count} ${stats.sales_month_count === 1 ? 'venta en el mes' : 'ventas en el mes'}`"
                    :icon="TrendingUp"
                    accent="success"
                />
            </div>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-3">
                    <div>
                        <CardTitle class="text-base">Flujo de caja</CardTitle>
                        <CardDescription>
                            Ventas vs compras de los últimos 7 días ·
                            <span :class="cashflowTotal >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-destructive'">
                                neto {{ formatCurrency(cashflowTotal) }}
                            </span>
                        </CardDescription>
                    </div>
                    <div class="flex items-center gap-3 text-xs text-muted-foreground">
                        <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-emerald-600/80" />Ventas</span>
                        <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-red-500/70" />Compras</span>
                        <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-blue-500" />Neto</span>
                    </div>
                </CardHeader>
                <CardContent>
                    <CashflowChart :data="chart" :height="180" />
                </CardContent>
            </Card>

            <div class="grid gap-4 lg:grid-cols-3">
                <Card class="lg:col-span-2">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-4">
                        <div>
                            <CardTitle class="text-base">Ventas recientes</CardTitle>
                            <CardDescription>Últimas operaciones registradas.</CardDescription>
                        </div>
                        <Button variant="ghost" size="sm" as-child>
                            <Link :href="route('sales.index')">
                                Ver todas
                                <ArrowRight />
                            </Link>
                        </Button>
                    </CardHeader>
                    <CardContent>
                        <Table v-if="recentSales.length > 0">
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-16">Folio</TableHead>
                                    <TableHead>Fecha</TableHead>
                                    <TableHead>Cliente</TableHead>
                                    <TableHead class="text-right">Total</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="sale in recentSales" :key="sale.id">
                                    <TableCell class="font-mono text-xs">#{{ sale.id }}</TableCell>
                                    <TableCell>{{ formatDateTime(sale.sale_date) }}</TableCell>
                                    <TableCell>{{ sale.customer ?? 'Consumidor final' }}</TableCell>
                                    <TableCell class="text-right font-semibold tabular-nums">
                                        {{ formatCurrency(sale.total) }}
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                        <EmptyState
                            v-else
                            title="Aún no hay ventas"
                            description="Registra tu primera venta para ver el resumen aquí."
                            :action="{ label: 'Nueva venta', href: route('sales.create') }"
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-3">
                        <div>
                            <CardTitle class="text-base">Mis tareas</CardTitle>
                            <CardDescription class="text-xs">
                                {{ myTasksSummary.open }} pendientes ·
                                <span v-if="myTasksSummary.today > 0" class="text-amber-600 dark:text-amber-400">{{ myTasksSummary.today }} hoy</span>
                                <span v-else class="text-muted-foreground">0 hoy</span>
                                ·
                                <span v-if="myTasksSummary.overdue > 0" class="text-destructive">{{ myTasksSummary.overdue }} vencidas</span>
                                <span v-else class="text-muted-foreground">0 vencidas</span>
                            </CardDescription>
                        </div>
                        <Button variant="ghost" size="sm" as-child>
                            <Link :href="route('tasks.index', { mine: 1 })">
                                Ver todas
                                <ArrowRight />
                            </Link>
                        </Button>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <Link
                            v-for="t in myTasks"
                            :key="t.id"
                            :href="route('tasks.show', t.id)"
                            class="block rounded-md border border-border/60 px-3 py-2 text-sm transition-colors hover:border-primary/40"
                            :class="t.is_overdue ? 'border-destructive/40 bg-destructive/5' : ''"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <p class="line-clamp-2 font-medium">{{ t.title }}</p>
                                <Badge :variant="t.priority_badge as any" class="shrink-0">{{ t.priority_label[0] }}</Badge>
                            </div>
                            <p v-if="t.due_date" class="mt-1 text-xs" :class="t.is_overdue ? 'text-destructive font-medium' : 'text-muted-foreground'">
                                {{ t.due_date }}
                                <span v-if="t.is_due_today" class="text-amber-600 dark:text-amber-400">· vence hoy</span>
                                <span v-else-if="t.is_overdue" class="text-destructive">· vencida</span>
                            </p>
                        </Link>
                        <div
                            v-if="myTasks.length === 0"
                            class="rounded-md border border-dashed border-border/60 p-4 text-center text-xs text-muted-foreground"
                        >
                            Sin tareas pendientes.
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-3">
                        <CardTitle class="text-base">Top productos</CardTitle>
                        <Button variant="ghost" size="sm" as-child>
                            <Link :href="route('reports.index')">
                                <ArrowRight />
                            </Link>
                        </Button>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <div
                            v-for="p in topProducts"
                            :key="p.id"
                            class="flex items-center justify-between rounded-md border border-border/60 px-3 py-2"
                        >
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">{{ p.name }}</p>
                                <p class="truncate font-mono text-xs text-muted-foreground">{{ p.sku }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold tabular-nums">{{ p.total_quantity }} und</p>
                                <p class="text-xs text-muted-foreground tabular-nums">{{ formatCurrency(p.total_revenue) }}</p>
                            </div>
                        </div>
                        <EmptyState
                            v-if="topProducts.length === 0"
                            title="Sin datos"
                            description="No hay ventas registradas para mostrar top productos."
                        />
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-3">
                        <div>
                            <CardTitle class="text-base">Cuentas por cobrar</CardTitle>
                            <CardDescription>Ventas con saldo pendiente.</CardDescription>
                        </div>
                        <Button variant="ghost" size="sm" as-child>
                            <Link :href="route('sales.index', { status: 'pending' })">
                                <ArrowRight />
                            </Link>
                        </Button>
                    </CardHeader>
                    <CardContent>
                        <Table v-if="accountsReceivable.length > 0">
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-16">Folio</TableHead>
                                    <TableHead>Cliente</TableHead>
                                    <TableHead>Fecha</TableHead>
                                    <TableHead class="text-right">Saldo</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="ar in accountsReceivable" :key="ar.id">
                                    <TableCell>
                                        <Link :href="route('sales.show', ar.id)" class="font-mono text-xs hover:underline">
                                            #{{ ar.id }}
                                        </Link>
                                    </TableCell>
                                    <TableCell class="text-sm">{{ ar.customer?.name ?? 'Consumidor final' }}</TableCell>
                                    <TableCell>
                                        <div class="text-sm">{{ ar.sale_date }}</div>
                                        <div v-if="ar.days_overdue > 0" class="text-xs text-destructive">
                                            {{ ar.days_overdue }} días vencidos
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-right font-semibold tabular-nums text-destructive">
                                        {{ formatCurrency(ar.balance) }}
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                        <EmptyState
                            v-else
                            :icon="DollarSign"
                            title="¡Sin cuentas por cobrar!"
                            description="Todas las ventas están al corriente."
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-3">
                        <div>
                            <CardTitle class="text-base">Cuentas por pagar</CardTitle>
                            <CardDescription>Compras con saldo pendiente.</CardDescription>
                        </div>
                        <Button variant="ghost" size="sm" as-child>
                            <Link :href="route('purchases.index', { status: 'received' })">
                                <ArrowRight />
                            </Link>
                        </Button>
                    </CardHeader>
                    <CardContent>
                        <Table v-if="accountsPayable.length > 0">
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Folio</TableHead>
                                    <TableHead>Proveedor</TableHead>
                                    <TableHead>Fecha</TableHead>
                                    <TableHead class="text-right">Saldo</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="ap in accountsPayable" :key="ap.id">
                                    <TableCell>
                                        <Link :href="route('purchases.index')" class="font-mono text-xs hover:underline">
                                            {{ ap.folio }}
                                        </Link>
                                    </TableCell>
                                    <TableCell class="text-sm">{{ ap.supplier.name }}</TableCell>
                                    <TableCell>
                                        <div class="text-sm">{{ ap.purchase_date }}</div>
                                        <div v-if="ap.days_overdue > 0" class="text-xs text-destructive">
                                            {{ ap.days_overdue }} días vencidos
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-right font-semibold tabular-nums text-destructive">
                                        {{ formatCurrency(ap.balance) }}
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                        <EmptyState
                            v-else
                            :icon="DollarSign"
                            title="¡Sin cuentas por pagar!"
                            description="Todas las compras están liquidadas."
                        />
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-3">
                        <CardTitle class="text-base">Top clientes</CardTitle>
                        <Button variant="ghost" size="sm" as-child>
                            <Link :href="route('customers.index')">
                                <ArrowRight />
                            </Link>
                        </Button>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <div
                            v-for="c in topCustomers"
                            :key="c.id"
                            class="flex items-center justify-between rounded-md border border-border/60 px-3 py-2"
                        >
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">{{ c.name }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ c.sales_count }} {{ c.sales_count === 1 ? 'compra' : 'compras' }}
                                </p>
                            </div>
                            <span class="text-sm font-semibold tabular-nums">{{ formatCurrency(c.total_spent) }}</span>
                        </div>
                        <EmptyState
                            v-if="topCustomers.length === 0"
                            title="Sin datos"
                            description="No hay clientes con ventas registradas."
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-base">Clientes</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center gap-3">
                            <div class="flex size-10 items-center justify-center rounded-xl bg-violet-500/10 text-violet-700 ring-1 ring-violet-500/20 dark:text-violet-300">
                                <Users class="size-5" />
                            </div>
                            <div>
                                <p class="text-2xl font-semibold tabular-nums">{{ stats.total_customers }}</p>
                                <p class="text-xs text-muted-foreground">Registrados en el sistema</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card v-if="stats.low_stock_count > 0">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-3">
                        <CardTitle class="text-base">Stock bajo</CardTitle>
                        <Button variant="ghost" size="sm" as-child>
                            <Link :href="route('products.index')">
                                <ArrowRight />
                            </Link>
                        </Button>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <div
                            v-for="p in lowStockProducts"
                            :key="p.id"
                            class="flex items-center justify-between rounded-md border border-border/60 px-3 py-2"
                        >
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">{{ p.name }}</p>
                                <p class="truncate font-mono text-xs text-muted-foreground">{{ p.sku }}</p>
                            </div>
                            <Badge :variant="p.stock === 0 ? 'destructive' : 'warning'">
                                {{ p.stock }} / {{ p.min_stock }}
                            </Badge>
                        </div>
                    </CardContent>
                </Card>

                <Card v-else>
                    <CardHeader class="pb-3">
                        <CardTitle class="text-base">Inventario saludable</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-sm text-muted-foreground">
                            Todos los productos tienen stock por encima de su mínimo.
                        </p>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
