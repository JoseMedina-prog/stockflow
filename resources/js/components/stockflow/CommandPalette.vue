<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { useCommandPalette } from '@/composables/useCommandPalette';
import { formatCurrency, formatDateTime } from '@/composables/useFormat';
import {
    Dialog,
    DialogContent,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { ArrowLeftRight, CreditCard, Loader2, Package, Receipt, Search, ShoppingBag, Truck, Undo2, User } from 'lucide-vue-next';

interface ProductHit {
    id: number;
    name: string;
    sku: string;
    stock: number;
    price: number;
    category: string | null;
    href: string;
}

interface CustomerHit {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    href: string;
}

interface SupplierHit {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    tax_id: string | null;
    href: string;
}

interface SaleHit {
    id: number;
    folio: string;
    total: number;
    sale_date: string;
    customer: string | null;
    href: string;
}

interface PurchaseHit {
    id: number;
    folio: string;
    total: number;
    purchase_date: string;
    status: string;
    status_label: string;
    supplier: string | null;
    href: string;
}

interface ReturnHit {
    id: number;
    folio: string;
    total: number;
    status: string;
    status_label: string;
    reason: string | null;
    customer: string | null;
    href: string;
}

interface PaymentHit {
    id: number;
    folio: string;
    method: string;
    method_label: string;
    amount: number;
    paid_at: string;
    payable_folio: string;
    payable_href: string | null;
}

interface MovementHit {
    id: number;
    product: string | null;
    sku: string | null;
    type: string;
    type_label: string;
    quantity: number;
    reason: string | null;
    occurred_at: string;
    href: string;
}

const { isOpen, close } = useCommandPalette();
const query = ref('');
const loading = ref(false);
const products = ref<ProductHit[]>([]);
const customers = ref<CustomerHit[]>([]);
const suppliers = ref<SupplierHit[]>([]);
const sales = ref<SaleHit[]>([]);
const purchases = ref<PurchaseHit[]>([]);
const returns = ref<ReturnHit[]>([]);
const payments = ref<PaymentHit[]>([]);
const movements = ref<MovementHit[]>([]);
const selectedIndex = ref(0);

let debounceTimer: ReturnType<typeof setTimeout> | null = null;

const productsOffset = 0;
const customersOffset = computed(() => products.value.length);
const suppliersOffset = computed(() => customersOffset.value + customers.value.length);
const salesOffset = computed(() => suppliersOffset.value + suppliers.value.length);
const purchasesOffset = computed(() => salesOffset.value + sales.value.length);
const returnsOffset = computed(() => purchasesOffset.value + purchases.value.length);
const paymentsOffset = computed(() => returnsOffset.value + returns.value.length);
const movementsOffset = computed(() => paymentsOffset.value + payments.value.length);

const flatItems = computed(() => [
    ...products.value,
    ...customers.value,
    ...suppliers.value,
    ...sales.value,
    ...purchases.value,
    ...returns.value,
    ...payments.value,
    ...movements.value,
]);
const hasResults = computed(() => flatItems.value.length > 0);
const showHint = computed(() => !loading.value && query.value.length < 2 && !hasResults.value);

const performSearch = async (q: string) => {
    if (q.length < 2) {
        products.value = [];
        customers.value = [];
        suppliers.value = [];
        sales.value = [];
        purchases.value = [];
        returns.value = [];
        payments.value = [];
        movements.value = [];
        return;
    }
    loading.value = true;
    try {
        const res = await fetch(`/search?q=${encodeURIComponent(q)}`, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        const data = await res.json();
        products.value = data.products;
        customers.value = data.customers;
        suppliers.value = data.suppliers ?? [];
        sales.value = data.sales;
        purchases.value = data.purchases ?? [];
        returns.value = data.returns ?? [];
        payments.value = data.payments ?? [];
        movements.value = data.movements ?? [];
        selectedIndex.value = 0;
    } finally {
        loading.value = false;
    }
};

watch(query, (q) => {
    if (debounceTimer) clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => performSearch(q), 200);
});

const handleOpenChange = (val: boolean) => {
    if (!val) {
        close();
        query.value = '';
        products.value = [];
        customers.value = [];
        suppliers.value = [];
        sales.value = [];
        purchases.value = [];
        returns.value = [];
        payments.value = [];
        movements.value = [];
    }
};

const navigateTo = (href: string) => {
    close();
    router.visit(href);
};

const onKeyDown = (e: KeyboardEvent) => {
    if (!isOpen.value) {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            useCommandPalette().open();
        }
        return;
    }

    if (e.key === 'Escape') {
        e.preventDefault();
        close();
    } else if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (flatItems.value.length > 0) {
            selectedIndex.value = (selectedIndex.value + 1) % flatItems.value.length;
        }
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        if (flatItems.value.length > 0) {
            selectedIndex.value = (selectedIndex.value - 1 + flatItems.value.length) % flatItems.value.length;
        }
    } else if (e.key === 'Enter') {
        e.preventDefault();
        const target = flatItems.value[selectedIndex.value];
        if (target) navigateTo(target.href);
    }
};

