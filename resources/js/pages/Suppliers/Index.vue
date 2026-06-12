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
import { Mail, Pencil, Phone, Plus, Search, Trash2, Truck, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface SupplierItem {
    id: number;
    name: string;
    contact_name: string | null;
    email: string | null;
    phone: string | null;
    tax_id: string | null;
    is_active: boolean;
}

interface PaginatedSuppliers {
    data: SupplierItem[];
    links: { url: string | null; label: string; active: boolean }[];
}

const props = defineProps<{
    suppliers: PaginatedSuppliers;
    filters: { search: string; status: string | null };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tablero', href: '/dashboard' },
    { title: 'Proveedores', href: '/suppliers' },
];

const searchInput = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');

watch(
    () => props.filters,
    (f) => {
        searchInput.value = f.search ?? '';
        status.value = f.status ?? '';
    },
);

const hasFilters = () => !!(props.filters.search || props.filters.status);

const applyFilters = () => {
    router.get(
        route('suppliers.index'),
        {
            search: searchInput.value || undefined,
            status: status.value || undefined,
        },
        { preserveScroll: true, preserveState: true },
    );
};

const clearFilters = () => {
    router.get(route('suppliers.index'), {}, { preserveScroll: true });
};

const confirmOpen = ref(false);
const processing = ref(false);
const target = ref<SupplierItem | null>(null);

const askDelete = (supplier: SupplierItem) => {
    target.value = supplier;
    confirmOpen.value = true;
};

const handleDelete = () => {
    if (!target.value) return;
    processing.value = true;
    router.delete(route('suppliers.destroy', target.value.id), {
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
    <Head title="Proveedores" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <PageHeader title="Proveedores" description="Gestiona los proveedores de la tienda.">
                <template #actions>
                    <Button as-child>
                        <Link :href="route('suppliers.create')">
                            <Plus class="mr-1" />
                            Nuevo proveedor
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <div class="flex flex-wrap items-end gap-2">
                <div class="relative min-w-[220px] flex-1">
                    <Search class="absolute left-2.5 top-2.5 size-4 text-muted-foreground" />
                    <Input
                        v-model="searchInput"
                        placeholder="Buscar por nombre, contacto, RFC, email o teléfono..."
                        class="pl-9"
                        @keyup.enter="applyFilters"
                    />
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs text-muted-foreground">Estado</label>
                    <select v-model="status" class="h-9 rounded-md border border-input bg-background px-2 text-sm" @change="applyFilters">
                        <option value="">Todos</option>
                        <option value="active">Activos</option>
                        <option value="inactive">Inactivos</option>
                    </select>
                </div>

                <Button v-if="hasFilters()" variant="ghost" @click="clearFilters">
                    <X class="mr-1" />
                    Limpiar
                </Button>
            </div>

            <div class="rounded-xl border border-border/60">
                <Table v-if="suppliers.data.length > 0">
                    <TableHeader>
                        <TableRow>
                            <TableHead>Nombre</TableHead>
                            <TableHead>Contacto</TableHead>
                            <TableHead>RFC</TableHead>
                            <TableHead class="text-center">Estado</TableHead>
                            <TableHead class="w-32 text-right">Acciones</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="supplier in suppliers.data" :key="supplier.id">
                            <TableCell>
                                <div class="font-medium">{{ supplier.name }}</div>
                                <div
                                    v-if="supplier.email || supplier.phone"
                                    class="mt-0.5 flex flex-wrap items-center gap-3 text-xs text-muted-foreground"
                                >
                                    <span v-if="supplier.email" class="flex items-center gap-1">
                                        <Mail class="size-3" />
                                        {{ supplier.email }}
                                    </span>
                                    <span v-if="supplier.phone" class="flex items-center gap-1">
                                        <Phone class="size-3" />
                                        {{ supplier.phone }}
                                    </span>
                                </div>
                            </TableCell>
                            <TableCell class="text-muted-foreground">
                                {{ supplier.contact_name ?? '—' }}
                            </TableCell>
                            <TableCell class="font-mono text-xs">
                                {{ supplier.tax_id ?? '—' }}
                            </TableCell>
                            <TableCell class="text-center">
                                <Badge :variant="supplier.is_active ? 'success' : 'secondary'">
                                    {{ supplier.is_active ? 'Activo' : 'Inactivo' }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-1">
                                    <Button variant="ghost" size="icon" as-child>
                                        <Link :href="route('suppliers.edit', supplier.id)">
                                            <Pencil />
                                        </Link>
                                    </Button>
                                    <Button variant="ghost" size="icon" class="text-destructive hover:text-destructive" @click="askDelete(supplier)">
                                        <Trash2 />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                <EmptyState
                    v-else-if="!hasFilters()"
                    :icon="Truck"
                    title="Sin proveedores todavía"
                    description="Registra tu primer proveedor para empezar a gestionar compras."
                    :action="{ label: 'Nuevo proveedor', href: route('suppliers.create') }"
                />
                <EmptyState v-else :icon="Search" title="Sin resultados" description="No encontramos proveedores con ese criterio." />
            </div>

            <Pagination v-if="suppliers.data.length > 0" :links="suppliers.links" />
        </div>

        <ConfirmDialog
            v-model:open="confirmOpen"
            title="Eliminar proveedor"
            :description="`¿Estás seguro de eliminar a «${target?.name}»? Esta acción no se puede deshacer.`"
            :processing="processing"
            @confirm="handleDelete"
        />
    </AppLayout>
</template>
