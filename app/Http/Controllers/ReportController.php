<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $topProducts = SaleItem::query()
            ->selectRaw('product_id, SUM(quantity) as total_quantity, SUM(subtotal) as total_revenue')
            ->with('product.category')
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->get()
            ->map(function (SaleItem $item) {
                $product = $item->product;

                return [
                    'product_id' => $item->product_id,
                    'name' => $product?->name ?? '—',
                    'sku' => $product?->sku ?? '—',
                    'category' => $product?->category?->name,
                    'total_quantity' => (int) $item->total_quantity,
                    'total_revenue' => (float) $item->total_revenue,
                ];
            });

        $recentSales = Sale::with(['customer', 'user'])
            ->latest('sale_date')
            ->limit(10)
            ->get()
            ->map(fn (Sale $sale) => [
                'id' => $sale->id,
                'sale_date' => $sale->sale_date->toDateTimeString(),
                'total' => (float) $sale->total,
                'items_count' => $sale->items()->count(),
                'customer' => $sale->customer?->name,
                'user' => $sale->user?->name,
            ]);

        $inventory = Product::with('category')
            ->orderBy('name')
            ->get()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'category' => $p->category?->name,
                'price' => (float) $p->price,
                'stock' => $p->stock,
                'min_stock' => $p->min_stock,
                'is_low_stock' => $p->isLowStock(),
                'value' => round((float) $p->price * $p->stock, 2),
            ]);

        $inventoryValue = (float) $inventory->sum('value');
        $lowStockCount = $inventory->where('is_low_stock', true)->count();

        return Inertia::render('Reports/Index', [
            'topProducts' => $topProducts,
            'recentSales' => $recentSales,
            'inventory' => $inventory,
            'summary' => [
                'inventory_value' => $inventoryValue,
                'inventory_count' => $inventory->count(),
                'low_stock_count' => $lowStockCount,
            ],
        ]);
    }
}
