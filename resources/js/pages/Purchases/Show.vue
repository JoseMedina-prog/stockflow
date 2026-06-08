<script setup lang="ts">
import ConfirmDialog from '@/components/stockflow/ConfirmDialog.vue';
import PageHeader from '@/components/stockflow/PageHeader.vue';
import PaymentDialog from '@/components/stockflow/PaymentDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { usePermissions } from '@/composables/usePermissions';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { formatCurrency, formatDateTime } from '@/composables/useFormat';
import { ArrowLeft, Check, CreditCard, Mail, Pencil, Phone, Trash2, X } from 'lucide-vue-next';
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

interface PurchaseData {
    id: number;
    folio: string;
    status: string;
    status_label: string;
    status_badge: string;
    purchase_date: string;
    received_at: string | null;
    subtotal: number;
    tax: number;
    total: number;
    paid_amount: number;
    balance: number;
    is_fully_paid: boolean;
    notes: string | null;
    can_edit: boolean;
    can_receive: boolean;
    can_cancel: boolean;
    can_delete: boolean;
    supplier: {
        id: number;
        name: string;
        tax_id: string | null;
        email: string | null;
        phone: string | null;
    };
    user: { id: number; name: string };
    items: Array<{
        id: number;
        product_id: number;
        product_name: string;
        product_sku: string;
        category: string | null;
        quantity: number;
        unit_cost: number;
        subtotal: number;
        line_total: number;
    }>;
    payments: PaymentData[];
}

const props = defineProps<{ purchase: PurchaseData }>();

const { can } = usePermissions();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tablero', href: '/dashboard' },
    { title: 'Compras', href: '/purchases' },
    { title: props.purchase.folio, href: `/purchases/${props.purchase.id}` },
];

const deleteOpen = ref(false);
const receiveOpen = ref(false);
const cancelOpen = ref(false);
const paymentOpen = ref(false);
const processing = ref(false);

const handleDelete = () => {
    processing.value = true;
    router.delete(route('purchases.destroy', props.purchase.id), {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
            deleteOpen.value = false;
        },
    });
};

const handleReceive = () => {
    processing.value = true;
    router.post(route('purchases.receive', props.purchase.id), {}, {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
            receiveOpen.value = false;
        },
    });
};

const handleCancel = () => {
    processing.value = true;
    router.post(route('purchases.cancel', props.purchase.id), {}, {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
            cancelOpen.value = false;
        },
    });
};

const voidPayment = (paymentId: number) => {
    if (!confirm('¿Anular este pago? El saldo se restablecerá.')) return;
    router.delete(route('payments.destroy', paymentId), { preserveScroll: true });
};
</script>

