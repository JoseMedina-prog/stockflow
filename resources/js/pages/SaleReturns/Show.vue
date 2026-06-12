<script setup lang="ts">
import PageHeader from '@/components/stockflow/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatCurrency, formatDateTime } from '@/composables/useFormat';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Check, X } from 'lucide-vue-next';
import { ref } from 'vue';

interface SaleReturnData {
    id: number;
    folio: string;
    status: string;
    status_label: string;
    status_badge: string;
    subtotal: number;
    tax: number;
    total: number;
    reason: string;
    notes: string | null;
    rejection_reason: string | null;
    approved_at: string | null;
    rejected_at: string | null;
    refund_method: string | null;
    refund_method_label: string | null;
    can_approve: boolean;
    can_reject: boolean;
    sale: { id: number; folio: string; sale_date: string; total: number };
    customer: { id: number; name: string } | null;
    user: { id: number; name: string };
    approver: { id: number; name: string } | null;
    items: Array<{
        id: number;
        product_id: number;
        product_name: string;
        product_sku: string;
        category: string | null;
        quantity_returned: number;
        unit_price: number;
        subtotal: number;
    }>;
    credit_note: {
        id: number;
        folio: string;
        amount: number;
        balance_remaining: number;
        status: string;
        status_label: string;
    } | null;
}

interface RefundMethod {
    value: string;
    label: string;
}

const props = defineProps<{
    saleReturn: SaleReturnData;
    refund_methods: RefundMethod[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tablero', href: '/dashboard' },
    { title: 'Devoluciones', href: '/returns' },
    { title: props.saleReturn.folio, href: `/returns/${props.saleReturn.id}` },
];

const approveOpen = ref(false);
const rejectOpen = ref(false);
const processing = ref(false);

const approveForm = useForm({
    refund_method: 'original_payment',
    notes: '',
});

const rejectForm = useForm({
    rejection_reason: '',
});

const handleApprove = () => {
    processing.value = true;
    approveForm.post(route('returns.approve', props.saleReturn.id), {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
            approveOpen.value = false;
        },
    });
};

const handleReject = () => {
    processing.value = true;
    rejectForm.post(route('returns.reject', props.saleReturn.id), {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
            rejectOpen.value = false;
        },
    });
};
</script>

