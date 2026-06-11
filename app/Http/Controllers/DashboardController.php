<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Quote;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    private const DEFER_TTL = 60;
    private const STATS_TTL = 30;

    public function __invoke(Request $request): Response
    {
        $today = Carbon::today()->toDateString();
        $monthStart = Carbon::now()->startOfMonth()->toDateString();
        $userId = $request->user()->id;

        return Inertia::render('Dashboard', [
            'stats' => $this->loadStats($today, $monthStart),
            'chart' => Inertia::defer(fn () => $this->loadChart()),
            'recentSales' => Inertia::defer(fn () => $this->loadRecentSales()),
            'lowStockProducts' => Inertia::defer(fn () => $this->loadLowStockProducts()),
            'accountsReceivable' => Inertia::defer(fn () => $this->loadAccountsReceivable()),
            'accountsPayable' => Inertia::defer(fn () => $this->loadAccountsPayable()),
            'topCustomers' => Inertia::defer(fn () => $this->loadTopCustomers()),
            'topProducts' => Inertia::defer(fn () => $this->loadTopProducts()),
            'myTasks' => Inertia::defer(fn () => $this->loadMyTasks($userId, $today)),
            'myTasksSummary' => Inertia::defer(fn () => $this->loadMyTasksSummary($userId, $today)),
        ]);
    }

    private function loadStats(string $today, string $monthStart): array
    {
        return Cache::remember(
            "dashboard:stats:{$today}:{$monthStart}",
            self::STATS_TTL,
            fn () => $this->computeStats($today, $monthStart),
        );
    }

    private function computeStats(string $today, string $monthStart): array
    {
        $saleRow = DB::table('sales')
            ->selectRaw('
                COALESCE(SUM(CASE WHEN sale_date = ? THEN total ELSE 0 END), 0) as sales_today_total,
                COUNT(CASE WHEN sale_date = ? THEN 1 END) as sales_today_count,
                COALESCE(SUM(CASE WHEN sale_date >= ? THEN total ELSE 0 END), 0) as sales_month_total,
                COUNT(CASE WHEN sale_date >= ? THEN 1 END) as sales_month_count,
                COALESCE(SUM(CASE WHEN balance > 0 THEN balance ELSE 0 END), 0) as receivable_total,
                COUNT(CASE WHEN balance > 0 THEN 1 END) as receivable_count
            ', [$today, $today, $monthStart, $monthStart])
            ->first();

        $purchaseRow = DB::table('purchases')
            ->selectRaw('
                COALESCE(SUM(CASE WHEN purchase_date = ? AND status != ? THEN total ELSE 0 END), 0) as purchases_today_total,
                COUNT(CASE WHEN purchase_date = ? AND status != ? THEN 1 END) as purchases_today_count,
                COALESCE(SUM(CASE WHEN purchase_date >= ? AND status != ? THEN total ELSE 0 END), 0) as purchases_month_total,
                COUNT(CASE WHEN balance > 0 AND status != ? THEN 1 END) as payable_count,
                COALESCE(SUM(CASE WHEN balance > 0 AND status != ? THEN balance ELSE 0 END), 0) as payable_total
            ', [$today, 'cancelled', $today, 'cancelled', $monthStart, 'cancelled', 'cancelled', 'cancelled'])
            ->first();

        $productRow = DB::table('products')
            ->selectRaw('
                COUNT(*) as total_products,
                COUNT(CASE WHEN stock <= min_stock THEN 1 END) as low_stock_count
            ')
            ->first();

        $customerRow = DB::table('customers')->count();

        $row = DB::table('quotes')
            ->selectRaw("SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_quotes_count,
                         COALESCE(SUM(CASE WHEN status = 'open' THEN total ELSE 0 END), 0) as open_quotes_value")
            ->first();

        $leadsCount = Lead::open()->count();
        $opps = Opportunity::open()
            ->selectRaw('COUNT(*) as c, COALESCE(SUM(amount * probability / 100.0), 0) as weighted')
            ->first();

        return [
            'sales_today_total' => (float) $saleRow->sales_today_total,
            'sales_today_count' => (int) $saleRow->sales_today_count,
            'sales_month_total' => (float) $saleRow->sales_month_total,
            'sales_month_count' => (int) $saleRow->sales_month_count,
            'purchases_today_total' => (float) $purchaseRow->purchases_today_total,
            'purchases_today_count' => (int) $purchaseRow->purchases_today_count,
            'purchases_month_total' => (float) $purchaseRow->purchases_month_total,
            'total_products' => (int) $productRow->total_products,
            'low_stock_count' => (int) $productRow->low_stock_count,
            'total_customers' => (int) $customerRow,
            'receivable_total' => (float) $saleRow->receivable_total,
            'receivable_count' => (int) $saleRow->receivable_count,
            'payable_total' => (float) $purchaseRow->payable_total,
            'payable_count' => (int) $purchaseRow->payable_count,
            'open_quotes_count' => (int) $row->open_quotes_count,
            'open_quotes_value' => (float) $row->open_quotes_value,
            'leads_open_count' => $leadsCount,
            'opportunities_open_count' => (int) $opps->c,
            'opportunities_open_value' => (float) $opps->weighted,
        ];
    }

    private function loadChart(): array
    {
        $endDate = Carbon::today()->toDateString();
        $startDate = Carbon::today()->subDays(6)->toDateString();
        $cacheKey = "dashboard:chart:{$startDate}:{$endDate}";

        return Cache::remember($cacheKey, self::DEFER_TTL, function () use ($startDate, $endDate) {
            $salesByDay = DB::table('sales')
                ->selectRaw('DATE(sale_date) as date, SUM(total) as total')
                ->whereBetween('sale_date', [$startDate, $endDate])
                ->groupBy('date')
                ->pluck('total', 'date');

            $purchasesByDay = DB::table('purchases')
                ->selectRaw('DATE(purchase_date) as date, SUM(total) as total')
                ->where('status', '!=', 'cancelled')
                ->whereBetween('purchase_date', [$startDate, $endDate])
                ->groupBy('date')
                ->pluck('total', 'date');

            $chart = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::today()->subDays($i);
                $key = $date->toDateString();
                $salesTotal = (float) ($salesByDay[$key] ?? 0);
                $purchasesTotal = (float) ($purchasesByDay[$key] ?? 0);
                $chart[] = [
                    'date' => $key,
                    'label' => $date->locale('es')->translatedFormat('D'),
                    'sales' => $salesTotal,
                    'purchases' => $purchasesTotal,
                    'cashflow' => round($salesTotal - $purchasesTotal, 2),
                ];
            }

            return $chart;
        });
    }

    private function loadRecentSales(): array
    {
        return Cache::remember('dashboard:recent_sales', self::DEFER_TTL, function () {
            return Sale::with('customer:id,name')
                ->latest('sale_date')
                ->limit(5)
                ->get(['id', 'sale_date', 'total', 'customer_id'])
                ->map(fn (Sale $sale) => [
                    'id' => $sale->id,
                    'sale_date' => $sale->sale_date->toDateTimeString(),
                    'total' => (float) $sale->total,
                    'customer' => $sale->customer?->name,
                ])->all();
        });
    }

    private function loadLowStockProducts(): array
    {
        return Cache::remember('dashboard:low_stock', self::DEFER_TTL, function () {
            return Product::with('category:id,name')
                ->whereColumn('stock', '<=', 'min_stock')
                ->orderBy('stock')
                ->limit(5)
                ->get(['id', 'name', 'sku', 'stock', 'min_stock', 'category_id'])
                ->map(fn (Product $p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'sku' => $p->sku,
                    'stock' => $p->stock,
                    'min_stock' => $p->min_stock,
                    'category' => $p->category?->name,
                ])->all();
        });
    }

    private function loadAccountsReceivable(): array
    {
        return Cache::remember('dashboard:receivable', self::DEFER_TTL, function () {
            $today = Carbon::today();
            return Sale::with('customer:id,name')
                ->where('balance', '>', 0)
                ->orderByDesc('balance')
                ->limit(5)
                ->get()
                ->map(fn (Sale $sale) => [
                    'id' => $sale->id,
                    'total' => (float) $sale->total,
                    'paid_amount' => (float) $sale->paid_amount,
                    'balance' => (float) $sale->balance,
                    'sale_date' => $sale->sale_date->toDateString(),
                    'days_overdue' => (int) $today->diffInDays(Carbon::parse($sale->sale_date), false) * -1,
                    'customer' => $sale->customer ? ['id' => $sale->customer->id, 'name' => $sale->customer->name] : null,
                ])->all();
        });
    }

    private function loadAccountsPayable(): array
    {
        return Cache::remember('dashboard:payable', self::DEFER_TTL, function () {
            $today = Carbon::today();
            return Purchase::with('supplier:id,name')
                ->where('balance', '>', 0)
                ->where('status', '!=', 'cancelled')
                ->orderByDesc('balance')
                ->limit(5)
                ->get()
                ->map(fn (Purchase $p) => [
                    'id' => $p->id,
                    'folio' => $p->folio,
                    'total' => (float) $p->total,
                    'paid_amount' => (float) $p->paid_amount,
                    'balance' => (float) $p->balance,
                    'purchase_date' => $p->purchase_date->toDateString(),
                    'days_overdue' => (int) $today->diffInDays(Carbon::parse($p->purchase_date), false) * -1,
                    'supplier' => ['id' => $p->supplier->id, 'name' => $p->supplier->name],
                ])->all();
        });
    }

    private function loadTopCustomers(): array
    {
        return Cache::remember('dashboard:top_customers', self::DEFER_TTL, function () {
            return DB::table('customers')
                ->join('sales', 'sales.customer_id', '=', 'customers.id')
                ->where('sales.total', '>', 0)
                ->groupBy('customers.id', 'customers.name')
                ->selectRaw('customers.id, customers.name, SUM(sales.total) as total_spent, COUNT(sales.id) as sales_count')
                ->orderByDesc('total_spent')
                ->limit(5)
                ->get()
                ->map(fn ($row) => [
                    'id' => $row->id,
                    'name' => $row->name,
                    'total_spent' => (float) $row->total_spent,
                    'sales_count' => (int) $row->sales_count,
                ])->all();
        });
    }

    private function loadTopProducts(): array
    {
        return Cache::remember('dashboard:top_products', self::DEFER_TTL, function () {
            return DB::table('sale_items')
                ->join('products', 'products.id', '=', 'sale_items.product_id')
                ->groupBy('products.id', 'products.name', 'products.sku')
                ->selectRaw('products.id, products.name, products.sku, SUM(sale_items.quantity) as total_quantity, SUM(sale_items.subtotal) as total_revenue')
                ->orderByDesc('total_quantity')
                ->limit(5)
                ->get()
                ->map(fn ($row) => [
                    'id' => $row->id,
                    'name' => $row->name,
                    'sku' => $row->sku,
                    'total_quantity' => (int) $row->total_quantity,
                    'total_revenue' => (float) $row->total_revenue,
                ])->all();
        });
    }

    private function loadMyTasks(int $userId, string $today): array
    {
        return Cache::remember("dashboard:my_tasks:{$userId}:{$today}", self::DEFER_TTL, function () use ($userId) {
            return Task::query()
                ->open()
                ->where('assigned_to', $userId)
                ->orderByRaw("CASE WHEN due_date IS NULL THEN 1 ELSE 0 END ASC")
                ->orderBy('due_date')
                ->orderByRaw("CASE priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 END")
                ->limit(8)
                ->get()
                ->map(fn (Task $t) => [
                    'id' => $t->id,
                    'title' => $t->title,
                    'due_date' => $t->due_date?->toDateString(),
                    'priority' => $t->priority->value,
                    'priority_label' => $t->priority->label(),
                    'priority_badge' => $t->priority->badgeVariant(),
                    'status' => $t->status->value,
                    'status_label' => $t->status->label(),
                    'status_badge' => $t->status->badgeVariant(),
                    'is_overdue' => $t->isOverdue(),
                    'is_due_today' => $t->isDueToday(),
                ])->all();
        });
    }

    private function loadMyTasksSummary(int $userId, string $today): array
    {
        return Cache::remember("dashboard:my_tasks_summary:{$userId}:{$today}", self::DEFER_TTL, function () use ($userId, $today) {
            $row = DB::table('tasks')
                ->selectRaw('
                    COUNT(CASE WHEN status = ? AND assigned_to = ? THEN 1 END) as open_count,
                    COUNT(CASE WHEN status = ? AND due_date < ? AND assigned_to = ? THEN 1 END) as overdue_count,
                    COUNT(CASE WHEN status = ? AND due_date = ? AND assigned_to = ? THEN 1 END) as today_count
                ', ['open', $userId, 'open', $today, $userId, 'open', $today, $userId])
                ->first();

            return [
                'open' => (int) $row->open_count,
                'overdue' => (int) $row->overdue_count,
                'today' => (int) $row->today_count,
            ];
        });
    }
}
