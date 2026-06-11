<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    private const TTL = 120;

    public function index(Request $request): Response
    {
        $topProducts = Cache::remember('reports:top_products', self::TTL, function () {
            return DB::table('sale_items')
                ->join('products', 'products.id', '=', 'sale_items.product_id')
                ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
                ->groupBy('products.id', 'products.name', 'products.sku', 'categories.name')
                ->selectRaw('products.id as product_id, products.name, products.sku, categories.name as category,
                             SUM(sale_items.quantity) as total_quantity, SUM(sale_items.subtotal) as total_revenue')
                ->orderByDesc('total_quantity')
                ->limit(10)
                ->get()
                ->map(fn ($row) => [
                    'product_id' => $row->product_id,
                    'name' => $row->name,
                    'sku' => $row->sku,
                    'category' => $row->category,
                    'total_quantity' => (int) $row->total_quantity,
                    'total_revenue' => (float) $row->total_revenue,
                ])->all();
        });

        $recentSales = Cache::remember('reports:recent_sales', self::TTL, function () {
            return DB::table('sales')
                ->leftJoin('customers', 'customers.id', '=', 'sales.customer_id')
                ->leftJoin('users', 'users.id', '=', 'sales.user_id')
                ->leftJoin('sale_items', 'sale_items.sale_id', '=', 'sales.id')
                ->groupBy('sales.id', 'sales.sale_date', 'sales.total', 'customers.name', 'users.name')
                ->selectRaw('sales.id, sales.sale_date, sales.total, customers.name as customer_name, users.name as user_name,
                             COUNT(sale_items.id) as items_count')
                ->orderByDesc('sales.sale_date')
                ->limit(10)
                ->get()
                ->map(fn ($row) => [
                    'id' => $row->id,
                    'sale_date' => \Illuminate\Support\Carbon::parse($row->sale_date)->toDateTimeString(),
                    'total' => (float) $row->total,
                    'items_count' => (int) $row->items_count,
                    'customer' => $row->customer_name,
                    'user' => $row->user_name,
                ])->all();
        });

        $inventory = Cache::remember('reports:inventory', self::TTL, function () {
            return DB::table('products')
                ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
                ->orderBy('products.name')
                ->selectRaw('products.id, products.name, products.sku, products.price, products.stock, products.min_stock, categories.name as category')
                ->get()
                ->map(fn ($row) => [
                    'id' => $row->id,
                    'name' => $row->name,
                    'sku' => $row->sku,
                    'category' => $row->category,
                    'price' => (float) $row->price,
                    'stock' => (int) $row->stock,
                    'min_stock' => (int) $row->min_stock,
                    'is_low_stock' => $row->stock <= $row->min_stock,
                    'value' => round((float) $row->price * $row->stock, 2),
                ])->all();
        });

        $inventoryValue = array_sum(array_column($inventory, 'value'));
        $lowStockCount = count(array_filter($inventory, fn ($p) => $p['is_low_stock']));

        return Inertia::render('Reports/Index', [
            'topProducts' => $topProducts,
            'recentSales' => $recentSales,
            'inventory' => $inventory,
            'summary' => [
                'inventory_value' => $inventoryValue,
                'inventory_count' => count($inventory),
                'low_stock_count' => $lowStockCount,
            ],
        ]);
    }
}
