<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Loader2 } from 'lucide-vue-next';

interface Product {
    id: number;
    category_id: number;
    name: string;
    sku: string;
    price: number;
    stock: number;
    min_stock: number;
    description: string | null;
}

const props = defineProps<{
    product: Product;
    categories: { id: number; name: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tablero', href: '/dashboard' },
    { title: 'Productos', href: '/products' },
    { title: 'Editar', href: `/products/${props.product.id}/edit` },
];

const form = useForm({
    category_id: String(props.product.category_id),
    name: props.product.name,
    sku: props.product.sku,
    price: Number(props.product.price),
    stock: props.product.stock,
    min_stock: props.product.min_stock,
    description: props.product.description ?? '',
});

const submit = () => {
    form.put(route('products.update', props.product.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Editar producto" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-3xl p-4">
            <div class="mb-6">
                <h1 class="text-2xl font-semibold tracking-tight">Editar producto</h1>
                <p class="mt-1 text-sm text-muted-foreground">Modifica los datos del producto.</p>
            </div>

            <form @submit.prevent="submit" class="space-y-5 rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="space-y-2 sm:col-span-2">
                        <Label for="category_id">Categoría</Label>
                        <Select id="category_id" v-model="form.category_id" required>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                {{ cat.name }}
                            </option>
                        </Select>
                        <p v-if="form.errors.category_id" class="text-sm text-destructive">
                            {{ form.errors.category_id }}
                        </p>
                    </div>

                    <div class="space-y-2 sm:col-span-2">
                        <Label for="name">Nombre</Label>
                        <Input id="name" v-model="form.name" type="text" required autofocus />
                        <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="sku">SKU</Label>
                        <Input id="sku" v-model="form.sku" type="text" required />
                        <p v-if="form.errors.sku" class="text-sm text-destructive">{{ form.errors.sku }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="price">Precio</Label>
                        <Input id="price" v-model="form.price" type="number" step="0.01" min="0" required />
                        <p v-if="form.errors.price" class="text-sm text-destructive">{{ form.errors.price }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="stock">Stock</Label>
                        <Input id="stock" v-model="form.stock" type="number" min="0" required />
                        <p v-if="form.errors.stock" class="text-sm text-destructive">{{ form.errors.stock }}</p>
                    </div>

                    <div class="space-y-2">
                        <Label for="min_stock">Stock mínimo</Label>
                        <Input id="min_stock" v-model="form.min_stock" type="number" min="0" required />
                        <p v-if="form.errors.min_stock" class="text-sm text-destructive">
                            {{ form.errors.min_stock }}
                        </p>
                    </div>

                    <div class="space-y-2 sm:col-span-2">
                        <Label for="description">Descripción</Label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="3"
                            class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        />
                        <p v-if="form.errors.description" class="text-sm text-destructive">
                            {{ form.errors.description }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2">
                    <Button variant="outline" as-child>
                        <Link :href="route('products.index')">Cancelar</Link>
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <Loader2 v-if="form.processing" class="mr-1 animate-spin" />
                        Actualizar
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
