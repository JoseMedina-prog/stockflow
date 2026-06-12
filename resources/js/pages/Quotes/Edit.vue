<script setup lang="ts">
import PageHeader from '@/components/stockflow/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatCurrency } from '@/composables/useFormat';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Loader2, Plus, Search, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface ProductOption {
    id: number;
    name: string;
    sku: string;
    price: number;
    stock: number;
    category: string | null;
}

interface CustomerOption {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
}

interface QuoteFormItem {
    product_id: number | null;
    description: string;
    quantity: number;
    price: number;
    discount_percent: number;
}

interface QuoteData {
    id: number;
    folio: string;
    customer_id: number | null;
    opportunity_id: number | null;
    customer: CustomerOption | null;
    opportunity: { id: number; name: string } | null;
    quote_date: string;
    valid_until: string | null;
    discount: number;
    tax: number;
    notes: string | null;
    terms: string | null;
    items: QuoteFormItem[];
}

const props = defineProps<{
    quote: QuoteData;
    products: ProductOption[];
    customers: CustomerOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tablero', href: '/dashboard' },
    { title: 'Cotizaciones', href: '/quotes' },
    { title: props.quote.folio, href: `/quotes/${props.quote.id}` },
    { title: 'Editar', href: `/quotes/${props.quote.id}/edit` },
];

const form = useForm<{
    customer_id: number | null;
    opportunity_id: number | null;
    quote_date: string;
    valid_until: string;
    discount: number;
    tax: number;
    notes: string;
    terms: string;
    items: QuoteFormItem[];
}>({
    customer_id: props.quote.customer_id,
    opportunity_id: props.quote.opportunity_id,
    quote_date: props.quote.quote_date,
    valid_until: props.quote.valid_until ?? '',
    discount: props.quote.discount,
    tax: props.quote.tax,
    notes: props.quote.notes ?? '',
    terms: props.quote.terms ?? '',
    items: props.quote.items.length > 0 ? props.quote.items : [{ product_id: null, description: '', quantity: 1, price: 0, discount_percent: 0 }],
});

const productSearch = ref('');

const filteredProducts = computed(() => {
    const s = productSearch.value.toLowerCase().trim();
    if (!s) return props.products.slice(0, 50);
    return props.products.filter((p) => p.name.toLowerCase().includes(s) || p.sku.toLowerCase().includes(s)).slice(0, 50);
});

const lineTotal = (item: QuoteFormItem) => {
    const sub = item.price * item.quantity;
    const disc = sub * (item.discount_percent / 100);
    return Math.max(0, sub - disc);
};

const subtotal = computed(() => form.items.reduce((acc, it) => acc + lineTotal(it), 0));
const total = computed(() => Math.max(0, subtotal.value - form.discount + form.tax));

const addItem = () => {
    form.items.push({ product_id: null, description: '', quantity: 1, price: 0, discount_percent: 0 });
};
const removeItem = (idx: number) => {
    form.items.splice(idx, 1);
    if (form.items.length === 0) addItem();
};

const selectProduct = (idx: number, product: ProductOption) => {
    form.items[idx].product_id = product.id;
    form.items[idx].price = product.price;
    if (!form.items[idx].description) {
        form.items[idx].description = product.name;
    }
    productSearch.value = '';
};

const submit = () => {
    form.put(route('quotes.update', props.quote.id), { preserveScroll: true });
};
</script>

