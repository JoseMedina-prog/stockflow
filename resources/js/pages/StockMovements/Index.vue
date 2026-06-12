<script setup lang="ts">
import EmptyState from '@/components/stockflow/EmptyState.vue';
import PageHeader from '@/components/stockflow/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Pagination } from '@/components/ui/pagination';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatDateTime } from '@/composables/useFormat';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeftRight, Search, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface StockMovementItem {
    id: number;
    type: string;
    type_label: string;
    type_badge: string;
    is_out: boolean;
    quantity: number;
    signed_quantity: number;
    reason: string | null;
    notes: string | null;
    occurred_at: string;
    product: { id: number; name: string; sku: string };
    user: { id: number; name: string } | null;
    reference_label: string | null;
    reference_href: string | null;
}

interface PaginatedMovements {
    data: StockMovementItem[];
    links: { url: string | null; label: string; active: boolean }[];
}

interface ProductOption {
    id: number;
    name: string;
    sku: string;
}

interface TypeOption {
    value: string;
    label: string;
}

const props = defineProps<{
    movements: PaginatedMovements;
    products: ProductOption[];
    types: TypeOption[];
    filters: {
        search: string | null;
        product_id: number | null;
        type: string | null;
        from: string | null;
        to: string | null;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tablero', href: '/dashboard' },
    { title: 'Movimientos de inventario', href: '/stock-movements' },
];

const searchInput = ref(props.filters.search ?? '');
const productId = ref<number | null>(props.filters.product_id ?? null);
const type = ref(props.filters.type ?? '');
const fromInput = ref(props.filters.from ?? '');
const toInput = ref(props.filters.to ?? '');

watch(
    () => props.filters,
    (f) => {
        searchInput.value = f.search ?? '';
        productId.value = f.product_id ?? null;
        type.value = f.type ?? '';
        fromInput.value = f.from ?? '';
        toInput.value = f.to ?? '';
    },
);

const hasFilters = () => !!(props.filters.search || props.filters.product_id || props.filters.type || props.filters.from || props.filters.to);

const applyFilters = () => {
    router.get(
        route('stock-movements.index'),
        {
            search: searchInput.value || undefined,
            product_id: productId.value || undefined,
            type: type.value || undefined,
            from: fromInput.value || undefined,
            to: toInput.value || undefined,
        },
        { preserveScroll: true, preserveState: true },
    );
};

const clearFilters = () => {
    router.get(route('stock-movements.index'), {}, { preserveScroll: true });
};
</script>

<template>
    <Head title="Movimientos de inventario" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <PageHeader title="Movimientos de inventario" description="Historial de entradas, salidas y ajustes de stock por producto." />

            <div class="flex flex-wrap items-end gap-2">
                <div class="relative min-w-[200px] flex-1">
                    <Search class="absolute left-2.5 top-2.5 size-4 text-muted-foreground" />
                    <Input v-model="searchInput" placeholder="Buscar por producto, SKU o razón..." class="pl-9" @keyup.enter="applyFilters" />
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs text-muted-foreground">Producto</label>
                    <select v-model="productId" class="h-9 rounded-md border border-input bg-background px-2 text-sm" @change="applyFilters">
                        <option :value="null">Todos</option>
                        <option v-for="p in products" :key="p.id" :value="p.id">
                            {{ p.name }}
                        </option>
                    </select>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs text-muted-foreground">Tipo</label>
                    <select v-model="type" class="h-9 rounded-md border border-input bg-background px-2 text-sm" @change="applyFilters">
                        <option value="">Todos</option>
                        <option v-for="t in types" :key="t.value" :value="t.value">
                            {{ t.label }}
                        </option>
                    </select>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs text-muted-foreground">Desde</label>
                    <Input v-model="fromInput" type="date" @change="applyFilters" />
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs text-muted-foreground">Hasta</label>
                    <Input v-model="toInput" type="date" @change="applyFilters" />
                </div>

                <Button v-if="hasFilters()" variant="ghost" @click="clearFilters">
                    <X class="mr-1" />
                    Limpiar
                </Button>
            </div>

            <div class="rounded-xl border border-border/60">
                <Table v-if="movements.data.length > 0">
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-44">Fecha</TableHead>
                            <TableHead>Producto</TableHead>
                            <TableHead class="w-28">Tipo</TableHead>
                            <TableHead class="w-24 text-right">Cantidad</TableHead>
                            <TableHead>Razón</TableHead>
                            <TableHead>Usuario</TableHead>
                            <TableHead class="w-32">Origen</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="m in movements.data" :key="m.id">
                            <TableCell class="text-xs text-muted-foreground">
                                {{ formatDateTime(m.occurred_at) }}
                            </TableCell>
                            <TableCell>
                                <div class="font-medium">{{ m.product.name }}</div>
                                <div class="text-xs text-muted-foreground">{{ m.product.sku }}</div>
                            </TableCell>
                            <TableCell>
                                <Badge :variant="m.type_badge as any">{{ m.type_label }}</Badge>
                            </TableCell>
                            <TableCell
                                class="text-right font-semibold tabular-nums"
                                :class="m.is_out ? 'text-destructive' : 'text-emerald-600 dark:text-emerald-400'"
                            >
                                {{ m.is_out ? '−' : '+' }}{{ m.quantity }}
                            </TableCell>
                            <TableCell>
                                <div>{{ m.reason ?? '—' }}</div>
                                <div v-if="m.notes" class="text-xs text-muted-foreground">
                                    {{ m.notes }}
                                </div>
                            </TableCell>
                            <TableCell class="text-muted-foreground">
                                {{ m.user?.name ?? '—' }}
                            </TableCell>
                            <TableCell>
                                <Link v-if="m.reference_href" :href="m.reference_href" class="text-xs text-primary hover:underline">
                                    {{ m.reference_label }}
                                </Link>
                                <span v-else class="text-xs text-muted-foreground">Manual</span>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                <EmptyState
                    v-else-if="!hasFilters()"
                    :icon="ArrowLeftRight"
                    title="Sin movimientos todavía"
                    description="Las ventas, compras y ajustes de stock aparecerán aquí."
                />
                <EmptyState v-else :icon="Search" title="Sin resultados" description="No encontramos movimientos con ese criterio." />
            </div>

            <Pagination v-if="movements.data.length > 0" :links="movements.links" />
        </div>
    </AppLayout>
</template>
