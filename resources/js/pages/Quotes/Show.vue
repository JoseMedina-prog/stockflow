<script setup lang="ts">
import ConfirmDialog from '@/components/stockflow/ConfirmDialog.vue';
import PageHeader from '@/components/stockflow/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { usePermissions } from '@/composables/usePermissions';
import { formatCurrency, formatDate, formatDateTime } from '@/composables/useFormat';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Check, CheckCircle, Edit, FileText, Mail, Phone, Printer, Send, ShoppingCart, Trash2, X } from 'lucide-vue-next';
import { ref } from 'vue';

interface QuoteData {
    id: number;
    folio: string;
    quote_date: string;
    valid_until: string | null;
    is_expired: boolean;
    subtotal: number;
    discount: number;
    tax: number;
    total: number;
    status: string;
    status_label: string;
    status_badge: string;
    notes: string | null;
    terms: string | null;
    sent_at: string | null;
    accepted_at: string | null;
    rejected_at: string | null;
    converted_at: string | null;
    converted_sale_id: number | null;
    can_edit: boolean;
    can_send: boolean;
    can_convert: boolean;
    opportunity: { id: number; name: string } | null;
    customer: { id: number; name: string; email: string | null; phone: string | null } | null;
    user: { id: number; name: string };
    items: Array<{
        id: number;
        product_id: number | null;
        product_name: string | null;
        product_sku: string | null;
        category: string | null;
        description: string | null;
        quantity: number;
        price: number;
        discount_percent: number;
        subtotal: number;
        line_total: number;
    }>;
}

const props = defineProps<{ quote: QuoteData }>();

const { can } = usePermissions();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tablero', href: '/dashboard' },
    { title: 'Cotizaciones', href: '/quotes' },
    { title: props.quote.folio, href: `/quotes/${props.quote.id}` },
];

const confirmDeleteOpen = ref(false);
const processing = ref(false);

const handleSend = () => {
    router.post(route('quotes.send', props.quote.id), {}, { preserveScroll: true });
};
const handleAccept = () => {
    router.post(route('quotes.accept', props.quote.id), {}, { preserveScroll: true });
};
const handleConvert = () => {
    if (!confirm('¿Convertir esta cotización en una venta? Se descontará el stock automáticamente.')) return;
    router.post(route('quotes.convert', props.quote.id), {}, { preserveScroll: true });
};
const handleDelete = () => {
    processing.value = true;
    router.delete(route('quotes.destroy', props.quote.id), {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
            confirmDeleteOpen.value = false;
        },
    });
};
const handleReject = () => {
    const reason = prompt('Motivo de rechazo (opcional):') ?? '';
    router.post(route('quotes.reject', props.quote.id), { reason }, { preserveScroll: true });
};
const handlePrint = () => {
    window.print();
};
</script>