<template>
    <Head :title="`Editar · ${quote.folio}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <PageHeader :title="`Editar ${quote.folio}`" description="Modifica los datos de la cotización en borrador.">
                <template #actions>
                    <Button variant="outline" as-child>
                        <Link :href="route('quotes.show', quote.id)">
                            <ArrowLeft class="mr-1" />
                            Volver
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <form @submit.prevent="submit" class="space-y-5">
                <div class="grid gap-4 md:grid-cols-3">
                    <div class="space-y-2">
                        <Label for="customer_id">Cliente</Label>
                        <select
                            id="customer_id"
                            v-model="form.customer_id"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-2 text-sm"
                        >
                            <option :value="null">Consumidor final / sin cliente</option>
                            <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <Label for="opportunity_id">Oportunidad (opcional)</Label>
                        <Input id="opportunity_id" v-model.number="form.opportunity_id" type="number" min="0" />
                    </div>
                    <div class="space-y-2">
                        <Label for="quote_date">Fecha</Label>
                        <Input id="quote_date" v-model="form.quote_date" type="date" required />
                    </div>
                    <div class="space-y-2">
                        <Label for="valid_until">Vigente hasta</Label>
                        <Input id="valid_until" v-model="form.valid_until" type="date" />
                    </div>
                </div>

                <div class="rounded-xl border border-border/60">
                    <div class="flex items-center justify-between border-b border-border/60 p-3">
                        <h3 class="text-sm font-semibold">Conceptos</h3>
                        <Button type="button" size="sm" variant="outline" @click="addItem">
                            <Plus class="mr-1" />
                            Agregar
                        </Button>
                    </div>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-2/5">Producto / descripción</TableHead>
                                <TableHead class="w-24 text-center">Cantidad</TableHead>
                                <TableHead class="w-32 text-right">Precio</TableHead>
                                <TableHead class="w-24 text-right">Desc. %</TableHead>
                                <TableHead class="w-32 text-right">Importe</TableHead>
                                <TableHead class="w-12"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="(item, idx) in form.items" :key="idx">
                                <TableCell>
                                    <div class="space-y-1">
                                        <Input v-model="item.description" placeholder="Descripción libre o nombre del producto" />
                                        <details class="text-xs text-muted-foreground">
                                            <summary class="cursor-pointer">Elegir producto del catálogo</summary>
                                            <div class="relative mt-1.5">
                                                <Search class="absolute left-2 top-2 size-3.5 text-muted-foreground" />
                                                <Input v-model="productSearch" placeholder="Buscar producto..." class="h-7 pl-7 text-xs" />
                                            </div>
                                            <div class="mt-1.5 max-h-40 overflow-y-auto rounded-md border border-border/60 bg-muted/30">
                                                <button
                                                    v-for="p in filteredProducts"
                                                    :key="p.id"
                                                    type="button"
                                                    class="block w-full px-2 py-1 text-left text-xs hover:bg-accent"
                                                    @click="selectProduct(idx, p)"
                                                >
                                                    <span class="font-medium">{{ p.name }}</span>
                                                    <span class="ml-1 font-mono text-muted-foreground">{{ p.sku }}</span>
                                                    <span class="float-right tabular-nums">{{ formatCurrency(p.price) }}</span>
                                                </button>
                                            </div>
                                        </details>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Input v-model.number="item.quantity" type="number" min="1" class="h-9 text-center" />
                                </TableCell>
                                <TableCell>
                                    <Input v-model.number="item.price" type="number" min="0" step="0.01" class="h-9 text-right" />
                                </TableCell>
                                <TableCell>
                                    <Input
                                        v-model.number="item.discount_percent"
                                        type="number"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        class="h-9 text-right"
                                    />
                                </TableCell>
                                <TableCell class="text-right font-semibold tabular-nums">{{ formatCurrency(lineTotal(item)) }}</TableCell>
                                <TableCell>
                                    <Button type="button" variant="ghost" size="icon" class="text-destructive" @click="removeItem(idx)">
                                        <Trash2 class="size-4" />
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <div class="space-y-3">
                        <div class="space-y-2">
                            <Label for="notes">Notas</Label>
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="3"
                                class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="terms">Términos y condiciones</Label>
                            <textarea
                                id="terms"
                                v-model="form.terms"
                                rows="3"
                                class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm"
                            />
                        </div>
                    </div>
                    <div class="space-y-2 rounded-lg border border-border/60 bg-muted/30 p-4 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-muted-foreground">Subtotal</span>
                            <span class="font-semibold tabular-nums">{{ formatCurrency(subtotal) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-muted-foreground">Descuento</span>
                            <Input v-model.number="form.discount" type="number" min="0" step="0.01" class="h-7 w-32 text-right" />
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-muted-foreground">Impuestos</span>
                            <Input v-model.number="form.tax" type="number" min="0" step="0.01" class="h-7 w-32 text-right" />
                        </div>
                        <div class="my-1 border-t border-border/60"></div>
                        <div class="flex items-center justify-between text-base">
                            <span class="font-semibold">Total</span>
                            <span class="font-semibold tabular-nums">{{ formatCurrency(total) }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2">
                    <Button variant="outline" as-child>
                        <Link :href="route('quotes.show', quote.id)">Cancelar</Link>
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <Loader2 v-if="form.processing" class="mr-1 animate-spin" />
                        Guardar cambios
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
