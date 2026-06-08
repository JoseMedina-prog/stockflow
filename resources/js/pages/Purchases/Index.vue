<script setup lang="ts">
import ConfirmDialog from '@/components/stockflow/ConfirmDialog.vue';
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
import { formatCurrency } from '@/composables/useFormat';
import { Eye, Pencil, Plus, Search, ShoppingBag, Trash2, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface PurchaseItem {
    id: number;
    folio: string;
    purchase_date: string;
    total: number;
    status: string;
    status_label: string;
    status_badge: string;
    items_count: number;
    supplier: { id: number; name: string };
    user: { id: number; name: string };
}

interface PaginatedPurchases {
    data: PurchaseItem[];
    links: { url: string | null; label: string; active: boolean }[];
}

interface SupplierOption {
    id: number;
    name: string;
}

interface StatusOption {
    value: string;
    label: string;
}

const props = defineProps<{
    purchases: PaginatedPurchases;
    suppliers: SupplierOption[];
    statuses: StatusOption[];
    filters: {
        search: string;
        supplier_id: number | null;
        status: string | null;
        from: string | null;
        to: string | null;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tablero', href: '/dashboard' },
    { title: 'Compras', href: '/purchases' },
];

const searchInput = ref(props.filters.search ?? '');
const supplierId = ref<number | null>(props.filters.supplier_id ?? null);
const status = ref(props.filters.status ?? '');
const fromInput = ref(props.filters.from ?? '');
const toInput = ref(props.filters.to ?? '');

watch(
    () => props.filters,
    (f) => {
        searchInput.value = f.search ?? '';
        supplierId.value = f.supplier_id ?? null;
        status.value = f.status ?? '';
        fromInput.value = f.from ?? '';
        toInput.value = f.to ?? '';
    },
);

const hasFilters = () =>
    !!(props.filters.search || props.filters.supplier_id || props.filters.status || props.filters.from || props.filters.to);

const applyFilters = () => {
    router.get(
        route('purchases.index'),
        {
            search: searchInput.value || undefined,
            supplier_id: supplierId.value || undefined,
            status: status.value || undefined,
            from: fromInput.value || undefined,
            to: toInput.value || undefined,
        },
        { preserveScroll: true, preserveState: true },
    );
};

const clearFilters = () => {
    router.get(route('purchases.index'), {}, { preserveScroll: true });
};

const confirmOpen = ref(false);
const processing = ref(false);
const target = ref<PurchaseItem | null>(null);

const askDelete = (purchase: PurchaseItem) => {
    target.value = purchase;
    confirmOpen.value = true;
};

const handleDelete = () => {
    if (!target.value) return;
    processing.value = true;
    router.delete(route('purchases.destroy', target.value.id), {
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
    <Head title="Compras" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <PageHeader
                title="Compras"
                description="Registra y consulta las compras a proveedores."
            >
                <template #actions>
                    <Button as-child>
                        <Link :href="route('purchases.create')">
                            <Plus class="mr-1" />
                            Nueva compra
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <div class="flex flex-wrap items-end gap-2">
                <div class="relative min-w-[200px] flex-1">
                    <Search class="absolute left-2.5 top-2.5 size-4 text-muted-foreground" />
                    <Input
                        v-model="searchInput"
                        placeholder="Buscar por folio o proveedor..."
                        class="pl-9"
                        @keyup.enter="applyFilters"
                    />
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs text-muted-foreground">Proveedor</label>
                    <select
                        v-model="supplierId"
                        class="h-9 rounded-md border border-input bg-background px-2 text-sm"
                        @change="applyFilters"
                    >
                        <option :value="null">Todos</option>
                        <option v-for="s in suppliers" :key="s.id" :value="s.id">
                            {{ s.name }}
                        </option>
                    </select>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs text-muted-foreground">Estado</label>
                    <select
                        v-model="status"
                        class="h-9 rounded-md border border-input bg-background px-2 text-sm"
                        @change="applyFilters"
                    >
                        <option value="">Todos</option>
                        <option v-for="st in statuses" :key="st.value" :value="st.value">
                            {{ st.label }}
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
                <Table v-if="purchases.data.length > 0">
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-32">Folio</TableHead>
                            <TableHead>Proveedor</TableHead>
                            <TableHead class="w-32">Fecha</TableHead>
                            <TableHead class="w-32">Estado</TableHead>
                            <TableHead class="text-center">Items</TableHead>
                            <TableHead class="w-32 text-right">Total</TableHead>
                            <TableHead class="w-32 text-right">Acciones</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="purchase in purchases.data" :key="purchase.id">
                            <TableCell class="font-mono text-xs">{{ purchase.folio }}</TableCell>
                            <TableCell>{{ purchase.supplier.name }}</TableCell>
                            <TableCell class="text-sm">{{ purchase.purchase_date }}</TableCell>
                            <TableCell>
                                <Badge :variant="purchase.status_badge as any">{{ purchase.status_label }}</Badge>
                            </TableCell>
                            <TableCell class="text-center">
                                <Badge variant="secondary">{{ purchase.items_count }}</Badge>
                            </TableCell>
                            <TableCell class="text-right font-semibold tabular-nums">
                                {{ formatCurrency(purchase.total) }}
                            </TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-1">
                                    <Button variant="ghost" size="icon" as-child>
                                        <Link :href="route('purchases.show', purchase.id)">
                                            <Eye />
                                        </Link>
                                    </Button>
                                    <Button
                                        v-if="purchase.status === 'pending'"
                                        variant="ghost"
                                        size="icon"
                                        as-child
                                    >
                                        <Link :href="route('purchases.edit', purchase.id)">
                                            <Pencil />
                                        </Link>
                                    </Button>
                                    <Button
                                        v-if="purchase.status === 'pending'"
                                        variant="ghost"
                                        size="icon"
                                        class="text-destructive hover:text-destructive"
                                        @click="askDelete(purchase)"
                                    >
                                        <Trash2 />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                <EmptyState
                    v-else-if="!hasFilters()"
                    :icon="ShoppingBag"
                    title="Sin compras todavía"
                    description="Registra tu primera compra para empezar a gestionar tu inventario."
                    :action="{ label: 'Nueva compra', href: route('purchases.create') }"
                />
                <EmptyState
                    v-else
                    :icon="Search"
                    title="Sin resultados"
                    description="No encontramos compras con ese criterio."
                />
            </div>

            <Pagination v-if="purchases.data.length > 0" :links="purchases.links" />
        </div>

        <ConfirmDialog
            v-model:open="confirmOpen"
            title="Eliminar compra"
            :description="`¿Estás seguro de eliminar la compra «${target?.folio}»? Esta acción no se puede deshacer.`"
            :processing="processing"
            @confirm="handleDelete"
        />
    </AppLayout>
</template>
