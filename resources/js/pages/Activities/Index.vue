<script setup lang="ts">
import ConfirmDialog from '@/components/stockflow/ConfirmDialog.vue';
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
import { Activity, Search, Trash2, X } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface ActivityItem {
    id: number;
    type: string;
    type_label: string;
    type_badge: string;
    description: string | null;
    outcome: string | null;
    occurred_at: string;
    duration_minutes: number | null;
    user: { id: number; name: string } | null;
    subject_type: string;
    subject_href: string | null;
    subject_label: string;
}

interface PaginatedActivities {
    data: ActivityItem[];
    links: { url: string | null; label: string; active: boolean }[];
}

interface TypeOption {
    value: string;
    label: string;
}

const props = defineProps<{
    activities: PaginatedActivities;
    types: TypeOption[];
    filters: {
        search: string;
        type: string | null;
        from: string | null;
        to: string | null;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tablero', href: '/dashboard' },
    { title: 'Actividades', href: '/activities' },
];

const searchInput = ref(props.filters.search ?? '');
const type = ref(props.filters.type ?? '');
const fromInput = ref(props.filters.from ?? '');
const toInput = ref(props.filters.to ?? '');

watch(
    () => props.filters,
    (f) => {
        searchInput.value = f.search ?? '';
        type.value = f.type ?? '';
        fromInput.value = f.from ?? '';
        toInput.value = f.to ?? '';
    },
);

const hasFilters = () => !!(props.filters.search || props.filters.type || props.filters.from || props.filters.to);

const applyFilters = () => {
    router.get(
        route('activities.index'),
        {
            search: searchInput.value || undefined,
            type: type.value || undefined,
            from: fromInput.value || undefined,
            to: toInput.value || undefined,
        },
        { preserveScroll: true, preserveState: true },
    );
};

const clearFilters = () => {
    router.get(route('activities.index'), {}, { preserveScroll: true });
};

const confirmOpen = ref(false);
const processing = ref(false);
const target = ref<ActivityItem | null>(null);

const askDelete = (activity: ActivityItem) => {
    target.value = activity;
    confirmOpen.value = true;
};

const handleDelete = () => {
    if (!target.value) return;
    processing.value = true;
    router.delete(route('activities.destroy', target.value.id), {
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
    <Head title="Actividades" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <PageHeader title="Actividades" description="Registro global de llamadas, correos, reuniones, notas y mensajes." />

            <div class="flex flex-wrap items-end gap-2">
                <div class="relative min-w-[200px] flex-1">
                    <Search class="absolute left-2.5 top-2.5 size-4 text-muted-foreground" />
                    <Input v-model="searchInput" placeholder="Buscar en descripción o resultado..." class="pl-9" @keyup.enter="applyFilters" />
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-xs text-muted-foreground">Tipo</label>
                    <select v-model="type" class="h-9 rounded-md border border-input bg-background px-2 text-sm" @change="applyFilters">
                        <option value="">Todos</option>
                        <option v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</option>
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
                <Table v-if="activities.data.length > 0">
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-32">Tipo</TableHead>
                            <TableHead>Descripción</TableHead>
                            <TableHead>Relacionada con</TableHead>
                            <TableHead>Usuario</TableHead>
                            <TableHead class="w-44">Cuándo</TableHead>
                            <TableHead class="w-16 text-right"></TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="a in activities.data" :key="a.id">
                            <TableCell>
                                <Badge :variant="a.type_badge as any">{{ a.type_label }}</Badge>
                            </TableCell>
                            <TableCell>
                                <div class="font-medium">{{ a.description ?? '—' }}</div>
                                <div v-if="a.outcome" class="mt-0.5 text-xs text-emerald-600 dark:text-emerald-400">→ {{ a.outcome }}</div>
                            </TableCell>
                            <TableCell>
                                <Link v-if="a.subject_href" :href="a.subject_href" class="text-xs hover:underline">
                                    <span class="text-muted-foreground">{{ a.subject_type }} ·</span>
                                    <span class="font-medium">{{ a.subject_label }}</span>
                                </Link>
                                <span v-else class="text-xs text-muted-foreground">—</span>
                            </TableCell>
                            <TableCell class="text-muted-foreground">{{ a.user?.name ?? '—' }}</TableCell>
                            <TableCell class="text-xs text-muted-foreground">{{ formatDateTime(a.occurred_at) }}</TableCell>
                            <TableCell class="text-right">
                                <Button variant="ghost" size="icon" class="text-destructive hover:text-destructive" @click="askDelete(a)">
                                    <Trash2 class="size-4" />
                                </Button>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
                <EmptyState
                    v-else-if="!hasFilters()"
                    :icon="Activity"
                    title="Sin actividades"
                    description="Las llamadas, correos y reuniones registradas aparecerán aquí."
                />
                <EmptyState v-else :icon="Search" title="Sin resultados" description="No encontramos actividades con ese criterio." />
            </div>

            <Pagination v-if="activities.data.length > 0" :links="activities.links" />
        </div>

        <ConfirmDialog
            v-model:open="confirmOpen"
            title="Eliminar actividad"
            :description="`¿Estás seguro de eliminar esta actividad? Esta acción no se puede deshacer.`"
            :processing="processing"
            @confirm="handleDelete"
        />
    </AppLayout>
</template>
