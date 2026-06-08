<script setup lang="ts">
import ActivityDialog from '@/components/stockflow/ActivityDialog.vue';
import EmptyState from '@/components/stockflow/EmptyState.vue';
import PageHeader from '@/components/stockflow/PageHeader.vue';
import StatCard from '@/components/stockflow/StatCard.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { usePermissions } from '@/composables/usePermissions';
import { formatCurrency, formatDate, formatDateTime } from '@/composables/useFormat';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CalendarClock,
    CircleDollarSign,
    Clock,
    CreditCard,
    Edit,
    FileText,
    Mail,
    MapPin,
    Phone,
    Plus,
    Receipt,
    RotateCcw,
    ShoppingCart,
    Target,
    Ticket,
    TrendingUp,
    Undo2,
    UserPlus,
    Wallet,
} from 'lucide-vue-next';
import { ref } from 'vue';

interface CustomerData {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    address: string | null;
    created_at: string;
}

interface Kpis {
    lifetime_value: number;
    sales_count: number;
    avg_ticket: number;
    open_balance: number;
    pending_sales: number;
    returns_count: number;
    returns_total: number;
    credit_notes_active: number;
    credit_notes_balance: number;
    open_opportunities: number;
    open_opportunities_value: number;
    open_tasks: number;
    last_purchase_at: string | null;
}

interface SaleItem {
    id: number;
    folio: string;
    sale_date: string;
    total: number;
    paid_amount: number;
    balance: number;
    is_fully_paid: boolean;
    items_count: number;
    user: { id: number; name: string };
}

interface ReturnItem {
    id: number;
    folio: string;
    return_date: string;
    total: number;
    status: string;
    status_label: string;
    status_badge: string;
}

interface CreditNoteItem {
    id: number;
    folio: string;
    issue_date: string;
    original_amount: number;
    balance_remaining: number;
    status: string;
    status_label: string;
    status_badge: string;
}

interface OpportunityItem {
    id: number;
    name: string;
    stage: string;
    stage_label: string;
    stage_badge: string;
    amount: number;
    probability: number;
    expected_close_date: string | null;
    is_overdue: boolean;
}

interface LeadItem {
    id: number;
    name: string;
    source_label: string;
    converted_at: string | null;
}

interface TaskItem {
    id: number;
    title: string;
    due_date: string | null;
    is_overdue: boolean;
    is_due_today: boolean;
    priority: string;
    priority_label: string;
    priority_badge: string;
    status: string;
    status_label: string;
    status_badge: string;
    href: string;
}

interface ActivityItem {
    id: number;
    type: string;
    type_label: string;
    type_badge: string;
    description: string | null;
    outcome: string | null;
    occurred_at: string;
    duration_minutes: number | null;
}

const props = defineProps<{
    customer: CustomerData;
    kpis: Kpis;
    recentSales: SaleItem[];
    recentReturns: ReturnItem[];
    creditNotes: CreditNoteItem[];
    opportunities: OpportunityItem[];
    convertedLeads: LeadItem[];
    openTasks: TaskItem[];
    activities: ActivityItem[];
}>();

const { can } = usePermissions();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tablero', href: '/dashboard' },
    { title: 'Clientes', href: '/customers' },
    { title: props.customer.name, href: `/customers/${props.customer.id}` },
];

const activityOpen = ref(false);

const formatDuration = (mins: number | null): string => {
    if (!mins) return '';
    if (mins < 60) return `${mins} min`;
    const h = Math.floor(mins / 60);
    const m = mins % 60;
    return m ? `${h}h ${m}m` : `${h}h`;
};

const daysSinceLastPurchase = (): string => {
    if (!props.kpis.last_purchase_at) return 'Nunca';
    const last = new Date(props.kpis.last_purchase_at);
    const now = new Date();
    const diff = Math.floor((now.getTime() - last.getTime()) / 86400000);
    if (diff <= 0) return 'Hoy';
    if (diff === 1) return 'Ayer';
    if (diff < 30) return `Hace ${diff} días`;
    if (diff < 365) return `Hace ${Math.floor(diff / 30)} meses`;
    return `Hace ${Math.floor(diff / 365)} años`;
};
</script>

