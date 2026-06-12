<script setup lang="ts">
import ConfirmDialog from '@/components/stockflow/ConfirmDialog.vue';
import EmptyState from '@/components/stockflow/EmptyState.vue';
import PageHeader from '@/components/stockflow/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Pagination } from '@/components/ui/pagination';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatCurrency } from '@/composables/useFormat';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Mail, Pencil, Phone, Plus, Search, Trash2, UserPlus, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface CustomerItem {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    address: string | null;
    sales_count: number;
    total_spent: number;
}

interface PaginatedCustomers {
    data: CustomerItem[];
    links: { url: string | null; label: string; active: boolean }[];
}

const props = defineProps<{
    customers: PaginatedCustomers;
    filters: { search: string };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tablero', href: '/dashboard' },
    { title: 'Clientes', href: '/customers' },
];

const searchInput = ref(props.filters.search ?? '');
watch(
    () => props.filters.search,
    (v) => {
        searchInput.value = v ?? '';
    },
);

const applyFilters = () => {
    router.get(route('customers.index'), { search: searchInput.value || undefined }, { preserveScroll: true, preserveState: true });
};

const clearFilters = () => {
    router.get(route('customers.index'), {}, { preserveScroll: true });
};

const confirmOpen = ref(false);
const processing = ref(false);
const target = ref<CustomerItem | null>(null);

const askDelete = (customer: CustomerItem) => {
    target.value = customer;
    confirmOpen.value = true;
};

const handleDelete = () => {
    if (!target.value) return;
    processing.value = true;
    router.delete(route('customers.destroy', target.value.id), {
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
    <Head title="Clientes" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <PageHeader title="Clientes" description="Gestiona los clientes de la tienda.">
                <template #actions>
                    <Button as-child>
                        <Link :href="route('customers.create')">
                            <Plus class="mr-1" />
                            Nuevo cliente
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <div class="flex items-center gap-2">
                <div class="relative flex-1">
                    <Search class="absolute left-2.5 top-2.5 size-4 text-muted-foreground" />
                    <Input v-model="searchInput" placeholder="Buscar por nombre, email o teléfono..." class="pl-9" @keyup.enter="applyFilters" />
                </div>
                <Button v-if="filters.search" variant="ghost" @click="clearFilters">
                    <X class="mr-1" />
                    Limpiar
                </Button>
            </div>

            <div class="rounded-xl border border-border/60">
                <Table v-if="customers.data.length > 0">
                    <TableHeader>
                        <TableRow>
                            <TableHead>Nombre</TableHead>
                            <TableHead>Contacto</TableHead>
                            <TableHead>Dirección</TableHead>
                            <TableHead class="text-center">Ventas</TableHead>
                            <TableHead class="text-right">Total</TableHead>
                            <TableHead class="w-40 text-right">Acciones</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="customer in customers.data" :key="customer.id">
                            <TableCell class="font-medium">
                                <Link :href="route('customers.index')" class="hover:underline">
                                    {{ customer.name }}
                                </Link>
                            </TableCell>
                            <TableCell>
                                <div v-if="customer.email" class="flex items-center gap-1.5 text-sm">
                                    <Mail class="size-3.5 text-muted-foreground" />
                                    {{ customer.email }}
                                </div>
                                <div v-if="customer.phone" class="flex items-center gap-1.5 text-sm text-muted-foreground">
                                    <Phone class="size-3.5" />
                                    {{ customer.phone }}
                                </div>
                                <span v-if="!customer.email && !customer.phone" class="text-muted-foreground">—</span>
                            </TableCell>
                            <TableCell class="max-w-xs truncate text-muted-foreground">
                                {{ customer.address ?? '—' }}
                            </TableCell>
                            <TableCell class="text-center">
                                <Badge variant="secondary">{{ customer.sales_count }}</Badge>
                            </TableCell>
                            <TableCell class="text-right text-sm tabular-nums">
                                {{ formatCurrency(customer.total_spent) }}
                            </TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-1">
                                    <Button variant="ghost" size="icon" as-child>
                                        <Link :href="route('customers.edit', customer.id)">
                                            <Pencil />
                                        </Link>
                                    </Button>
                                    <Button variant="ghost" size="icon" class="text-destructive hover:text-destructive" @click="askDelete(customer)">
                                        <Trash2 />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                <EmptyState
                    v-else-if="!filters.search"
                    :icon="UserPlus"
                    title="Sin clientes todavía"
                    description="Registra tu primer cliente para empezar a vender."
                    :action="{ label: 'Nuevo cliente', href: route('customers.create') }"
                />
                <EmptyState v-else :icon="Search" title="Sin resultados" description="No encontramos clientes con ese criterio." />
            </div>

            <Pagination v-if="customers.data.length > 0" :links="customers.links" />
        </div>

        <ConfirmDialog
            v-model:open="confirmOpen"
            title="Eliminar cliente"
            :description="`¿Estás seguro de eliminar a «${target?.name}»? Esta acción no se puede deshacer.`"
            :processing="processing"
            @confirm="handleDelete"
        />
    </AppLayout>
</template>