<template>
    <Head :title="`Compra ${purchase.folio}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <PageHeader
                    :title="`Compra ${purchase.folio}`"
                    :description="`Registrada el ${formatDateTime(purchase.purchase_date)} por ${purchase.user.name}.`"
                >
                    <template #actions>
                        <Button variant="outline" as-child>
                            <Link :href="route('purchases.index')">
                                <ArrowLeft class="mr-1" />
                                Volver
                            </Link>
                        </Button>
                        <Button v-if="can('payments.create') && !purchase.is_fully_paid" @click="paymentOpen = true">
                            <CreditCard class="mr-1" />
                            Registrar pago
                        </Button>
                        <Button v-if="purchase.can_receive" variant="outline" as-child>
                            <Link :href="route('purchases.edit', purchase.id)">
                                <Pencil class="mr-1" />
                                Editar
                            </Link>
                        </Button>
                        <Button
                            v-if="purchase.can_receive"
                            @click="receiveOpen = true"
                        >
                            <Check class="mr-1" />
                            Marcar como recibida
                        </Button>
                        <Button
                            v-if="purchase.can_cancel"
                            variant="outline"
                            class="text-destructive"
                            @click="cancelOpen = true"
                        >
                            <X class="mr-1" />
                            Cancelar
                        </Button>
                        <Button
                            v-if="purchase.can_delete"
                            variant="ghost"
                            class="text-destructive hover:text-destructive"
                            @click="deleteOpen = true"
                        >
                            <Trash2 />
                        </Button>
                    </template>
                </PageHeader>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <Card class="lg:col-span-1">
                    <CardHeader>
                        <CardTitle>Estado</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3 text-sm">
                        <div>
                            <p class="text-xs text-muted-foreground">Estado</p>
                            <Badge :variant="purchase.status_badge as any" class="mt-1">{{ purchase.status_label }}</Badge>
                        </div>
                        <div v-if="purchase.received_at">
                            <p class="text-xs text-muted-foreground">Recibida el</p>
                            <p class="font-medium">{{ formatDateTime(purchase.received_at) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Fecha de compra</p>
                            <p class="font-medium">{{ purchase.purchase_date }}</p>
                        </div>
                        <Separator />
                        <div>
                            <p class="text-xs text-muted-foreground">Proveedor</p>
                            <Link :href="route('suppliers.edit', purchase.supplier.id)" class="font-medium hover:underline">
                                {{ purchase.supplier.name }}
                            </Link>
                            <p v-if="purchase.supplier.tax_id" class="font-mono text-xs text-muted-foreground">
                                {{ purchase.supplier.tax_id }}
                            </p>
                            <div v-if="purchase.supplier.email" class="mt-1 flex items-center gap-1.5 text-xs text-muted-foreground">
                                <Mail class="size-3" />
                                {{ purchase.supplier.email }}
                            </div>
                            <div v-if="purchase.supplier.phone" class="flex items-center gap-1.5 text-xs text-muted-foreground">
                                <Phone class="size-3" />
                                {{ purchase.supplier.phone }}
                            </div>
                        </div>
                        <Separator v-if="purchase.notes" />
                        <div v-if="purchase.notes">
                            <p class="text-xs text-muted-foreground">Notas</p>
                            <p class="mt-1 whitespace-pre-line text-sm">{{ purchase.notes }}</p>
                        </div>
                    </CardContent>
                </Card>

                <Card class="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>Productos</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="rounded-md border border-border/60">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Producto</TableHead>
                                        <TableHead class="w-20 text-center">Cant.</TableHead>
                                        <TableHead class="w-28 text-right">Costo</TableHead>
                                        <TableHead class="w-32 text-right">Subtotal</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="item in purchase.items" :key="item.id">
                                        <TableCell>
                                            <div class="font-medium">{{ item.product_name }}</div>
                                            <div class="text-xs text-muted-foreground">
                                                {{ item.product_sku }}<span v-if="item.category"> · {{ item.category }}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell class="text-center tabular-nums">{{ item.quantity }}</TableCell>
                                        <TableCell class="text-right tabular-nums">{{ formatCurrency(item.unit_cost) }}</TableCell>
                                        <TableCell class="text-right font-semibold tabular-nums">{{ formatCurrency(item.subtotal) }}</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>

                        <div class="mt-4 flex flex-col items-end gap-1 text-sm">
                            <div class="flex w-64 justify-between">
                                <span class="text-muted-foreground">Subtotal</span>
                                <span class="tabular-nums">{{ formatCurrency(purchase.subtotal) }}</span>
                            </div>
                            <div v-if="purchase.tax > 0" class="flex w-64 justify-between">
                                <span class="text-muted-foreground">Impuestos</span>
                                <span class="tabular-nums">{{ formatCurrency(purchase.tax) }}</span>
                            </div>
                            <Separator class="my-2 w-64" />
                            <div class="flex w-64 justify-between text-base font-semibold">
                                <span>Total</span>
                                <span class="tabular-nums">{{ formatCurrency(purchase.total) }}</span>
                            </div>
                            <div class="flex w-64 justify-between">
                                <span class="text-muted-foreground">Pagado</span>
                                <span class="font-semibold tabular-nums text-emerald-600 dark:text-emerald-400">
                                    {{ formatCurrency(purchase.paid_amount) }}
                                </span>
                            </div>
                            <div class="flex w-64 justify-between border-t pt-1">
                                <span class="font-semibold">Saldo</span>
                                <span
                                    class="font-semibold tabular-nums"
                                    :class="purchase.balance > 0 ? 'text-destructive' : ''"
                                >
                                    {{ formatCurrency(purchase.balance) }}
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Card class="mt-5">
                <CardHeader>
                    <CardTitle class="text-base">Pagos realizados</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="purchase.payments.length === 0" class="py-6 text-center text-sm text-muted-foreground">
                        Aún no se han registrado pagos para esta compra.
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
                                <TableRow v-for="p in purchase.payments" :key="p.id">
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

        <ConfirmDialog
            v-model:open="deleteOpen"
            title="Eliminar compra"
            :description="`¿Estás seguro de eliminar la compra «${purchase.folio}»? Esta acción no se puede deshacer.`"
            :processing="processing"
            @confirm="handleDelete"
        />

        <ConfirmDialog
            v-model:open="receiveOpen"
            title="Marcar como recibida"
            :description="`¿Confirmas que recibiste la mercancía de «${purchase.supplier.name}»? El stock de ${purchase.items.length} producto(s) se incrementará.`"
            confirm-label="Sí, recibida"
            :processing="processing"
            @confirm="handleReceive"
        />

        <ConfirmDialog
            v-model:open="cancelOpen"
            title="Cancelar compra"
            :description="`¿Cancelar la compra «${purchase.folio}»?${purchase.status === 'received' ? ' Se revertirá el stock previamente incrementado.' : ''}`"
            confirm-label="Sí, cancelar"
            :processing="processing"
            @confirm="handleCancel"
        />

        <PaymentDialog
            v-model:open="paymentOpen"
            :balance="purchase.balance"
            :methods="[
                { value: 'cash', label: 'Efectivo' },
                { value: 'card', label: 'Tarjeta' },
                { value: 'transfer', label: 'Transferencia' },
                { value: 'check', label: 'Cheque' },
            ]"
            :post-url="route('purchases.payments.store', purchase.id)"
            :description="`Pago a «${purchase.supplier.name}».`"
        />
    </AppLayout>
</template>