<template>
    <Head :title="`Cotización ${quote.folio}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-4xl p-4">
            <div class="mb-6 flex flex-wrap items-start justify-between gap-2">
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl font-semibold tracking-tight">{{ quote.folio }}</h1>
                        <Badge :variant="quote.status_badge as any">{{ quote.status_label }}</Badge>
                        <Badge v-if="quote.is_expired" variant="destructive">Vencida</Badge>
                    </div>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Emitida el {{ formatDate(quote.quote_date) }}
                        <span v-if="quote.valid_until"> · Vigente hasta {{ formatDate(quote.valid_until) }}</span>
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <Button variant="outline" as-child>
                        <Link :href="route('quotes.index')">
                            <ArrowLeft class="mr-1" />
                            Volver
                        </Link>
                    </Button>
                    <Button variant="outline" @click="handlePrint">
                        <Printer class="mr-1" />
                        Imprimir
                    </Button>
                    <Button v-if="quote.can_edit" variant="outline" as-child>
                        <Link :href="route('quotes.edit', quote.id)">
                            <Edit class="mr-1" />
                            Editar
                        </Link>
                    </Button>
                    <Button v-if="quote.can_send" @click="handleSend">
                        <Send class="mr-1" />
                        Marcar enviada
                    </Button>
                    <Button v-if="quote.status === 'sent'" variant="outline" @click="handleAccept">
                        <Check class="mr-1" />
                        Aceptar
                    </Button>
                    <Button v-if="quote.status === 'sent'" variant="outline" class="text-destructive" @click="handleReject">
                        <X class="mr-1" />
                        Rechazar
                    </Button>
                    <Button v-if="quote.can_convert" @click="handleConvert">
                        <ShoppingCart class="mr-1" />
                        Convertir en venta
                    </Button>
                    <Button
                        v-if="quote.status !== 'converted' && can('quotes.delete')"
                        variant="outline"
                        size="icon"
                        class="text-destructive"
                        @click="confirmDeleteOpen = true"
                    >
                        <Trash2 />
                    </Button>
                </div>
            </div>

            <div v-if="quote.status === 'accepted'" class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-200">
                <p class="flex items-center gap-2 font-medium">
                    <CheckCircle class="size-4" />
                    Cotización aceptada por el cliente
                </p>
                <p v-if="quote.accepted_at" class="mt-1 text-xs">Aceptada el {{ formatDateTime(quote.accepted_at) }}.</p>
            </div>

            <div v-if="quote.converted_sale_id" class="mb-5 rounded-lg border border-teal-200 bg-teal-50 p-4 text-sm text-teal-900 dark:border-teal-900/50 dark:bg-teal-950/30 dark:text-teal-200">
                <p class="flex items-center gap-2 font-medium">
                    <CheckCircle class="size-4" />
                    Convertida en venta
                </p>
                <p class="mt-1 text-xs">
                    <Link :href="route('sales.show', quote.converted_sale_id)" class="font-semibold hover:underline">
                        Ver venta #{{ quote.converted_sale_id }}
                    </Link>
                </p>
            </div>

            <div class="grid gap-5 sm:grid-cols-3">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Cliente</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-1 text-sm">
                        <p v-if="quote.customer" class="font-medium">{{ quote.customer.name }}</p>
                        <p v-else class="text-muted-foreground">Consumidor final</p>
                        <p v-if="quote.customer?.email" class="flex items-center gap-1.5 text-xs text-muted-foreground">
                            <Mail class="size-3" /> {{ quote.customer.email }}
                        </p>
                        <p v-if="quote.customer?.phone" class="flex items-center gap-1.5 text-xs text-muted-foreground">
                            <Phone class="size-3" /> {{ quote.customer.phone }}
                        </p>
                        <Link v-if="quote.customer" :href="route('customers.index')" class="text-xs hover:underline">
                            Ver ficha del cliente →
                        </Link>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Comercial</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-1 text-sm">
                        <p>
                            <span class="text-muted-foreground">Vendedor:</span>
                            <span class="ml-1 font-medium">{{ quote.user.name }}</span>
                        </p>
                        <p v-if="quote.opportunity">
                            <span class="text-muted-foreground">Oportunidad:</span>
                            <Link :href="route('opportunities.show', quote.opportunity.id)" class="ml-1 font-medium hover:underline">
                                {{ quote.opportunity.name }}
                            </Link>
                        </p>
                        <p v-if="quote.sent_at" class="text-xs text-muted-foreground">Enviada: {{ formatDateTime(quote.sent_at) }}</p>
                        <p v-if="quote.accepted_at" class="text-xs text-muted-foreground">Aceptada: {{ formatDateTime(quote.accepted_at) }}</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Vigencia</CardTitle>
                    </CardHeader>
                    <CardContent class="text-sm">
                        <p v-if="quote.valid_until">
                            Hasta el <span class="font-medium">{{ formatDate(quote.valid_until) }}</span>
                        </p>
                        <p v-else class="text-muted-foreground">Sin fecha de vencimiento</p>
                        <p v-if="quote.is_expired" class="mt-1 text-xs text-destructive">Vencida — ya no se puede convertir automáticamente.</p>
                    </CardContent>
                </Card>
            </div>

            <Card class="mt-5">
                <CardHeader>
                    <CardTitle class="text-base">Detalle</CardTitle>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Producto / concepto</TableHead>
                                <TableHead class="text-center">Cantidad</TableHead>
                                <TableHead class="text-right">Precio</TableHead>
                                <TableHead class="text-right">Desc. %</TableHead>
                                <TableHead class="text-right">Importe</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="item in quote.items" :key="item.id">
                                <TableCell>
                                    <div class="font-medium">{{ item.description ?? item.product_name ?? '—' }}</div>
                                    <div v-if="item.product_sku" class="font-mono text-xs text-muted-foreground">
                                        {{ item.product_sku }} <span v-if="item.category">· {{ item.category }}</span>
                                    </div>
                                </TableCell>
                                <TableCell class="text-center tabular-nums">{{ item.quantity }}</TableCell>
                                <TableCell class="text-right tabular-nums">{{ formatCurrency(item.price) }}</TableCell>
                                <TableCell class="text-right tabular-nums text-muted-foreground">
                                    {{ item.discount_percent > 0 ? `${item.discount_percent}%` : '—' }}
                                </TableCell>
                                <TableCell class="text-right font-semibold tabular-nums">{{ formatCurrency(item.line_total) }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <div class="mt-4 space-y-1 border-t pt-4 text-sm">
                        <div class="flex justify-end gap-8">
                            <span class="text-muted-foreground">Subtotal</span>
                            <span class="w-32 text-right font-semibold tabular-nums">{{ formatCurrency(quote.subtotal) }}</span>
                        </div>
                        <div v-if="quote.discount > 0" class="flex justify-end gap-8">
                            <span class="text-muted-foreground">Descuento</span>
                            <span class="w-32 text-right tabular-nums">-{{ formatCurrency(quote.discount) }}</span>
                        </div>
                        <div v-if="quote.tax > 0" class="flex justify-end gap-8">
                            <span class="text-muted-foreground">Impuestos</span>
                            <span class="w-32 text-right tabular-nums">{{ formatCurrency(quote.tax) }}</span>
                        </div>
                        <Separator class="my-2" />
                        <div class="flex justify-end gap-8 text-base">
                            <span class="font-semibold">Total</span>
                            <span class="w-32 text-right font-semibold tabular-nums">{{ formatCurrency(quote.total) }}</span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <div v-if="quote.notes || quote.terms" class="mt-5 grid gap-5 md:grid-cols-2">
                <Card v-if="quote.notes">
                    <CardHeader><CardTitle class="text-base">Notas</CardTitle></CardHeader>
                    <CardContent><p class="whitespace-pre-line text-sm">{{ quote.notes }}</p></CardContent>
                </Card>
                <Card v-if="quote.terms">
                    <CardHeader><CardTitle class="text-base">Términos y condiciones</CardTitle></CardHeader>
                    <CardContent><p class="whitespace-pre-line text-sm">{{ quote.terms }}</p></CardContent>
                </Card>
            </div>
        </div>

        <ConfirmDialog
            v-model:open="confirmDeleteOpen"
            title="Eliminar cotización"
            :description="`¿Estás seguro de eliminar la cotización ${quote.folio}? Esta acción no se puede deshacer.`"
            :processing="processing"
            @confirm="handleDelete"
        />
    </AppLayout>
</template>
