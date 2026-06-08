<script setup lang="ts">
import EmptyState from '@/components/stockflow/EmptyState.vue';
import StockBadge from '@/components/stockflow/StockBadge.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { formatCurrency, formatDateTime } from '@/composables/useFormat';
import { BarChart3, DollarSign, Package, ShoppingCart, TrendingUp } from 'lucide-vue-next';

defineProps<{
    topProducts: Array<{
        product_id: number;
        name: string;
        sku: string;
        category: string | null;
        total_quantity: number;
        total_revenue: number;
    }>;
    recentSales: Array<{
        id: number;
        sale_date: string;
        total: number;
        items_count: number;
        customer: string | null;
        user: string;
    }>;
    inventory: Array<{
        id: number;
        name: string;
        sku: string;
        category: string | null;
        price: number;
        stock: number;
        min_stock: number;
        is_low_stock: boolean;
        value: number;
    }>;
    summary: {
        inventory_value: number;
        inventory_count: number;
        low_stock_count: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tablero', href: '/dashboard' },
    { title: 'Reportes', href: '/reports' },
];
</script>

<template>
    <Head title="Reportes" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Reportes</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Métricas agregadas del negocio.
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <Card>
                    <CardContent class="flex items-center gap-3 p-5">
                        <div class="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                            <DollarSign class="size-5" />
                        </div>
                        <div>
                            <p class="text-sm text-muted-foreground">Valor del inventario</p>
                            <p class="text-2xl font-semibold tabular-nums">
                                {{ formatCurrency(summary.inventory_value) }}
                            </p>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="flex items-center gap-3 p-5">
                        <div class="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                            <Package class="size-5" />
                        </div>
                        <div>
                            <p class="text-sm text-muted-foreground">Productos en catálogo</p>
                            <p class="text-2xl font-semibold tabular-nums">{{ summary.inventory_count }}</p>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent class="flex items-center gap-3 p-5">
                        <div
                            class="flex size-10 items-center justify-center rounded-lg"
                            :class="summary.low_stock_count > 0 ? 'bg-destructive/10 text-destructive' : 'bg-emerald-500/10 text-emerald-600'"
                        >
                            <TrendingUp class="size-5" />
                        </div>
                        <div>
                            <p class="text-sm text-muted-foreground">Con stock bajo</p>
                            <p class="text-2xl font-semibold tabular-nums">{{ summary.low_stock_count }}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <BarChart3 class="size-4" />
                            Productos más vendidos
                        </CardTitle>
                        <CardDescription>Top 10 por unidades vendidas.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table v-if="topProducts.length > 0">
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-8">#</TableHead>
                                    <TableHead>Producto</TableHead>
                                    <TableHead class="text-right">Unidades</TableHead>
                                    <TableHead class="text-right">Ingresos</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="(p, index) in topProducts" :key="p.product_id">
                                    <TableCell class="text-muted-foreground">{{ index + 1 }}</TableCell>
                                    <TableCell>
                                        <div class="font-medium">{{ p.name }}</div>
                                        <div class="font-mono text-xs text-muted-foreground">
                                            {{ p.sku }}
                                            <span v-if="p.category"> · {{ p.category }}</span>
                                        </div>
                                    </TableCell>
                                    <TableCell class="text-right tabular-nums">{{ p.total_quantity }}</TableCell>
                                    <TableCell class="text-right font-semibold tabular-nums">
                                        {{ formatCurrency(p.total_revenue) }}
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                        <EmptyState
                            v-else
                            :icon="ShoppingCart"
                            title="Sin ventas registradas"
                            description="Cuando registres ventas, el top de productos aparecerá aquí."
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Ventas recientes</CardTitle>
                        <CardDescription>Últimas 10 ventas registradas.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table v-if="recentSales.length > 0">
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-16">Folio</TableHead>
                                    <TableHead>Fecha</TableHead>
                                    <TableHead>Cliente</TableHead>
                                    <TableHead class="text-right">Total</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="sale in recentSales" :key="sale.id">
                                    <TableCell class="font-mono text-xs">
                                        <Link :href="route('sales.show', sale.id)" class="hover:underline">
                                            #{{ sale.id }}
                                        </Link>
                                    </TableCell>
                                    <TableCell class="text-sm">{{ formatDateTime(sale.sale_date) }}</TableCell>
                                    <TableCell>{{ sale.customer ?? 'Consumidor final' }}</TableCell>
                                    <TableCell class="text-right font-semibold tabular-nums">
                                        {{ formatCurrency(sale.total) }}
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                        <EmptyState
                            v-else
                            :icon="ShoppingCart"
                            title="Sin ventas registradas"
                            description="Las últimas ventas aparecerán aquí conforme las vayas registrando."
                        />
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Inventario actual</CardTitle>
                    <CardDescription>
                        {{ summary.inventory_count }} productos —
                        valor total: <span class="font-semibold text-foreground">{{ formatCurrency(summary.inventory_value) }}</span>
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Table v-if="inventory.length > 0">
                        <TableHeader>
                            <TableRow>
                                <TableHead>Producto</TableHead>
                                <TableHead>Categoría</TableHead>
                                <TableHead class="text-right">Precio</TableHead>
                                <TableHead>Stock</TableHead>
                                <TableHead class="text-right">Valor</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="p in inventory" :key="p.id">
                                <TableCell>
                                    <div class="font-medium">{{ p.name }}</div>
                                    <div class="font-mono text-xs text-muted-foreground">{{ p.sku }}</div>
                                </TableCell>
                                <TableCell>
                                    <Badge v-if="p.category" variant="outline">{{ p.category }}</Badge>
                                    <span v-else class="text-muted-foreground">—</span>
                                </TableCell>
                                <TableCell class="text-right tabular-nums">{{ formatCurrency(p.price) }}</TableCell>
                                <TableCell>
                                    <StockBadge :stock="p.stock" :min-stock="p.min_stock" />
                                </TableCell>
                                <TableCell class="text-right font-semibold tabular-nums">
                                    {{ formatCurrency(p.value) }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                    <EmptyState
                        v-else
                        :icon="Package"
                        title="Sin productos"
                        description="Agrega productos al catálogo para ver el inventario valorado."
                    />
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
