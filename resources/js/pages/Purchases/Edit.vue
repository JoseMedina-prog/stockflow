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

interface PurchaseItem {
    product_id: number;
    quantity: number;
    unit_cost: number;
}

const props = defineProps<{
    purchase: {
        id: number;
        folio: string;
        supplier_id: number;
        purchase_date: string;
        notes: string | null;
        items: PurchaseItem[];
    };
    products: ProductOption[];
    suppliers: SupplierOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tablero', href: '/dashboard' },
    { title: 'Compras', href: '/purchases' },
    { title: 'Editar', href: `/purchases/${props.purchase.id}/edit` },
];

const form = useForm({
    supplier_id: String(props.purchase.supplier_id),
    purchase_date: props.purchase.purchase_date,
    notes: props.purchase.notes ?? '',
    items: props.purchase.items.map((i) => ({
        product_id: String(i.product_id),
        quantity: i.quantity,
        unit_cost: i.unit_cost,
    })) as Array<{ product_id: string; quantity: number; unit_cost: number }>,
});

const addItem = () => {
    form.items.push({ product_id: '', quantity: 1, unit_cost: 0 });
};

const removeItem = (index: number) => {
    form.items.splice(index, 1);
    if (form.items.length === 0) addItem();
};

const getProduct = (id: string): ProductOption | undefined => props.products.find((p) => String(p.id) === String(id));

const onProductChange = (index: number) => {
    const product = getProduct(form.items[index].product_id);
    if (product) {
        form.items[index].unit_cost = product.price;
    }
};

const subtotalOf = (item: { product_id: string; quantity: number; unit_cost: number }): number => {
    const qty = Number(item.quantity) || 0;
    const cost = Number(item.unit_cost) || 0;
    return Math.round(qty * cost * 100) / 100;
};

const total = computed(() => Math.round(form.items.reduce((acc, item) => acc + subtotalOf(item), 0) * 100) / 100);

const submit = () => {
    form.put(route('purchases.update', props.purchase.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Editar compra" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-4xl p-4">
            <div class="mb-6">
                <h1 class="text-2xl font-semibold tracking-tight">Editar compra</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Modifica los datos de la compra <span class="font-mono">{{ purchase.folio }}</span
                    >.
                </p>
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
                            />
                            <p v-if="form.errors.notes" class="text-sm text-destructive">{{ form.errors.notes }}</p>
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
                            class="grid items-end gap-3 rounded-lg border border-border/60 p-3 sm:grid-cols-[1fr_5rem_7rem_7rem_2.5rem]"
                        >
                            <div class="space-y-1.5">
                                <Label :for="`product-${index}`" class="text-xs">Producto</Label>
                                <Select :id="`product-${index}`" v-model="item.product_id" required @update:model-value="onProductChange(index)">
                                    <option value="" disabled>Selecciona...</option>
                                    <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} ({{ p.sku }})</option>
                                </Select>
                            </div>

                            <div class="space-y-1.5">
                                <Label :for="`qty-${index}`" class="text-xs">Cant.</Label>
                                <Input :id="`qty-${index}`" v-model="item.quantity" type="number" min="1" required />
                            </div>

                            <div class="space-y-1.5">
                                <Label :for="`cost-${index}`" class="text-xs">Costo</Label>
                                <Input :id="`cost-${index}`" v-model="item.unit_cost" type="number" step="0.01" min="0" required />
                            </div>

                            <div class="space-y-1.5">
                                <Label class="text-xs">Subtotal</Label>
                                <div class="flex h-9 items-center rounded-md border border-input bg-muted/40 px-3 text-sm tabular-nums">
                                    {{ formatCurrency(subtotalOf(item)) }}
                                </div>
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
                    <div class="text-right sm:text-left">
                        <p class="text-sm text-muted-foreground">Total de la compra</p>
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
                            Actualizar
                        </Button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