<template>
    <Head :title="`Devolución ${saleReturn.folio}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <PageHeader
                    :title="`Devolución ${saleReturn.folio}`"
                    :description="`Registrada el ${formatDateTime(saleReturn.user ? new Date() : new Date())} por ${saleReturn.user.name}.`"
                >
                    <template #actions>
                        <Button variant="outline" as-child>
                            <Link :href="route('returns.index')">
                                <ArrowLeft class="mr-1" />
                                Volver
                            </Link>
                        </Button>
                        <Button v-if="saleReturn.can_approve" @click="approveOpen = true">
                            <Check class="mr-1" />
                            Aprobar
                        </Button>
                        <Button v-if="saleReturn.can_reject" variant="outline" class="text-destructive" @click="rejectOpen = true">
                            <X class="mr-1" />
                            Rechazar
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
                            <Badge :variant="saleReturn.status_badge as any" class="mt-1">{{ saleReturn.status_label }}</Badge>
                        </div>
                        <div v-if="saleReturn.approved_at">
                            <p class="text-xs text-muted-foreground">Aprobada</p>
                            <p class="font-medium">{{ formatDateTime(saleReturn.approved_at) }}</p>
                            <p v-if="saleReturn.approver" class="text-xs text-muted-foreground">por {{ saleReturn.approver.name }}</p>
                        </div>
                        <div v-if="saleReturn.rejected_at">
                            <p class="text-xs text-muted-foreground">Rechazada</p>
                            <p class="font-medium">{{ formatDateTime(saleReturn.rejected_at) }}</p>
                            <p v-if="saleReturn.rejection_reason" class="mt-1 text-xs text-muted-foreground">
                                {{ saleReturn.rejection_reason }}
                            </p>
                        </div>
                        <div v-if="saleReturn.refund_method">
                            <p class="text-xs text-muted-foreground">Método de reembolso</p>
                            <p class="font-medium">{{ saleReturn.refund_method_label }}</p>
                        </div>
                        <Separator />
                        <div>
                            <p class="text-xs text-muted-foreground">Venta original</p>
                            <Link :href="route('sales.show', saleReturn.sale.id)" class="font-mono text-sm hover:underline">
                                {{ saleReturn.sale.folio }}
                            </Link>
                            <p class="text-xs text-muted-foreground">{{ saleReturn.sale.sale_date }}</p>
                        </div>
                        <div v-if="saleReturn.customer">
                            <p class="text-xs text-muted-foreground">Cliente</p>
                            <Link :href="route('customers.index')" class="font-medium hover:underline">
                                {{ saleReturn.customer.name }}
                            </Link>
                        </div>
                        <Separator />
                        <div>
                            <p class="text-xs text-muted-foreground">Motivo</p>
                            <p class="mt-1 text-sm">{{ saleReturn.reason }}</p>
                        </div>
                        <div v-if="saleReturn.notes">
                            <p class="text-xs text-muted-foreground">Notas</p>
                            <p class="mt-1 whitespace-pre-line text-sm">{{ saleReturn.notes }}</p>
                        </div>
                        <div v-if="saleReturn.credit_note">
                            <Separator />
                            <div>
                                <p class="text-xs text-muted-foreground">Nota de crédito</p>
                                <Link :href="route('customers.index')" class="font-mono text-sm hover:underline">
                                    {{ saleReturn.credit_note.folio }}
                                </Link>
                                <p class="text-xs text-muted-foreground">
                                    Saldo:
                                    <span class="font-semibold tabular-nums text-foreground">{{
                                        formatCurrency(saleReturn.credit_note.balance_remaining)
                                    }}</span>
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="lg:col-span-2">
                    <CardHeader>
                        <CardTitle>Productos devueltos</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="rounded-md border border-border/60">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Producto</TableHead>
                                        <TableHead class="w-20 text-center">Cant.</TableHead>
                                        <TableHead class="w-28 text-right">Precio</TableHead>
                                        <TableHead class="w-32 text-right">Subtotal</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="item in saleReturn.items" :key="item.id">
                                        <TableCell>
                                            <div class="font-medium">{{ item.product_name }}</div>
                                            <div class="text-xs text-muted-foreground">
                                                {{ item.product_sku }}<span v-if="item.category"> · {{ item.category }}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell class="text-center tabular-nums">{{ item.quantity_returned }}</TableCell>
                                        <TableCell class="text-right tabular-nums">{{ formatCurrency(item.unit_price) }}</TableCell>
                                        <TableCell class="text-right font-semibold tabular-nums">{{ formatCurrency(item.subtotal) }}</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>

                        <div class="mt-4 flex flex-col items-end gap-1 text-sm">
                            <div class="flex w-64 justify-between">
                                <span class="text-muted-foreground">Subtotal</span>
                                <span class="tabular-nums">{{ formatCurrency(saleReturn.subtotal) }}</span>
                            </div>
                            <div v-if="saleReturn.tax > 0" class="flex w-64 justify-between">
                                <span class="text-muted-foreground">Impuestos</span>
                                <span class="tabular-nums">{{ formatCurrency(saleReturn.tax) }}</span>
                            </div>
                            <Separator class="my-2 w-64" />
                            <div class="flex w-64 justify-between text-base font-semibold">
                                <span>Total a devolver</span>
                                <span class="tabular-nums">{{ formatCurrency(saleReturn.total) }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <div v-if="approveOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="approveOpen = false">
            <div class="w-full max-w-md rounded-lg bg-card p-6 shadow-xl">
                <h2 class="text-lg font-semibold">Aprobar devolución</h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    Al aprobar, el stock se re-ingresa automáticamente. Si el método es "Nota de crédito", se emitirá una nota a nombre del cliente.
                </p>
                <form @submit.prevent="handleApprove" class="mt-4 space-y-4">
                    <div class="space-y-2">
                        <Label for="refund_method">Método de reembolso</Label>
                        <select
                            id="refund_method"
                            v-model="approveForm.refund_method"
                            required
                            class="h-9 w-full rounded-md border border-input bg-background px-2 text-sm"
                        >
                            <option v-for="m in refund_methods" :key="m.value" :value="m.value">
                                {{ m.label }}
                            </option>
                        </select>
                        <p v-if="approveForm.errors.refund_method" class="text-sm text-destructive">
                            {{ approveForm.errors.refund_method }}
                        </p>
                    </div>
                    <div class="space-y-2">
                        <Label for="approve_notes">Notas</Label>
                        <textarea
                            id="approve_notes"
                            v-model="approveForm.notes"
                            rows="2"
                            class="flex min-h-[60px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm"
                        />
                    </div>
                    <div class="flex items-center justify-end gap-2">
                        <Button type="button" variant="outline" @click="approveOpen = false">Cancelar</Button>
                        <Button type="submit" :disabled="processing">
                            <Check class="mr-1" />
                            Confirmar aprobación
                        </Button>
                    </div>
                </form>
            </div>
        </div>

        <div v-if="rejectOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="rejectOpen = false">
            <div class="w-full max-w-md rounded-lg bg-card p-6 shadow-xl">
                <h2 class="text-lg font-semibold">Rechazar devolución</h2>
                <p class="mt-1 text-sm text-muted-foreground">Indica el motivo del rechazo. La devolución no se puede revertir después.</p>
                <form @submit.prevent="handleReject" class="mt-4 space-y-4">
                    <div class="space-y-2">
                        <Label for="rejection_reason">Motivo</Label>
                        <textarea
                            id="rejection_reason"
                            v-model="rejectForm.rejection_reason"
                            rows="3"
                            required
                            class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm"
                            placeholder="Ej: Fuera de política de devoluciones"
                        />
                        <p v-if="rejectForm.errors.rejection_reason" class="text-sm text-destructive">
                            {{ rejectForm.errors.rejection_reason }}
                        </p>
                    </div>
                    <div class="flex items-center justify-end gap-2">
                        <Button type="button" variant="outline" @click="rejectOpen = false">Cancelar</Button>
                        <Button type="submit" variant="destructive" :disabled="processing">
                            <X class="mr-1" />
                            Rechazar
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