onMounted(() => {
    document.addEventListener('keydown', onKeyDown);
});

onUnmounted(() => {
    document.removeEventListener('keydown', onKeyDown);
});

const isMac = typeof navigator !== 'undefined' && /Mac|iPhone|iPad/.test(navigator.platform);
</script>

<template>
    <Dialog :open="isOpen" @update:open="handleOpenChange">
        <DialogContent
            class="max-w-xl gap-0 overflow-hidden border-0 bg-popover p-0 shadow-2xl"
            :show-close-button="false"
        >
            <div class="flex items-center gap-3 border-b border-border/60 px-4">
                <Search class="size-4 shrink-0 text-muted-foreground" />
                <Input
                    v-model="query"
                    placeholder="Buscar productos, clientes, proveedores, compras, ventas, devoluciones, pagos o movimientos…"
                    class="h-12 flex-1 border-0 bg-transparent text-sm shadow-none focus-visible:ring-0 focus-visible:ring-offset-0"
                    autofocus
                />
                <Loader2 v-if="loading" class="size-4 animate-spin text-muted-foreground" />
                <kbd class="hidden items-center gap-1 rounded border border-border/60 bg-muted px-1.5 py-0.5 font-mono text-[10px] text-muted-foreground sm:inline-flex">
                    <span>{{ isMac ? '⌘' : 'Ctrl' }}</span>K
                </kbd>
            </div>

            <div class="max-h-[420px] overflow-y-auto py-2">
                <p v-if="showHint" class="px-4 py-12 text-center text-sm text-muted-foreground">
                    Escribe al menos 2 caracteres para buscar.
                </p>

                <p v-else-if="!hasResults && !loading && query.length >= 2" class="px-4 py-12 text-center text-sm text-muted-foreground">
                    Sin resultados para «{{ query }}».
                </p>

                <template v-else>
                    <div v-if="products.length > 0" class="px-2 pb-1">
                        <p class="px-2 py-1.5 text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">Productos</p>
                        <button
                            v-for="(p, i) in products"
                            :key="`p-${p.id}`"
                            type="button"
                            class="flex w-full items-center gap-3 rounded-md px-2 py-2 text-left text-sm transition-colors"
                            :class="selectedIndex === productsOffset + i ? 'bg-accent text-accent-foreground' : 'hover:bg-accent/60'"
                            @click="navigateTo(p.href)"
                            @mouseenter="selectedIndex = productsOffset + i"
                        >
                            <Package class="size-4 shrink-0 text-muted-foreground" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium">{{ p.name }}</p>
                                <p class="truncate font-mono text-xs text-muted-foreground">
                                    {{ p.sku }}<span v-if="p.category"> · {{ p.category }}</span>
                                </p>
                            </div>
                            <span class="shrink-0 text-xs tabular-nums text-muted-foreground">
                                {{ p.stock }} und
                            </span>
                        </button>
                    </div>

                    <div v-if="customers.length > 0" class="px-2 pb-1">
                        <p class="px-2 py-1.5 text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">Clientes</p>
                        <button
                            v-for="(c, i) in customers"
                            :key="`c-${c.id}`"
                            type="button"
                            class="flex w-full items-center gap-3 rounded-md px-2 py-2 text-left text-sm transition-colors"
                            :class="selectedIndex === customersOffset + i ? 'bg-accent text-accent-foreground' : 'hover:bg-accent/60'"
                            @click="navigateTo(c.href)"
                            @mouseenter="selectedIndex = customersOffset + i"
                        >
                            <User class="size-4 shrink-0 text-muted-foreground" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium">{{ c.name }}</p>
                                <p class="truncate text-xs text-muted-foreground">{{ c.email ?? c.phone ?? '—' }}</p>
                            </div>
                        </button>
                    </div>

                    <div v-if="suppliers.length > 0" class="px-2 pb-1">
                        <p class="px-2 py-1.5 text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">Proveedores</p>
                        <button
                            v-for="(s, i) in suppliers"
                            :key="`s-${s.id}`"
                            type="button"
                            class="flex w-full items-center gap-3 rounded-md px-2 py-2 text-left text-sm transition-colors"
                            :class="selectedIndex === suppliersOffset + i ? 'bg-accent text-accent-foreground' : 'hover:bg-accent/60'"
                            @click="navigateTo(s.href)"
                            @mouseenter="selectedIndex = suppliersOffset + i"
                        >
                            <Truck class="size-4 shrink-0 text-muted-foreground" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium">{{ s.name }}</p>
                                <p class="truncate text-xs text-muted-foreground">
                                    {{ s.tax_id ?? s.email ?? s.phone ?? '—' }}
                                </p>
                            </div>
                        </button>
                    </div>

                    <div v-if="sales.length > 0" class="px-2 pb-1">
                        <p class="px-2 py-1.5 text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">Ventas</p>
                        <button
                            v-for="(s, i) in sales"
                            :key="`s-${s.id}`"
                            type="button"
                            class="flex w-full items-center gap-3 rounded-md px-2 py-2 text-left text-sm transition-colors"
                            :class="selectedIndex === salesOffset + i ? 'bg-accent text-accent-foreground' : 'hover:bg-accent/60'"
                            @click="navigateTo(s.href)"
                            @mouseenter="selectedIndex = salesOffset + i"
                        >
                            <Receipt class="size-4 shrink-0 text-muted-foreground" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium">{{ s.folio }} · {{ s.customer ?? 'Consumidor final' }}</p>
                                <p class="truncate text-xs text-muted-foreground">{{ formatDateTime(s.sale_date) }}</p>
                            </div>
                            <span class="shrink-0 text-sm font-semibold tabular-nums">
                                {{ formatCurrency(s.total) }}
                            </span>
                        </button>
                    </div>

                    <div v-if="purchases.length > 0" class="px-2 pb-1">
                        <p class="px-2 py-1.5 text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">Compras</p>
                        <button
                            v-for="(p, i) in purchases"
                            :key="`p-${p.id}`"
                            type="button"
                            class="flex w-full items-center gap-3 rounded-md px-2 py-2 text-left text-sm transition-colors"
                            :class="selectedIndex === purchasesOffset + i ? 'bg-accent text-accent-foreground' : 'hover:bg-accent/60'"
                            @click="navigateTo(p.href)"
                            @mouseenter="selectedIndex = purchasesOffset + i"
                        >
                            <ShoppingBag class="size-4 shrink-0 text-muted-foreground" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium">{{ p.folio }} · {{ p.supplier ?? 'Sin proveedor' }}</p>
                                <p class="truncate text-xs text-muted-foreground">{{ p.status_label }} · {{ p.purchase_date }}</p>
                            </div>
                            <span class="shrink-0 text-sm font-semibold tabular-nums">
                                {{ formatCurrency(p.total) }}
                            </span>
                        </button>
                    </div>

                    <div v-if="returns.length > 0" class="px-2 pb-1">
                        <p class="px-2 py-1.5 text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">Devoluciones</p>
                        <button
                            v-for="(r, i) in returns"
                            :key="`r-${r.id}`"
                            type="button"
                            class="flex w-full items-center gap-3 rounded-md px-2 py-2 text-left text-sm transition-colors"
                            :class="selectedIndex === returnsOffset + i ? 'bg-accent text-accent-foreground' : 'hover:bg-accent/60'"
                            @click="navigateTo(r.href)"
                            @mouseenter="selectedIndex = returnsOffset + i"
                        >
                            <Undo2 class="size-4 shrink-0 text-muted-foreground" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium">{{ r.folio }} · {{ r.customer ?? 'Consumidor final' }}</p>
                                <p class="truncate text-xs text-muted-foreground">{{ r.status_label }} · {{ r.reason ?? '—' }}</p>
                            </div>
                            <span class="shrink-0 text-sm font-semibold tabular-nums">
                                {{ formatCurrency(r.total) }}
                            </span>
                        </button>
                    </div>

                    <div v-if="payments.length > 0" class="px-2 pb-1">
                        <p class="px-2 py-1.5 text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">Pagos</p>
                        <button
                            v-for="(pay, i) in payments"
                            :key="`pay-${pay.id}`"
                            type="button"
                            class="flex w-full items-center gap-3 rounded-md px-2 py-2 text-left text-sm transition-colors"
                            :class="selectedIndex === paymentsOffset + i ? 'bg-accent text-accent-foreground' : 'hover:bg-accent/60'"
                            @click="pay.payable_href && navigateTo(pay.payable_href)"
                            @mouseenter="selectedIndex = paymentsOffset + i"
                        >
                            <CreditCard class="size-4 shrink-0 text-muted-foreground" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium">{{ pay.folio }} · {{ pay.payable_folio }}</p>
                                <p class="truncate text-xs text-muted-foreground">{{ pay.method_label }} · {{ pay.paid_at }}</p>
                            </div>
                            <span class="shrink-0 text-sm font-semibold tabular-nums">
                                {{ formatCurrency(pay.amount) }}
                            </span>
                        </button>
                    </div>

                    <div v-if="movements.length > 0" class="px-2">
                        <p class="px-2 py-1.5 text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">Movimientos</p>
                        <button
                            v-for="(m, i) in movements"
                            :key="`m-${m.id}`"
                            type="button"
                            class="flex w-full items-center gap-3 rounded-md px-2 py-2 text-left text-sm transition-colors"
                            :class="selectedIndex === movementsOffset + i ? 'bg-accent text-accent-foreground' : 'hover:bg-accent/60'"
                            @click="navigateTo(m.href)"
                            @mouseenter="selectedIndex = movementsOffset + i"
                        >
                            <ArrowLeftRight class="size-4 shrink-0 text-muted-foreground" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium">
                                    {{ m.product ?? '—' }}
                                    <span class="ml-1 text-xs text-muted-foreground">{{ m.sku }}</span>
                                </p>
                                <p class="truncate text-xs text-muted-foreground">
                                    {{ m.type_label }} · {{ m.reason ?? '—' }}
                                </p>
                            </div>
                            <span class="shrink-0 text-xs tabular-nums text-muted-foreground">
                                {{ m.quantity }} und
                            </span>
                        </button>
                    </div>
                </template>
            </div>

            <div class="flex items-center justify-between gap-2 border-t border-border/60 bg-muted/30 px-4 py-2 text-[11px] text-muted-foreground">
                <div class="flex items-center gap-3">
                    <span class="flex items-center gap-1">
                        <kbd class="rounded border border-border/60 bg-background px-1 font-mono">↑↓</kbd>
                        navegar
                    </span>
                    <span class="flex items-center gap-1">
                        <kbd class="rounded border border-border/60 bg-background px-1 font-mono">↵</kbd>
                        seleccionar
                    </span>
                    <span class="flex items-center gap-1">
                        <kbd class="rounded border border-border/60 bg-background px-1 font-mono">Esc</kbd>
                        cerrar
                    </span>
                </div>
                <span class="hidden sm:inline">StockFlow</span>
            </div>
        </DialogContent>
    </Dialog>
</template>
