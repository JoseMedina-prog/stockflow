<script setup lang="ts">
import EmptyState from '@/components/stockflow/EmptyState.vue';
import PageHeader from '@/components/stockflow/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Landmark } from 'lucide-vue-next';

defineProps<{
    accounts: Array<{
        id: number;
        code: string;
        name: string;
        type: string;
        type_label: string;
        normal_balance: string;
        normal_balance_label: string;
        category: string | null;
        parent: { code: string; name: string } | null;
        is_system: boolean;
        is_active: boolean;
        description: string | null;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tablero', href: '/dashboard' },
    { title: 'Contabilidad', href: '/accounting' },
    { title: 'Catálogo de cuentas', href: '/accounting/accounts' },
];

const grouped = (accounts: any[]) => {
    const groups: Record<string, any[]> = {};
    for (const a of accounts) {
        const key = a.type;
        if (!groups[key]) groups[key] = [];
        groups[key].push(a);
    }
    return groups;
};
</script>

<template>
    <Head title="Catálogo de cuentas" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <PageHeader
                title="Catálogo de cuentas"
                description="Plan contable base. Solo lectura: las cuentas del sistema las crea el seeder."
            >
                <template #actions>
                    <Link :href="route('accounting.index')" class="text-sm text-muted-foreground hover:underline">
                        ← Volver al resumen
                    </Link>
                </template>
            </PageHeader>

            <EmptyState
                v-if="accounts.length === 0"
                :icon="Landmark"
                title="Sin cuentas"
                description="Ejecuta el seeder de plan contable para inicializar el catálogo."
            />

            <div v-else class="space-y-4">
                <Card v-for="(group, type) in grouped(accounts)" :key="type">
                    <CardContent class="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-24">Código</TableHead>
                                    <TableHead>Cuenta</TableHead>
                                    <TableHead>Padre</TableHead>
                                    <TableHead class="text-right">Naturaleza</TableHead>
                                    <TableHead>Estado</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="a in group" :key="a.id">
                                    <TableCell class="font-mono text-xs">{{ a.code }}</TableCell>
                                    <TableCell>
                                        <div class="font-medium">{{ a.name }}</div>
                                        <div v-if="a.description" class="text-xs text-muted-foreground">{{ a.description }}</div>
                                    </TableCell>
                                    <TableCell class="text-xs text-muted-foreground">
                                        <span v-if="a.parent">{{ a.parent.code }} — {{ a.parent.name }}</span>
                                        <span v-else>—</span>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <Badge variant="secondary">{{ a.normal_balance_label }}</Badge>
                                    </TableCell>
                                    <TableCell>
                                        <Badge v-if="a.is_system" variant="info">Sistema</Badge>
                                        <Badge v-if="!a.is_active" variant="destructive">Inactiva</Badge>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
