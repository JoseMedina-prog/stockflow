<script setup lang="ts">
import ConfirmDialog from '@/components/stockflow/ConfirmDialog.vue';
import EmptyState from '@/components/stockflow/EmptyState.vue';
import PageHeader from '@/components/stockflow/PageHeader.vue';
import StockBadge from '@/components/stockflow/StockBadge.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Pagination } from '@/components/ui/pagination';
import { Select } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatCurrency } from '@/composables/useFormat';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Package, Pencil, Plus, Search, Trash2, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface ProductItem {
    id: number;
    name: string;
    sku: string;
    price: number;
    stock: number;
    min_stock: number;
    is_low_stock: boolean;
    category: { id: number; name: string } | null;
}

interface PaginatedProducts {
    data: ProductItem[];
    links: { url: string | null; label: string; active: boolean }[];
}

const props = defineProps<{
    products: PaginatedProducts;
    categories: { id: number; name: string }[];
    filters: { search: string; category_id: number | null };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tablero', href: '/dashboard' },
    { title: 'Productos', href: '/products' },
];

const searchInput = ref(props.filters.search ?? '');
const categoryId = ref<number | null>(props.filters.category_id ?? null);

watch(
    () => props.filters.search,
    (v) => {
        searchInput.value = v ?? '';
    },
);

watch(
    () => props.filters.category_id,
    (v) => {
        categoryId.value = v ?? null;
    },
);

const applyFilters = () => {
    router.get(
        route('products.index'),
        {
            search: searchInput.value || undefined,
            category_id: categoryId.value || undefined,
        },
        { preserveScroll: true, preserveState: true },
    );
};

const clearFilters = () => {
    router.get(route('products.index'), {}, { preserveScroll: true });
};

const confirmOpen = ref(false);
const processing = ref(false);
const target = ref<ProductItem | null>(null);

const askDelete = (product: ProductItem) => {
    target.value = product;
    confirmOpen.value = true;
};

const handleDelete = () => {
    if (!target.value) return;
    processing.value = true;
    router.delete(route('products.destroy', target.value.id), {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
            confirmOpen.value = false;
            target.value = null;
        },
    });
};
</script>

<template>
    <Head title="Productos" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <PageHeader title="Productos" description="Gestiona el catálogo de productos y su inventario.">
                <template #actions>
                    <Button as-child>
                        <Link :href="route('products.create')">
                            <Plus class="mr-1" />
                            Nuevo producto
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <div class="relative flex-1">
                    <Search class="absolute left-2.5 top-2.5 size-4 text-muted-foreground" />
                    <Input v-model="searchInput" placeholder="Buscar por nombre o SKU..." class="pl-9" @keyup.enter="applyFilters" />
                </div>
                <Select
                    :model-value="categoryId"
                    class="sm:w-56"
                    @update:model-value="
                        (v) => {
                            categoryId = v ? Number(v) : null;
                            applyFilters();
                        }
                    "
                >
                    <option value="">Todas las categorías</option>
                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                        {{ cat.name }}
                    </option>
                </Select>
                <Button v-if="filters.search || filters.category_id" variant="ghost" @click="clearFilters">
                    <X class="mr-1" />
                    Limpiar
                </Button>
            </div>

            <div class="rounded-xl border border-border/60">
                <Table v-if="products.data.length > 0">
                    <TableHeader>
                        <TableRow>
                            <TableHead>Producto</TableHead>
                            <TableHead>Categoría</TableHead>
                            <TableHead class="text-right">Precio</TableHead>
                            <TableHead>Stock</TableHead>
                            <TableHead class="w-32 text-right">Acciones</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="product in products.data" :key="product.id">
                            <TableCell>
                                <div class="font-medium">{{ product.name }}</div>
                                <div class="font-mono text-xs text-muted-foreground">{{ product.sku }}</div>
                            </TableCell>
                            <TableCell>
                                <Badge v-if="product.category" variant="outline">
                                    {{ product.category.name }}
                                </Badge>
                                <span v-else class="text-muted-foreground">—</span>
                            </TableCell>
                            <TableCell class="text-right tabular-nums">
                                {{ formatCurrency(product.price) }}
                            </TableCell>
                            <TableCell>
                                <StockBadge :stock="product.stock" :min-stock="product.min_stock" />
                            </TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-1">
                                    <Button variant="ghost" size="icon" as-child>
                                        <Link :href="route('products.edit', product.id)">
                                            <Pencil />
                                        </Link>
                                    </Button>
                                    <Button variant="ghost" size="icon" class="text-destructive hover:text-destructive" @click="askDelete(product)">
                                        <Trash2 />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                <EmptyState
                    v-else-if="!filters.search && !filters.category_id"
                    :icon="Package"
                    title="Sin productos todavía"
                    description="Agrega tu primer producto para empezar a vender."
                    :action="{ label: 'Nuevo producto', href: route('products.create') }"
                />
                <EmptyState v-else :icon="Search" title="Sin resultados" description="No encontramos productos con los filtros aplicados." />
            </div>

            <Pagination v-if="products.data.length > 0" :links="products.links" />
        </div>

        <ConfirmDialog
            v-model:open="confirmOpen"
            title="Eliminar producto"
            :description="`¿Estás seguro de eliminar «${target?.name}»? Esta acción no se puede deshacer.`"
            :processing="processing"
            @confirm="handleDelete"
        />
    </AppLayout>
</template>
