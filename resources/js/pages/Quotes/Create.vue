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
import { AlertCircle, ArrowLeft, Loader2, Plus, Search, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

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

interface OpportunityOption {
    id: number;
    name: string;
    customer?: { id: number; name: string; email: string | null; phone: string | null } | null;
}

interface Defaults {
    customer_id: number | null;
    opportunity_id: number | null;
    quote_date: string;
    valid_until: string;
    status: string;
}

interface QuoteFormItem {
    product_id: number | null;
    description: string;
    quantity: number;
    price: number;
    discount_percent: number;
}

const props = defineProps<{
    products: ProductOption[];
    customers: CustomerOption[];
    opportunity: OpportunityOption | null;
    defaults: Defaults;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tablero', href: '/dashboard' },
    { title: 'Ventas', href: '/sales' },
    { title: 'Cotizaciones', href: '/quotes' },
    { title: 'Nueva', href: '/quotes/create' },
];

const form = useForm<{
    customer_id: number | null;
    opportunity_id: number | null;
    quote_date: string;
    valid_until: string;
    discount: number;
    tax: number;
    status: string;
    notes: string;
    terms: string;
    items: QuoteFormItem[];
}>({
    customer_id: props.defaults.customer_id,
    opportunity_id: props.defaults.opportunity_id,
    quote_date: props.defaults.quote_date,
    valid_until: props.defaults.valid_until,
    discount: 0,
    tax: 0,
    status: props.defaults.status,
    notes: '',
    terms: 'Precios en pesos. Vigencia de la cotización según la fecha indicada. Una vez aceptada, se requiere anticipo del 50% para iniciar el pedido.',
    items: [{ product_id: null, description: '', quantity: 1, price: 0, discount_percent: 0 }],
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

const onOpportunityIdChange = (id: number | null) => {
    if (id) {
        const opp = props.opportunity && props.opportunity.id === id ? props.opportunity : null;
        if (opp?.customer) {
            form.customer_id = opp.customer.id;
        }
    }
};

watch(() => form.opportunity_id, onOpportunityIdChange);

const submit = () => {
    form.post(route('quotes.store'), { preserveScroll: true });
};

const humanizeField = (field: string): string => {
    const labels: Record<string, string> = {
        quote_date: 'Fecha',
        valid_until: 'Vigente hasta',
        customer_id: 'Cliente',
        opportunity_id: 'Oportunidad',
        status: 'Estado',
        discount: 'Descuento global',
        tax: 'Impuestos',
        items: 'Conceptos',
        notes: 'Notas',
        terms: 'Términos',
    };
    if (labels[field]) return labels[field];
    const itemMatch = field.match(/^items\.(\d+)\.(\w+)$/);
    if (itemMatch) {
        const itemLabels: Record<string, string> = {
            description: 'descripción',
            quantity: 'cantidad',
            price: 'precio',
            discount_percent: 'descuento %',
            product_id: 'producto',
        };
        return `Concepto #${Number(itemMatch[1]) + 1} · ${itemLabels[itemMatch[2]] ?? itemMatch[2]}`;
    }
    return field;
};
</script>

<template>
    <Head title="Nueva cotización" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <PageHeader title="Nueva cotización" description="Captura los datos de la cotización y los productos o servicios.">
                <template #actions>
                    <Button variant="outline" as-child>
                        <Link :href="route('quotes.index')">
                            <ArrowLeft class="mr-1" />
                            Volver
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <form @submit.prevent="submit" class="space-y-5">
                <div
                    v-if="form.errors && Object.keys(form.errors).length > 0"
                    class="flex items-start gap-3 rounded-lg border border-destructive/40 bg-destructive/10 p-4 text-sm text-destructive"
                >
                    <AlertCircle class="mt-0.5 size-5 shrink-0" />
                    <div>
                        <p class="font-semibold">No pudimos guardar la cotización. Revisa los siguientes campos:</p>
                        <ul class="mt-1 list-inside list-disc space-y-0.5">
                            <li v-for="(message, field) in form.errors" :key="field">{{ humanizeField(String(field)) }}: {{ message }}</li>
                        </ul>
                    </div>
                </div>

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
                        <p v-if="form.errors.customer_id" class="text-xs text-destructive">{{ form.errors.customer_id }}</p>
                    </div>
                    <div class="space-y-2">
                        <Label for="opportunity_id">Oportunidad (opcional)</Label>
                        <Input id="opportunity_id" v-model.number="form.opportunity_id" type="number" min="0" placeholder="ID de oportunidad" />
                        <p v-if="opportunity && form.opportunity_id === opportunity.id" class="text-xs text-muted-foreground">
                            Origen: {{ opportunity.name }}
                        </p>
                        <p v-if="form.errors.opportunity_id" class="text-xs text-destructive">{{ form.errors.opportunity_id }}</p>
                    </div>
                    <div class="space-y-2">
                        <Label>Estado</Label>
                        <select v-model="form.status" class="flex h-9 w-full rounded-md border border-input bg-background px-2 text-sm">
                            <option value="draft">Borrador</option>
                            <option value="sent">Enviada</option>
                        </select>
                        <p v-if="form.errors.status" class="text-xs text-destructive">{{ form.errors.status }}</p>
                    </div>
                    <div class="space-y-2">
                        <Label for="quote_date">Fecha</Label>
                        <Input id="quote_date" v-model="form.quote_date" type="date" required />
                        <p v-if="form.errors.quote_date" class="text-xs text-destructive">{{ form.errors.quote_date }}</p>
                    </div>
                    <div class="space-y-2">
                        <Label for="valid_until">Vigente hasta</Label>
                        <Input id="valid_until" v-model="form.valid_until" type="date" />
                        <p v-if="form.errors.valid_until" class="text-xs text-destructive">{{ form.errors.valid_until }}</p>
                    </div>
                    <div class="space-y-2">
                        <Label for="discount">Descuento global</Label>
                        <Input id="discount" v-model.number="form.discount" type="number" min="0" step="0.01" />
                        <p v-if="form.errors.discount" class="text-xs text-destructive">{{ form.errors.discount }}</p>
                    </div>
                </div>

                <div class="rounded-xl border border-border/60">
                    <div class="flex items-center justify-between border-b border-border/60 p-3">
                        <h3 class="text-sm font-semibold">Conceptos</h3>
                        <Button type="button" size="sm" variant="outline" @click="addItem">
                            <Plus class="mr-1" />
                            Agregar concepto
                        </Button>
                    </div>
                    <p v-if="form.errors.items" class="border-b border-border/60 bg-destructive/10 px-3 py-2 text-xs text-destructive">
                        {{ form.errors.items }}
                    </p>
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
                                        <p v-if="item.product_id" class="text-xs text-muted-foreground">Catálogo #{{ item.product_id }}</p>
                                        <p v-if="form.errors[`items.${idx}.description`]" class="text-xs text-destructive">
                                            {{ form.errors[`items.${idx}.description`] }}
                                        </p>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <Input v-model.number="item.quantity" type="number" min="1" class="h-9 text-center" />
                                    <p v-if="form.errors[`items.${idx}.quantity`]" class="mt-1 text-xs text-destructive">
                                        {{ form.errors[`items.${idx}.quantity`] }}
                                    </p>
                                </TableCell>
                                <TableCell>
                                    <Input v-model.number="item.price" type="number" min="0" step="0.01" class="h-9 text-right" />
                                    <p v-if="form.errors[`items.${idx}.price`]" class="mt-1 text-xs text-destructive">
                                        {{ form.errors[`items.${idx}.price`] }}
                                    </p>
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
                                    <p v-if="form.errors[`items.${idx}.discount_percent`]" class="mt-1 text-xs text-destructive">
                                        {{ form.errors[`items.${idx}.discount_percent`] }}
                                    </p>
                                </TableCell>
                                <TableCell class="text-right font-semibold tabular-nums">
                                    {{ formatCurrency(lineTotal(item)) }}
                                </TableCell>
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
                                placeholder="Información adicional para el cliente..."
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
                        <Link :href="route('quotes.index')">Cancelar</Link>
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <Loader2 v-if="form.processing" class="mr-1 animate-spin" />
                        Guardar cotización
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
