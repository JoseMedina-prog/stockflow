<script setup lang="ts">
import EmptyState from '@/components/stockflow/EmptyState.vue';
import PageHeader from '@/components/stockflow/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Pagination } from '@/components/ui/pagination';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { formatCurrency, formatDateTime } from '@/composables/useFormat';
import { Eye, Plus, Search, ShoppingCart, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface SaleItem {
    id: number;
    sale_date: string;
    total: number;
    items_count: number;
    customer: { id: number; name: string } | null;
    user: { id: number; name: string };
}

interface PaginatedSales {
    data: SaleItem[];
    links: { url: string | null; label: string; active: boolean }[];
}

const props = defineProps<{
    sales: PaginatedSales;
    filters: { search: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tablero', href: '/dashboard' },
    { title: 'Ventas', href: '/sales' },
];

const searchInput = ref(props.filters.search ?? '');
watch(
    () => props.filters.search,
    (v) => {
        searchInput.value = v ?? '';
    },
);

const applyFilters = () => {
    router.get(
        route('sales.index'),
        { search: searchInput.value || undefined },
        { preserveScroll: true, preserveState: true },
    );
};

const clearFilters = () => {
    router.get(route('sales.index'), {}, { preserveScroll: true });
};
</script>

<template>
    <Head title="Ventas" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <PageHeader
                title="Ventas"
                description="Registra y consulta las ventas de la tienda."
            >
                <template #actions>
                    <Button as-child>
                        <Link :href="route('sales.create')">
                            <Plus class="mr-1" />
                            Nueva venta
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <div class="flex items-center gap-2">
                <div class="relative flex-1">
                    <Search class="absolute left-2.5 top-2.5 size-4 text-muted-foreground" />
                    <Input
                        v-model="searchInput"
                        placeholder="Buscar por # de venta o nombre de cliente..."
                        class="pl-9"
                        @keyup.enter="applyFilters"
                    />
                </div>
                <Button v-if="filters.search" variant="ghost" @click="clearFilters">
                    <X class="mr-1" />
                    Limpiar
                </Button>
            </div>

            <div class="rounded-xl border border-border/60">
                <Table v-if="sales.data.length > 0">
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-20">Folio</TableHead>
                            <TableHead>Fecha</TableHead>
                            <TableHead>Cliente</TableHead>
                            <TableHead>Vendedor</TableHead>
                            <TableHead class="text-center">Items</TableHead>
                            <TableHead class="text-right">Total</TableHead>
                            <TableHead class="w-20 text-right">Ver</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="sale in sales.data" :key="sale.id">
                            <TableCell class="font-mono text-xs">#{{ sale.id }}</TableCell>
                            <TableCell>{{ formatDateTime(sale.sale_date) }}</TableCell>
                            <TableCell>
                                {{ sale.customer?.name ?? 'Consumidor final' }}
                            </TableCell>
                            <TableCell class="text-muted-foreground">{{ sale.user.name }}</TableCell>
                            <TableCell class="text-center">
                                <Badge variant="secondary">{{ sale.items_count }}</Badge>
                            </TableCell>
                            <TableCell class="text-right font-semibold tabular-nums">
                                {{ formatCurrency(sale.total) }}
                            </TableCell>
                            <TableCell class="text-right">
                                <Button variant="ghost" size="icon" as-child>
                                    <Link :href="route('sales.show', sale.id)">
                                        <Eye />
                                    </Link>
                                </Button>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                <EmptyState
                    v-else-if="!filters.search"
                    :icon="ShoppingCart"
                    title="Sin ventas todavía"
                    description="Registra tu primera venta para empezar a ver el historial aquí."
                    :action="{ label: 'Nueva venta', href: route('sales.create') }"
                />
                <EmptyState
                    v-else
                    :icon="Search"
                    title="Sin resultados"
                    description="No encontramos ventas con ese criterio."
                />
            </div>

            <Pagination v-if="sales.data.length > 0" :links="sales.links" />
        </div>
    </AppLayout>
</template>
