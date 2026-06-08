<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $today = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();

        $salesToday = Sale::whereDate('sale_date', $today);
        $salesMonth = Sale::where('sale_date', '>=', $monthStart);
        $purchasesToday = Purchase::whereDate('purchase_date', $today);
        $purchasesMonth = Purchase::where('purchase_date', '>=', $monthStart);

        $stats = [
            'sales_today_total' => (float) $salesToday->sum('total'),
            'sales_today_count' => (int) $salesToday->count(),
            'sales_month_total' => (float) $salesMonth->sum('total'),
            'sales_month_count' => (int) $salesMonth->count(),
            'purchases_today_total' => (float) $purchasesToday->where('status', '!=', 'cancelled')->sum('total'),
            'purchases_today_count' => (int) $purchasesToday->where('status', '!=', 'cancelled')->count(),
            'purchases_month_total' => (float) $purchasesMonth->where('status', '!=', 'cancelled')->sum('total'),
            'total_products' => (int) Product::count(),
            'low_stock_count' => (int) Product::whereColumn('stock', '<=', 'min_stock')->count(),
            'total_customers' => (int) Customer::count(),
            'receivable_total' => (float) Sale::where('balance', '>', 0)->sum('balance'),
            'receivable_count' => (int) Sale::where('balance', '>', 0)->count(),
            'payable_total' => (float) Purchase::where('balance', '>', 0)->sum('balance'),
            'payable_count' => (int) Purchase::where('balance', '>', 0)->count(),
            'open_quotes_count' => (int) \App\Models\Quote::open()->count(),
            'open_quotes_value' => (float) \App\Models\Quote::open()->sum('total'),
            'leads_open_count' => (int) \App\Models\Lead::open()->count(),
            'opportunities_open_count' => (int) \App\Models\Opportunity::open()->count(),
            'opportunities_open_value' => (float) \App\Models\Opportunity::open()->get()->sum(fn ($o) => $o->weightedAmount()),
        ];

        $chart = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $salesTotal = (float) Sale::whereDate('sale_date', $date)->sum('total');
            $purchasesTotal = (float) Purchase::whereDate('purchase_date', $date)
                ->where('status', '!=', 'cancelled')
                ->sum('total');
            $chart->push([
                'date' => $date->toDateString(),
                'label' => $date->locale('es')->translatedFormat('D'),
                'sales' => $salesTotal,
                'purchases' => $purchasesTotal,
                'cashflow' => round($salesTotal - $purchasesTotal, 2),
            ]);
        }

        $recentSales = Sale::with('customer')
            ->latest('sale_date')
            ->limit(5)
            ->get()
            ->map(fn (Sale $sale) => [
                'id' => $sale->id,
                'sale_date' => $sale->sale_date->toDateTimeString(),
                'total' => (float) $sale->total,
                'customer' => $sale->customer?->name,
            ]);

        $lowStockProducts = Product::with('category')
            ->whereColumn('stock', '<=', 'min_stock')
            ->orderBy('stock')
            ->limit(5)
            ->get()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'stock' => $p->stock,
                'min_stock' => $p->min_stock,
                'category' => $p->category?->name,
            ]);

        $accountsReceivable = Sale::with('customer')
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
            ]);

        $accountsPayable = Purchase::with('supplier')
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
            ]);

        $topCustomers = Customer::query()
            ->withSum(['sales as total_spent'], 'total')
            ->withCount(['sales as sales_count'])
            ->whereHas('sales', fn (Builder $q) => $q->where('total', '>', 0))
            ->orderByDesc('total_spent')
            ->limit(5)
            ->get()
            ->map(fn (Customer $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'total_spent' => (float) $c->total_spent,
                'sales_count' => (int) $c->sales_count,
            ]);

        $topProducts = SaleItem::query()
            ->select(
                'products.id',
                'products.name',
                'products.sku',
                DB::raw('SUM(sale_items.quantity) as total_quantity'),
                DB::raw('SUM(sale_items.subtotal) as total_revenue'),
            )
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderByDesc('total_quantity')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'name' => $row->name,
                'sku' => $row->sku,
                'total_quantity' => (int) $row->total_quantity,
                'total_revenue' => (float) $row->total_revenue,
            ]);

        $myTasks = Task::query()
            ->with('assignee:id,name')
            ->open()
            ->where('assigned_to', $request->user()->id)
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
            ]);

        $myTasksSummary = [
            'open' => Task::open()->where('assigned_to', $request->user()->id)->count(),
            'overdue' => Task::overdue()->where('assigned_to', $request->user()->id)->count(),
            'today' => Task::open()
                ->where('assigned_to', $request->user()->id)
                ->whereDate('due_date', now()->toDateString())
                ->count(),
        ];

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'chart' => $chart,
            'recentSales' => $recentSales,
            'lowStockProducts' => $lowStockProducts,
            'accountsReceivable' => $accountsReceivable,
            'accountsPayable' => $accountsPayable,
            'topCustomers' => $topCustomers,
            'topProducts' => $topProducts,
            'myTasks' => $myTasks,
            'myTasksSummary' => $myTasksSummary,
        ]);
    }
}
