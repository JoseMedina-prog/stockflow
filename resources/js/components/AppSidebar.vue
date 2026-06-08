<script setup lang="ts">
import CommandPalette from '@/components/stockflow/CommandPalette.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import ThemeToggle from '@/components/stockflow/ThemeToggle.vue';
import { useCommandPalette } from '@/composables/useCommandPalette';
import { usePermissions } from '@/composables/usePermissions';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuBadge,
} from '@/components/ui/sidebar';
import { Link } from '@inertiajs/vue3';
import {
    Activity,
    ArrowLeftRight,
    BarChart3,
    BookOpen,
    CheckSquare,
    CreditCard,
    FileText,
    Landmark,
    LayoutGrid,
    Package,
    Percent,
    Search,
    ShoppingBag,
    ShoppingCart,
    Tag,
    Target,
    Truck,
    Undo2,
    UserPlus,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

interface NavItem {
    title: string;
    href: string;
    icon: typeof LayoutGrid;
    permission: string;
}

const { can } = usePermissions();

const platformItems = computed<NavItem[]>(() => [
    { title: 'Tablero', href: '/dashboard', icon: LayoutGrid, permission: 'dashboard.view_any' },
    { title: 'Categorías', href: '/categories', icon: Tag, permission: 'categories.view_any' },
    { title: 'Productos', href: '/products', icon: Package, permission: 'products.view_any' },
    { title: 'Clientes', href: '/customers', icon: Users, permission: 'customers.view_any' },
    { title: 'Proveedores', href: '/suppliers', icon: Truck, permission: 'suppliers.view_any' },
].filter((i) => can(i.permission)));

const erpItems = computed<NavItem[]>(() => [
    { title: 'Compras', href: '/purchases', icon: ShoppingBag, permission: 'purchases.view_any' },
    { title: 'Ventas', href: '/sales', icon: ShoppingCart, permission: 'sales.view_any' },
    { title: 'Devoluciones', href: '/returns', icon: Undo2, permission: 'returns.view_any' },
    { title: 'Pagos', href: '/payments', icon: CreditCard, permission: 'payments.view_any' },
    { title: 'Movimientos', href: '/stock-movements', icon: ArrowLeftRight, permission: 'stock_movements.view_any' },
].filter((i) => can(i.permission)));

const crmItems = computed<NavItem[]>(() => [
    { title: 'Leads', href: '/leads', icon: UserPlus, permission: 'leads.view_any' },
    { title: 'Oportunidades', href: '/opportunities', icon: Target, permission: 'opportunities.view_any' },
    { title: 'Cotizaciones', href: '/quotes', icon: FileText, permission: 'quotes.view_any' },
    { title: 'Tareas', href: '/tasks', icon: CheckSquare, permission: 'tasks.view_any' },
    { title: 'Actividades', href: '/activities', icon: Activity, permission: 'activities.view_any' },
].filter((i) => can(i.permission)));

const reportsItem = computed(() => can('reports.view_any') ? { title: 'Reportes', href: '/reports', icon: BarChart3, permission: 'reports.view_any' } : null);

const accountingItems = computed<NavItem[]>(() => [
    { title: 'Contabilidad', href: '/accounting', icon: Landmark, permission: 'accounting.view_any' },
    { title: 'Impuestos', href: '/taxes', icon: Percent, permission: 'taxes.view_any' },
].filter((i) => can(i.permission)));

const { open: openCommandPalette } = useCommandPalette();
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
                <SidebarMenuItem class="mt-1">
                    <SidebarMenuButton
                        size="sm"
                        class="text-muted-foreground"
                        @click="openCommandPalette"
                    >
                        <Search />
                        <span>Buscar…</span>
                        <SidebarMenuBadge
                            class="ml-auto inline-flex items-center gap-0.5 rounded border border-border/60 bg-background px-1 font-mono text-[10px]"
                        >
                            <span class="hidden md:inline">Ctrl</span>
                            <span class="md:hidden">⌘</span>K
                        </SidebarMenuBadge>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain v-if="platformItems.length" label="Plataforma" :items="platformItems" />
            <NavMain v-if="erpItems.length" label="Operaciones" :items="erpItems" />
            <NavMain v-if="crmItems.length" label="CRM" :items="crmItems" />
            <NavMain v-if="accountingItems.length" label="Contabilidad" :items="accountingItems" />
            <NavMain v-if="reportsItem" label="Análisis" :items="[reportsItem]" />
        </SidebarContent>

        <SidebarFooter>
            <div class="flex items-center justify-between gap-1 px-1">
                <ThemeToggle />
                <NavUser />
            </div>
        </SidebarFooter>
    </Sidebar>
    <slot />

    <CommandPalette />
</template>
