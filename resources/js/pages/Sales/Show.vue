<script setup lang="ts">
import PaymentDialog from '@/components/stockflow/PaymentDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatCurrency, formatDateTime } from '@/composables/useFormat';
import { usePermissions } from '@/composables/usePermissions';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, CreditCard, Receipt, Undo2 } from 'lucide-vue-next';
import { ref } from 'vue';

interface PaymentData {
    id: number;
    folio: string;
    method: string;
    method_label: string;
    amount: number;
    reference: string | null;
    paid_at: string;
    notes: string | null;
    user: { id: number; name: string };
}

interface SaleData {
    id: number;
    sale_date: string;
    subtotal: number;
    tax: number;
    total: number;
    paid_amount: number;
    balance: number;
    is_fully_paid: boolean;
    customer: { id: number; name: string; email: string | null } | null;
    user: { id: number; name: string };
    items: Array<{
        id: number;
        product_id: number;
        product_name: string;
        product_sku: string;
        category: string | null;
        quantity: number;
        price: number;
        subtotal: number;
        tax_id: number | null;
        tax_name: string | null;
        tax_rate: number;
        tax_amount: number;
    }>;
    payments: PaymentData[];
}

const props = defineProps<{ sale: SaleData }>();

const { can } = usePermissions();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tablero', href: '/dashboard' },
    { title: 'Ventas', href: '/sales' },
    { title: `#${props.sale.id}`, href: `/sales/${props.sale.id}` },
];

const paymentOpen = ref(false);

const voidPayment = (paymentId: number) => {
    if (!confirm('¿Anular este pago? El saldo se restablecerá.')) return;
    router.delete(route('payments.destroy', paymentId), { preserveScroll: true });
};
</script>

<template>
    <Head :title="`Venta #${sale.id}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-4xl p-4">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl font-semibold tracking-tight">Venta #{{ sale.id }}</h1>
                        <Badge :variant="sale.is_fully_paid ? 'success' : 'warning'">
                            <Receipt class="mr-1 size-3" />
                            {{ sale.is_fully_paid ? 'Pagada' : 'Pendiente de pago' }}
                        </Badge>
                    </div>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ formatDateTime(sale.sale_date) }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Button v-if="can('returns.create')" variant="outline" as-child>
                        <Link :href="route('sales.returns.create', sale.id)">
                            <Undo2 class="mr-1" />
                            Devolución
                        </Link>
                    </Button>
                    <Button v-if="can('payments.create') && !sale.is_fully_paid" @click="paymentOpen = true">
                        <CreditCard class="mr-1" />
                        Registrar pago
                    </Button>
                    <Button variant="outline" as-child>
                        <Link :href="route('sales.index')">
                            <ArrowLeft class="mr-1" />
                            Volver
                        </Link>
                    </Button>
                </div>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Cliente</CardTitle>
                    </CardHeader>
                    <CardContent class="text-sm">
                        <p v-if="sale.customer" class="font-medium">{{ sale.customer.name }}</p>
                        <p v-else class="text-muted-foreground">Consumidor final</p>
                        <p v-if="sale.customer?.email" class="text-muted-foreground">
                            {{ sale.customer.email }}
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Vendedor</CardTitle>
                    </CardHeader>
                    <CardContent class="text-sm">
                        <p class="font-medium">{{ sale.user.name }}</p>
                    </CardContent>
                </Card>
            </div>

            <Card class="mt-5">
                <CardHeader>
                    <CardTitle class="text-base">Detalle de la venta</CardTitle>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Producto</TableHead>
                                <TableHead>Categoría</TableHead>
                                <TableHead class="text-center">Cantidad</TableHead>
                                <TableHead class="text-right">Precio</TableHead>
                                <TableHead class="text-right">Subtotal</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="item in sale.items" :key="item.id">
                                <TableCell>
                                    <div class="font-medium">{{ item.product_name }}</div>
                                    <div class="font-mono text-xs text-muted-foreground">{{ item.product_sku }}</div>
                                </TableCell>
                                <TableCell>
                                    <Badge v-if="item.category" variant="outline">{{ item.category }}</Badge>
                                    <span v-else class="text-muted-foreground">—</span>
                                </TableCell>
                                <TableCell class="text-center tabular-nums">{{ item.quantity }}</TableCell>
                                <TableCell class="text-right tabular-nums">{{ formatCurrency(item.price) }}</TableCell>
                                <TableCell class="text-right font-semibold tabular-nums">
                                    {{ formatCurrency(item.subtotal) }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <div class="mt-4 space-y-1 border-t pt-4 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Subtotal</span>
                            <span class="tabular-nums">{{ formatCurrency(sale.subtotal) }}</span>
                        </div>
                        <div v-if="sale.tax > 0" class="flex justify-between">
                            <span class="text-muted-foreground">Impuestos</span>
                            <span class="tabular-nums">{{ formatCurrency(sale.tax) }}</span>
                        </div>
                        <div class="flex justify-between font-semibold">
                            <span>Total</span>
                            <span class="tabular-nums">{{ formatCurrency(sale.total) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Pagado</span>
                            <span class="font-semibold tabular-nums text-emerald-600 dark:text-emerald-400">
                                {{ formatCurrency(sale.paid_amount) }}
                            </span>
                        </div>
                        <Separator class="my-2" />
                        <div class="flex justify-between text-base">
                            <span class="font-semibold">Saldo</span>
                            <span class="font-semibold tabular-nums" :class="sale.balance > 0 ? 'text-destructive' : ''">
                                {{ formatCurrency(sale.balance) }}
                            </span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card class="mt-5">
                <CardHeader>
                    <CardTitle class="text-base">Pagos registrados</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="sale.payments.length === 0" class="py-6 text-center text-sm text-muted-foreground">
                        Aún no se han registrado pagos para esta venta.
                    </div>
                    <div v-else class="rounded-md border border-border/60">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Folio</TableHead>
                                    <TableHead>Método</TableHead>
                                    <TableHead>Referencia</TableHead>
                                    <TableHead>Fecha</TableHead>
                                    <TableHead>Registrado por</TableHead>
                                    <TableHead class="text-right">Monto</TableHead>
                                    <TableHead class="w-16" v-if="can('payments.create')"></TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="p in sale.payments" :key="p.id">
                                    <TableCell class="font-mono text-xs">{{ p.folio }}</TableCell>
                                    <TableCell>{{ p.method_label }}</TableCell>
                                    <TableCell class="font-mono text-xs text-muted-foreground">{{ p.reference ?? '—' }}</TableCell>
                                    <TableCell class="text-xs text-muted-foreground">{{ formatDateTime(p.paid_at) }}</TableCell>
                                    <TableCell class="text-muted-foreground">{{ p.user.name }}</TableCell>
                                    <TableCell class="text-right font-semibold tabular-nums">{{ formatCurrency(p.amount) }}</TableCell>
                                    <TableCell class="text-right">
                                        <button
                                            v-if="can('payments.create')"
                                            class="text-xs text-destructive hover:underline"
                                            @click="voidPayment(p.id)"
                                        >
                                            Anular
                                        </button>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>
        </div>

        <PaymentDialog
            v-model:open="paymentOpen"
            :balance="sale.balance"
            :methods="[
                { value: 'cash', label: 'Efectivo' },
                { value: 'card', label: 'Tarjeta' },
                { value: 'transfer', label: 'Transferencia' },
                { value: 'check', label: 'Cheque' },
            ]"
            :post-url="route('sales.payments.store', sale.id)"
            :description="sale.customer ? `Pago a cuenta de «${sale.customer.name}».` : 'Venta a consumidor final.'"
        />
    </AppLayout>
</template>