<template>
    <Head :title="`Cliente · ${customer.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <PageHeader :title="customer.name" :description="`Cliente desde el ${formatDate(customer.created_at)}.`">
                <template #actions>
                    <Button variant="outline" as-child>
                        <Link :href="route('customers.index')">
                            <ArrowLeft class="mr-1" />
                            Volver
                        </Link>
                    </Button>
                    <Button v-if="can('activities.create')" variant="outline" @click="activityOpen = true">
                        <Plus class="mr-1" />
                        Actividad
                    </Button>
                    <Button v-if="can('sales.create')" as-child>
                        <Link :href="route('sales.create')">
                            <ShoppingCart class="mr-1" />
                            Nueva venta
                        </Link>
                    </Button>
                    <Button v-if="can('customers.update')" variant="outline" as-child>
                        <Link :href="route('customers.edit', customer.id)">
                            <Edit class="mr-1" />
                            Editar
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard
                    title="Valor de vida (LTV)"
                    :value="formatCurrency(kpis.lifetime_value)"
                    :description="`${kpis.sales_count} venta(s) registrada(s)`"
                    :icon="TrendingUp"
                    accent="success"
                />
                <StatCard
                    title="Ticket promedio"
                    :value="formatCurrency(kpis.avg_ticket)"
                    :description="daysSinceLastPurchase()"
                    :icon="Ticket"
                    accent="primary"
                />
                <StatCard
                    title="Saldo pendiente"
                    :value="formatCurrency(kpis.open_balance)"
                    :description="kpis.pending_sales > 0 ? `${kpis.pending_sales} venta(s) con saldo` : 'Sin pendientes'"
                    :icon="Wallet"
                    :accent="kpis.open_balance > 0 ? 'destructive' : 'success'"
                />
                <StatCard
                    title="Oportunidades abiertas"
                    :value="String(kpis.open_opportunities)"
                    :description="`${formatCurrency(kpis.open_opportunities_value)} en pipeline`"
                    :icon="Target"
                    accent="info"
                />
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <Card class="lg:col-span-1">
                    <CardHeader>
                        <CardTitle class="text-base">Información de contacto</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3 text-sm">
                        <div v-if="customer.email" class="flex items-center gap-2">
                            <Mail class="size-4 text-muted-foreground" />
                            <a :href="`mailto:${customer.email}`" class="hover:underline">{{ customer.email }}</a>
                        </div>
                        <div v-if="customer.phone" class="flex items-center gap-2">
                            <Phone class="size-4 text-muted-foreground" />
                            <a :href="`tel:${customer.phone}`" class="hover:underline">{{ customer.phone }}</a>
                        </div>
                        <div v-if="customer.address" class="flex items-start gap-2">
                            <MapPin class="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                            <span>{{ customer.address }}</span>
                        </div>
                        <p v-if="!customer.email && !customer.phone && !customer.address" class="text-muted-foreground">
                            Sin datos de contacto.
                        </p>
                        <Separator />
                        <div class="grid grid-cols-2 gap-3 text-xs">
                            <div>
                                <p class="text-muted-foreground">Devoluciones</p>
                                <p class="text-base font-semibold tabular-nums">{{ kpis.returns_count }}</p>
                                <p class="text-muted-foreground">{{ formatCurrency(kpis.returns_total) }}</p>
                            </div>
                            <div>
                                <p class="text-muted-foreground">Notas de crédito</p>
                                <p class="text-base font-semibold tabular-nums">{{ kpis.credit_notes_active }}</p>
                                <p class="text-muted-foreground">{{ formatCurrency(kpis.credit_notes_balance) }}</p>
                            </div>
                            <div>
                                <p class="text-muted-foreground">Tareas abiertas</p>
                                <p class="text-base font-semibold tabular-nums">{{ kpis.open_tasks }}</p>
                            </div>
                            <div>
                                <p class="text-muted-foreground">Actividades</p>
                                <p class="text-base font-semibold tabular-nums">{{ activities.length }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="lg:col-span-2">
                    <CardHeader class="flex flex-row items-center justify-between space-y-0">
                        <div>
                            <CardTitle class="text-base">Historial de ventas</CardTitle>
                            <CardDescription>Últimas {{ recentSales.length }} ventas del cliente.</CardDescription>
                        </div>
                        <Button v-if="can('sales.view_any')" variant="ghost" size="sm" as-child>
                            <Link :href="route('sales.index', { search: customer.name })">Ver todas</Link>
                        </Button>
                    </CardHeader>
                    <CardContent>
                        <div v-if="recentSales.length === 0" class="rounded-md border border-dashed border-border/60 p-6 text-center text-sm text-muted-foreground">
                            <Receipt class="mx-auto mb-2 size-8 opacity-50" />
                            Aún no hay ventas registradas.
                        </div>
                        <div v-else class="rounded-md border border-border/60">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Folio</TableHead>
                                        <TableHead>Fecha</TableHead>
                                        <TableHead class="text-right">Total</TableHead>
                                        <TableHead class="text-right">Saldo</TableHead>
                                        <TableHead>Estado</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="sale in recentSales" :key="sale.id">
                                        <TableCell>
                                            <Link :href="route('sales.show', sale.id)" class="font-mono text-xs hover:underline">
                                                {{ sale.folio }}
                                            </Link>
                                        </TableCell>
                                        <TableCell class="text-xs text-muted-foreground">{{ formatDate(sale.sale_date) }}</TableCell>
                                        <TableCell class="text-right tabular-nums">{{ formatCurrency(sale.total) }}</TableCell>
                                        <TableCell class="text-right tabular-nums">
                                            <span :class="sale.balance > 0 ? 'text-destructive' : 'text-muted-foreground'">
                                                {{ formatCurrency(sale.balance) }}
                                            </span>
                                        </TableCell>
                                        <TableCell>
                                            <Badge :variant="sale.is_fully_paid ? 'success' : 'warning'">
                                                {{ sale.is_fully_paid ? 'Pagada' : 'Pendiente' }}
                                            </Badge>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Oportunidades activas</CardTitle>
                        <CardDescription>Pipeline en curso del cliente.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <EmptyState
                            v-if="opportunities.length === 0"
                            :icon="Target"
                            title="Sin oportunidades"
                            description="No hay oportunidades registradas para este cliente."
                        />
                        <div v-else class="space-y-2">
                            <Link
                                v-for="o in opportunities"
                                :key="o.id"
                                :href="route('opportunities.show', o.id)"
                                class="flex items-center justify-between gap-3 rounded-md border border-border/60 p-3 transition-colors hover:border-primary/40"
                            >
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium">{{ o.name }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        <Badge :variant="o.stage_badge as any" class="mr-1">{{ o.stage_label }}</Badge>
                                        <span v-if="o.expected_close_date">
                                            Cierre: {{ formatDate(o.expected_close_date) }}
                                            <span v-if="o.is_overdue" class="text-destructive">(vencida)</span>
                                        </span>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold tabular-nums">{{ formatCurrency(o.amount) }}</p>
                                    <p class="text-xs text-muted-foreground">{{ o.probability }}%</p>
                                </div>
                            </Link>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Devoluciones y notas de crédito</CardTitle>
                        <CardDescription>Post-venta y saldos a favor.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <EmptyState
                            v-if="recentReturns.length === 0 && creditNotes.length === 0"
                            :icon="RotateCcw"
                            title="Sin movimientos"
                            description="No hay devoluciones ni notas de crédito."
                        />
                        <div v-else class="space-y-3">
                            <div v-if="recentReturns.length > 0">
                                <p class="mb-1.5 text-xs font-semibold uppercase tracking-wider text-muted-foreground">Devoluciones</p>
                                <div class="space-y-1.5">
                                    <Link
                                        v-for="r in recentReturns.slice(0, 5)"
                                        :key="r.id"
                                        :href="`/returns/${r.id}`"
                                        class="flex items-center justify-between rounded-md border border-border/60 px-3 py-2 text-sm hover:border-primary/40"
                                    >
                                        <span class="font-mono text-xs">{{ r.folio }}</span>
                                        <span class="text-xs text-muted-foreground">{{ formatDate(r.return_date) }}</span>
                                        <Badge :variant="r.status_badge as any">{{ r.status_label }}</Badge>
                                        <span class="font-semibold tabular-nums">{{ formatCurrency(r.total) }}</span>
                                    </Link>
                                </div>
                            </div>
                            <div v-if="creditNotes.length > 0">
                                <p class="mb-1.5 text-xs font-semibold uppercase tracking-wider text-muted-foreground">Notas de crédito</p>
                                <div class="space-y-1.5">
                                    <div
                                        v-for="c in creditNotes.slice(0, 5)"
                                        :key="c.id"
                                        class="flex items-center justify-between rounded-md border border-border/60 px-3 py-2 text-sm"
                                    >
                                        <span class="font-mono text-xs">{{ c.folio }}</span>
                                        <span class="text-xs text-muted-foreground">{{ formatDate(c.issue_date) }}</span>
                                        <Badge :variant="c.status_badge as any">{{ c.status_label }}</Badge>
                                        <span class="font-semibold tabular-nums">{{ formatCurrency(c.balance_remaining) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Tareas abiertas</CardTitle>
                        <CardDescription>Seguimiento pendiente.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <EmptyState
                            v-if="openTasks.length === 0"
                            :icon="CalendarClock"
                            title="Sin tareas"
                            description="No hay tareas pendientes para este cliente."
                        />
                        <div v-else class="space-y-2">
                            <Link
                                v-for="t in openTasks"
                                :key="t.id"
                                :href="t.href"
                                class="flex items-start justify-between gap-3 rounded-md border border-border/60 p-3 transition-colors hover:border-primary/40"
                            >
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium">{{ t.title }}</p>
                                    <div class="mt-1 flex flex-wrap items-center gap-1.5 text-xs text-muted-foreground">
                                        <Badge :variant="t.priority_badge as any">{{ t.priority_label }}</Badge>
                                        <Badge :variant="t.status_badge as any">{{ t.status_label }}</Badge>
                                        <span v-if="t.due_date">
                                            <Clock class="mr-0.5 inline size-3" />
                                            {{ formatDate(t.due_date) }}
                                            <span v-if="t.is_overdue" class="font-semibold text-destructive">(vencida)</span>
                                            <span v-else-if="t.is_due_today" class="font-semibold text-amber-600">(hoy)</span>
                                        </span>
                                    </div>
                                </div>
                            </Link>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Línea de tiempo</CardTitle>
                        <CardDescription>Últimas {{ activities.length }} actividades registradas.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div v-if="activities.length === 0" class="rounded-md border border-dashed border-border/60 p-6 text-center text-sm text-muted-foreground">
                            <FileText class="mx-auto mb-2 size-8 opacity-50" />
                            Sin actividades registradas.
                        </div>
                        <ol v-else class="relative space-y-3">
                            <li v-for="a in activities" :key="a.id" class="flex gap-3">
                                <Badge :variant="a.type_badge as any" class="mt-0.5 shrink-0">{{ a.type_label }}</Badge>
                                <div class="min-w-0 flex-1 border-b border-border/40 pb-2">
                                    <p class="text-sm">{{ a.description ?? '—' }}</p>
                                    <p v-if="a.outcome" class="mt-0.5 text-xs text-emerald-600 dark:text-emerald-400">→ {{ a.outcome }}</p>
                                    <p class="mt-0.5 text-xs text-muted-foreground">
                                        {{ formatDateTime(a.occurred_at) }}
                                        <span v-if="a.duration_minutes" class="ml-2">{{ formatDuration(a.duration_minutes) }}</span>
                                    </p>
                                </div>
                            </li>
                        </ol>
                    </CardContent>
                </Card>
            </div>

            <Card v-if="convertedLeads.length > 0">
                <CardHeader>
                    <CardTitle class="text-base">Leads convertidos</CardTitle>
                    <CardDescription>Prospectos que se convirtieron en este cliente.</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        <Link
                            v-for="l in convertedLeads"
                            :key="l.id"
                            :href="route('leads.show', l.id)"
                            class="flex items-center justify-between rounded-md border border-border/60 p-3 transition-colors hover:border-primary/40"
                        >
                            <div>
                                <p class="text-sm font-medium">{{ l.name }}</p>
                                <p class="text-xs text-muted-foreground">{{ l.source_label }}</p>
                            </div>
                            <div class="text-right text-xs text-muted-foreground">
                                <UserPlus class="ml-auto size-4" />
                                <p>{{ l.converted_at ? formatDate(l.converted_at) : '—' }}</p>
                            </div>
                        </Link>
                    </div>
                </CardContent>
            </Card>
        </div>

        <ActivityDialog
            v-model:open="activityOpen"
            :types="[
                { value: 'call', label: 'Llamada', badge: 'info', has_duration: true },
                { value: 'email', label: 'Correo', badge: 'secondary', has_duration: false },
                { value: 'meeting', label: 'Reunión', badge: 'warning', has_duration: true },
                { value: 'note', label: 'Nota', badge: 'secondary', has_duration: false },
                { value: 'message', label: 'Mensaje', badge: 'info', has_duration: false },
                { value: 'whatsapp', label: 'WhatsApp', badge: 'success', has_duration: false },
            ]"
            :post-url="route('customers.activities.store', customer.id)"
            :description="`Actividad sobre el cliente «${customer.name}».`"
        />
    </AppLayout>
</template>
