<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import { formatCurrency } from '@/composables/useFormat';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Loader2, Plus, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';

interface ProductOption {
    id: number;
    name: string;
    sku: string;
    price: number;
    stock: number;
    category: string | null;
}

interface SupplierOption {
    id: number;
    name: string;
    tax_id: string | null;
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
    suppliers: SupplierOption[];
    taxes: TaxOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tablero', href: '/dashboard' },
    { title: 'Compras', href: '/purchases' },
    { title: 'Nueva', href: '/purchases/create' },
];

const today = new Date().toISOString().slice(0, 10);

const form = useForm({
    supplier_id: '' as string,
    purchase_date: today,
    receive_immediately: true,
    notes: '',
    items: [{ product_id: '' as string, quantity: 1, unit_cost: 0, tax_id: '' as string }] as Array<{
        product_id: string;
        quantity: number;
        unit_cost: number;
        tax_id: string;
    }>,
});

const addItem = () => {
    form.items.push({ product_id: '', quantity: 1, unit_cost: 0, tax_id: '' });
};

const removeItem = (index: number) => {
    form.items.splice(index, 1);
    if (form.items.length === 0) addItem();
};

const getProduct = (id: string): ProductOption | undefined => props.products.find((p) => String(p.id) === String(id));

const getTax = (id: string): TaxOption | undefined => props.taxes.find((t) => String(t.id) === String(id));

const onProductChange = (index: number) => {
    const product = getProduct(form.items[index].product_id);
    if (product) {
        form.items[index].unit_cost = product.price;
    }
};

const subtotalOf = (item: { product_id: string; quantity: number; unit_cost: number; tax_id: string }): number => {
    const qty = Number(item.quantity) || 0;
    const cost = Number(item.unit_cost) || 0;
    return Math.round(qty * cost * 100) / 100;
};

const taxAmountOf = (item: { product_id: string; quantity: number; unit_cost: number; tax_id: string }): number => {
    const tax = getTax(item.tax_id);
    if (!tax || tax.rate === 0) return 0;
    return Math.round(subtotalOf(item) * tax.rate * 100) / 100;
};

const subtotal = computed(() => Math.round(form.items.reduce((acc, item) => acc + subtotalOf(item), 0) * 100) / 100);

const taxesTotal = computed(() => Math.round(form.items.reduce((acc, item) => acc + taxAmountOf(item), 0) * 100) / 100);

const total = computed(() => Math.round((subtotal.value + taxesTotal.value) * 100) / 100);

const submit = () => {
    form.post(route('purchases.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Nueva compra" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-4xl p-4">
            <div class="mb-6">
                <h1 class="text-2xl font-semibold tracking-tight">Nueva compra</h1>
                <p class="mt-1 text-sm text-muted-foreground">Registra los productos comprados a un proveedor.</p>
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <Card>
                    <CardHeader>
                        <CardTitle>Datos de la compra</CardTitle>
                    </CardHeader>
                    <CardContent class="grid gap-5 sm:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="supplier_id">Proveedor</Label>
                            <Select id="supplier_id" v-model="form.supplier_id" required>
                                <option value="" disabled>Selecciona...</option>
                                <option v-for="s in suppliers" :key="s.id" :value="s.id">
                                    {{ s.name }}<span v-if="s.tax_id"> · {{ s.tax_id }}</span>
                                </option>
                            </Select>
                            <p v-if="form.errors.supplier_id" class="text-sm text-destructive">{{ form.errors.supplier_id }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="purchase_date">Fecha</Label>
                            <Input id="purchase_date" v-model="form.purchase_date" type="date" required />
                            <p v-if="form.errors.purchase_date" class="text-sm text-destructive">{{ form.errors.purchase_date }}</p>
                        </div>

                        <div class="space-y-2 sm:col-span-2">
                            <Label for="notes">Notas</Label>
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="2"
                                class="flex min-h-[60px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                placeholder="Condiciones de pago, número de factura, observaciones..."
                            />
                            <p v-if="form.errors.notes" class="text-sm text-destructive">{{ form.errors.notes }}</p>
                        </div>

                        <label class="flex items-center gap-2 text-sm sm:col-span-2">
                            <input
                                v-model="form.receive_immediately"
                                type="checkbox"
                                class="size-4 rounded border-input text-primary focus:ring-1 focus:ring-ring"
                            />
                            <span>Recibir mercancía al guardar (incrementa el stock)</span>
                        </label>
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
                                <Select :id="`product-${index}`" v-model="item.product_id" required @update:model-value="onProductChange(index)">
                                    <option value="" disabled>Selecciona...</option>
                                    <option v-for="p in products" :key="p.id" :value="p.id">
                                        {{ p.name }} ({{ p.sku }}) — stock actual: {{ p.stock }}
                                    </option>
                                </Select>
                            </div>

                            <div class="space-y-1.5">
                                <Label :for="`qty-${index}`" class="text-xs">Cant.</Label>
                                <Input :id="`qty-${index}`" v-model.number="item.quantity" type="number" min="1" required />
                            </div>

                            <div class="space-y-1.5">
                                <Label :for="`cost-${index}`" class="text-xs">Costo</Label>
                                <Input :id="`cost-${index}`" v-model.number="item.unit_cost" type="number" step="0.01" min="0" required />
                            </div>

                            <div class="space-y-1.5">
                                <Label :for="`tax-${index}`" class="text-xs">Impuesto</Label>
                                <Select :id="`tax-${index}`" v-model="item.tax_id">
                                    <option value="">Sin impuesto</option>
                                    <option v-for="t in taxes" :key="t.id" :value="t.id">{{ t.code }} — {{ t.percent }}%</option>
                                </Select>
                            </div>

                            <div class="space-y-1.5">
                                <Label class="text-xs">Subtotal</Label>
                                <div class="flex h-9 items-center rounded-md border border-input bg-muted/40 px-3 text-sm tabular-nums">
                                    {{ formatCurrency(subtotalOf(item)) }}
                                </div>
                                <p v-if="taxAmountOf(item) > 0" class="text-xs tabular-nums text-muted-foreground">
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
                            <Link :href="route('purchases.index')">Cancelar</Link>
                        </Button>
                        <Button type="submit" :disabled="form.processing || total <= 0 || !form.supplier_id">
                            <Loader2 v-if="form.processing" class="mr-1 animate-spin" />
                            Registrar compra
                        </Button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
