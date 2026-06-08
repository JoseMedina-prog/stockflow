<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { formatCurrency } from '@/composables/useFormat';
import { Loader2, Plus, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';

interface ProductOption {
    id: number;
    name: string;
    sku: string;
    price: number;
    stock: number;
    category: string | null;
    is_low_stock: boolean;
}

interface CustomerOption {
    id: number;
    name: string;
    email: string | null;
}

interface TaxOption {
    id: number;
    code: string;
    name: string;
    rate: number;
    percent: number;
}

const props = defineProps<{
    products: ProductOption[];
    customers: CustomerOption[];
    taxes: TaxOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tablero', href: '/dashboard' },
    { title: 'Ventas', href: '/sales' },
    { title: 'Nueva', href: '/sales/create' },
];

const today = new Date().toISOString().slice(0, 16);

const form = useForm({
    customer_id: '' as string,
    sale_date: today,
    items: [
        { product_id: '' as string, quantity: 1, price: 0, tax_id: '' as string },
    ] as Array<{ product_id: string; quantity: number; price: number; tax_id: string }>,
});

const addItem = () => {
    form.items.push({ product_id: '', quantity: 1, price: 0, tax_id: '' });
};

const removeItem = (index: number) => {
    form.items.splice(index, 1);
    if (form.items.length === 0) addItem();
};

const getProduct = (id: string): ProductOption | undefined =>
    props.products.find((p) => String(p.id) === String(id));

const getTax = (id: string): TaxOption | undefined =>
    props.taxes.find((t) => String(t.id) === String(id));

const onProductChange = (index: number) => {
    const product = getProduct(form.items[index].product_id);
    if (product) {
        form.items[index].price = product.price;
    }
};

const subtotalOf = (item: { product_id: string; quantity: number; price: number; tax_id: string }): number => {
    const qty = Number(item.quantity) || 0;
    const price = Number(item.price) || 0;
    return Math.round(qty * price * 100) / 100;
};

const taxAmountOf = (item: { product_id: string; quantity: number; price: number; tax_id: string }): number => {
    const tax = getTax(item.tax_id);
    if (!tax || tax.rate === 0) return 0;
    return Math.round(subtotalOf(item) * tax.rate * 100) / 100;
};

const subtotal = computed(() =>
    Math.round(form.items.reduce((acc, item) => acc + subtotalOf(item), 0) * 100) / 100,
);

const taxesTotal = computed(() =>
    Math.round(form.items.reduce((acc, item) => acc + taxAmountOf(item), 0) * 100) / 100,
);

const total = computed(() => Math.round((subtotal.value + taxesTotal.value) * 100) / 100);

const submit = () => {
    form.post(route('sales.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Nueva venta" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-4xl p-4">
            <div class="mb-6">
                <h1 class="text-2xl font-semibold tracking-tight">Nueva venta</h1>
                <p class="mt-1 text-sm text-muted-foreground">Registra los productos vendidos y descuenta el stock automáticamente.</p>
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <Card>
                    <CardHeader>
                        <CardTitle>Datos de la venta</CardTitle>
                    </CardHeader>
                    <CardContent class="grid gap-5 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="customer_id">Cliente</Label>
                            <Select id="customer_id" v-model="form.customer_id">
                                <option value="">Consumidor final</option>
                                <option v-for="c in customers" :key="c.id" :value="c.id">
                                    {{ c.name }}
                                </option>
                            </Select>
                        </div>

                        <div class="space-y-2">
                            <Label for="sale_date">Fecha y hora</Label>
                            <Input
                                id="sale_date"
                                v-model="form.sale_date"
                                type="datetime-local"
                                required
                            />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0">
                        <CardTitle>Productos</CardTitle>
                        <Button type="button" variant="outline" size="sm" @click="addItem">
                            <Plus class="mr-1" />
                            Agregar producto
                        </Button>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div
                            v-for="(item, index) in form.items"
                            :key="index"
                            class="grid items-end gap-3 rounded-lg border border-border/60 p-3 sm:grid-cols-[1.5fr_5rem_7rem_8rem_7rem_2.5rem]"
                        >
                            <div class="space-y-1.5">
                                <Label :for="`product-${index}`" class="text-xs">Producto</Label>
                                <Select
                                    :id="`product-${index}`"
                                    v-model="item.product_id"
                                    required
                                    @update:model-value="onProductChange(index)"
                                >
                                    <option value="" disabled>Selecciona...</option>
                                    <option
                                        v-for="p in products"
                                        :key="p.id"
                                        :value="p.id"
                                        :disabled="p.stock === 0"
                                    >
                                        {{ p.name }} ({{ p.sku }}) — stock: {{ p.stock }}
                                    </option>
                                </Select>
                                <p
                                    v-if="getProduct(item.product_id) && getProduct(item.product_id)!.stock < item.quantity"
                                    class="text-xs text-destructive"
                                >
                                    Stock insuficiente ({{ getProduct(item.product_id)!.stock }} disponibles)
                                </p>
                            </div>

                            <div class="space-y-1.5">
                                <Label :for="`qty-${index}`" class="text-xs">Cant.</Label>
                                <Input
                                    :id="`qty-${index}`"
                                    v-model.number="item.quantity"
                                    type="number"
                                    min="1"
                                    required
                                />
                            </div>

                            <div class="space-y-1.5">
                                <Label :for="`price-${index}`" class="text-xs">Precio</Label>
                                <Input
                                    :id="`price-${index}`"
                                    v-model.number="item.price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    required
                                />
                            </div>

                            <div class="space-y-1.5">
                                <Label :for="`tax-${index}`" class="text-xs">Impuesto</Label>
                                <Select :id="`tax-${index}`" v-model="item.tax_id">
                                    <option value="">Sin impuesto</option>
                                    <option v-for="t in taxes" :key="t.id" :value="t.id">
                                        {{ t.code }} — {{ t.percent }}%
                                    </option>
                                </Select>
                            </div>

                            <div class="space-y-1.5">
                                <Label class="text-xs">Subtotal</Label>
                                <div class="flex h-9 items-center rounded-md border border-input bg-muted/40 px-3 text-sm tabular-nums">
                                    {{ formatCurrency(subtotalOf(item)) }}
                                </div>
                                <p v-if="taxAmountOf(item) > 0" class="text-xs text-muted-foreground tabular-nums">
                                    + {{ formatCurrency(taxAmountOf(item)) }} imp.
                                </p>
                            </div>

                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                class="text-destructive hover:text-destructive"
                                :disabled="form.items.length === 1"
                                @click="removeItem(index)"
                            >
                                <Trash2 />
                            </Button>
                        </div>

                        <p v-if="form.errors.items" class="text-sm text-destructive">
                            {{ form.errors.items }}
                        </p>
                    </CardContent>
                </Card>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="space-y-1 text-right sm:text-left">
                        <p class="text-sm text-muted-foreground">
                            Subtotal: <span class="font-semibold tabular-nums">{{ formatCurrency(subtotal) }}</span>
                            <span v-if="taxesTotal > 0" class="ml-3">
                                Impuestos: <span class="font-semibold tabular-nums">{{ formatCurrency(taxesTotal) }}</span>
                            </span>
                        </p>
                        <p class="text-3xl font-semibold tabular-nums">
                            {{ formatCurrency(total) }}
                        </p>
                    </div>
                    <div class="flex items-center justify-end gap-2">
                        <Button variant="outline" as-child>
                            <Link :href="route('sales.index')">Cancelar</Link>
                        </Button>
                        <Button type="submit" :disabled="form.processing || total <= 0">
                            <Loader2 v-if="form.processing" class="mr-1 animate-spin" />
                            Registrar venta
                        </Button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
