<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { formatCurrency } from '@/composables/useFormat';
import { ArrowLeft, Loader2, Plus, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';

interface SaleItem {
    id: number;
    product_id: number;
    product_name: string;
    product_sku: string;
    category: string | null;
    quantity_sold: number;
    quantity_returned: number;
    returnable_quantity: number;
    unit_price: number;
    subtotal: number;
}

const props = defineProps<{
    sale: {
        id: number;
        folio: string;
        sale_date: string;
        total: number;
        customer: { id: number; name: string } | null;
    };
    items: SaleItem[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tablero', href: '/dashboard' },
    { title: 'Ventas', href: '/sales' },
    { title: `Venta ${props.sale.folio}`, href: `/sales/${props.sale.id}` },
    { title: 'Nueva devolución', href: `/sales/${props.sale.id}/returns/create` },
];

const form = useForm({
    reason: '',
    notes: '',
    items: props.items.map((i) => ({
        sale_item_id: i.id,
        quantity_returned: 0,
    })),
});

const itemMeta = computed(() => {
    const map: Record<number, SaleItem> = {};
    for (const item of props.items) {
        map[item.id] = item;
    }

    return map;
});

const subtotalOf = (saleItemId: number, qty: number): number => {
    const meta = itemMeta.value[saleItemId];
    if (!meta) return 0;
    return Math.round(meta.unit_price * qty * 100) / 100;
};

const total = computed(() =>
    form.items.reduce((acc, item) => acc + subtotalOf(item.sale_item_id, Number(item.quantity_returned) || 0), 0),
);

const maxFor = (saleItemId: number): number => itemMeta.value[saleItemId]?.returnable_quantity ?? 0;

const submit = () => {
    form.items = form.items.filter((i) => Number(i.quantity_returned) > 0);

    if (form.items.length === 0) {
        form.setError('items', 'Agrega al menos un producto a devolver.');
        return;
    }

    form.post(route('sales.returns.store', props.sale.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Nueva devolución" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-4xl p-4">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">Nueva devolución</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Devolución de productos de la venta
                        <Link :href="route('sales.show', sale.id)" class="font-mono text-primary hover:underline">
                            {{ sale.folio }}
                        </Link>
                        <span v-if="sale.customer"> · {{ sale.customer.name }}</span>
                    </p>
                </div>
                <Button variant="outline" as-child>
                    <Link :href="route('sales.show', sale.id)">
                        <ArrowLeft class="mr-1" />
                        Volver
                    </Link>
                </Button>
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <Card>
                    <CardHeader>
                        <CardTitle>Datos de la devolución</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="space-y-2">
                            <Label for="reason">Motivo</Label>
                            <textarea
                                id="reason"
                                v-model="form.reason"
                                rows="2"
                                required
                                class="flex min-h-[60px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                placeholder="¿Por qué se devuelven estos productos?"
                            />
                            <p v-if="form.errors.reason" class="text-sm text-destructive">{{ form.errors.reason }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="notes">Notas internas</Label>
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="2"
                                class="flex min-h-[60px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                placeholder="Detalles adicionales (opcional)"
                            />
                            <p v-if="form.errors.notes" class="text-sm text-destructive">{{ form.errors.notes }}</p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Productos a devolver</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <p v-if="form.errors.items" class="text-sm text-destructive">{{ form.errors.items }}</p>

                        <div
                            v-for="(item, index) in form.items"
                            :key="item.sale_item_id"
                            class="grid items-end gap-3 rounded-lg border border-border/60 p-3 sm:grid-cols-[1fr_6rem_7rem_2.5rem]"
                        >
                            <div class="space-y-1.5">
                                <Label class="text-xs">Producto</Label>
                                <div class="text-sm font-medium">{{ itemMeta[item.sale_item_id]?.product_name }}</div>
                                <div class="font-mono text-xs text-muted-foreground">
                                    {{ itemMeta[item.sale_item_id]?.product_sku }}
                                    · vendidos: {{ itemMeta[item.sale_item_id]?.quantity_sold }}
                                    · ya devueltos: {{ itemMeta[item.sale_item_id]?.quantity_returned }}
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <Label :for="`qty-${index}`" class="text-xs">A devolver</Label>
                                <Input
                                    :id="`qty-${index}`"
                                    v-model="item.quantity_returned"
                                    type="number"
                                    :min="0"
                                    :max="maxFor(item.sale_item_id)"
                                />
                            </div>

                            <div class="space-y-1.5">
                                <Label class="text-xs">Subtotal</Label>
                                <div class="flex h-9 items-center rounded-md border border-input bg-muted/40 px-3 text-sm tabular-nums">
                                    {{ formatCurrency(subtotalOf(item.sale_item_id, Number(item.quantity_returned) || 0)) }}
                                </div>
                            </div>

                            <div class="flex items-end justify-end">
                                <div class="flex h-9 items-center text-xs text-muted-foreground">
                                    Máx: {{ maxFor(item.sale_item_id) }}
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="text-right sm:text-left">
                        <p class="text-sm text-muted-foreground">Total a devolver</p>
                        <p class="text-3xl font-semibold tabular-nums">{{ formatCurrency(total) }}</p>
                    </div>
                    <div class="flex items-center justify-end gap-2">
                        <Button variant="outline" as-child>
                            <Link :href="route('sales.show', sale.id)">Cancelar</Link>
                        </Button>
                        <Button type="submit" :disabled="form.processing || total <= 0 || !form.reason">
                            <Loader2 v-if="form.processing" class="mr-1 animate-spin" />
                            Crear devolución (pendiente)
                        </Button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
