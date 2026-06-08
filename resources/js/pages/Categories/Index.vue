<script setup lang="ts">
import ConfirmDialog from '@/components/stockflow/ConfirmDialog.vue';
import EmptyState from '@/components/stockflow/EmptyState.vue';
import PageHeader from '@/components/stockflow/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Pagination } from '@/components/ui/pagination';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Tag, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

interface CategoryItem {
    id: number;
    name: string;
    description: string | null;
    products_count: number;
}

interface PaginatedCategories {
    data: CategoryItem[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
    total: number;
}

defineProps<{
    categories: PaginatedCategories;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tablero', href: '/dashboard' },
    { title: 'Categorías', href: '/categories' },
];

const confirmOpen = ref(false);
const processing = ref(false);
const target = ref<CategoryItem | null>(null);

const askDelete = (category: CategoryItem) => {
    target.value = category;
    confirmOpen.value = true;
};

const handleDelete = () => {
    if (!target.value) return;
    processing.value = true;
    router.delete(route('categories.destroy', target.value.id), {
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
    <Head title="Categorías" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <PageHeader
                title="Categorías"
                description="Gestiona las categorías de tus productos."
            >
                <template #actions>
                    <Button as-child>
                        <Link :href="route('categories.create')">
                            <Plus class="mr-1" />
                            Nueva categoría
                        </Link>
                    </Button>
                </template>
            </PageHeader>

            <div class="rounded-xl border border-border/60">
                <Table v-if="categories.data.length > 0">
                    <TableHeader>
                        <TableRow>
                            <TableHead>Nombre</TableHead>
                            <TableHead>Descripción</TableHead>
                            <TableHead class="text-center">Productos</TableHead>
                            <TableHead class="w-32 text-right">Acciones</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="category in categories.data" :key="category.id">
                            <TableCell class="font-medium">{{ category.name }}</TableCell>
                            <TableCell class="text-muted-foreground">
                                {{ category.description ?? '—' }}
                            </TableCell>
                            <TableCell class="text-center">
                                <Badge variant="secondary">{{ category.products_count }}</Badge>
                            </TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-1">
                                    <Button variant="ghost" size="icon" as-child>
                                        <Link :href="route('categories.edit', category.id)">
                                            <Pencil />
                                        </Link>
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="text-destructive hover:text-destructive"
                                        @click="askDelete(category)"
                                    >
                                        <Trash2 />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                <EmptyState
                    v-else
                    :icon="Tag"
                    title="Sin categorías todavía"
                    description="Crea tu primera categoría para empezar a organizar tu catálogo."
                    :action="{ label: 'Nueva categoría', href: route('categories.create') }"
                />
            </div>

            <Pagination v-if="categories.data.length > 0" :links="categories.links" />
        </div>

        <ConfirmDialog
            v-model:open="confirmOpen"
            title="Eliminar categoría"
            :description="`¿Estás seguro de eliminar «${target?.name}»? Esta acción no se puede deshacer.`"
            :processing="processing"
            @confirm="handleDelete"
        />
    </AppLayout>
</template>
