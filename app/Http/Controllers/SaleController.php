<?php

namespace App\Http\Controllers;

use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Http\Requests\Sale\StoreSaleRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Tax;
use App\Services\PostingService;
use App\Services\StockLedger;
use App\Support\FolioGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SaleController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Sale::query()
            ->with(['customer', 'user', 'items'])
            ->withCount('items');

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('id', $search)
                    ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        $sales = $query
            ->latest('sale_date')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Sale $sale) => [
                'id' => $sale->id,
                'folio' => $sale->folio,
                'sale_date' => $sale->sale_date->toDateTimeString(),
                'total' => (float) $sale->total,
                'items_count' => $sale->items_count,
                'customer' => $sale->customer ? ['id' => $sale->customer->id, 'name' => $sale->customer->name] : null,
                'user' => ['id' => $sale->user->id, 'name' => $sale->user->name],
            ]);

        return Inertia::render('Sales/Index', [
            'sales' => $sales,
            'filters' => [
                'search' => $request->string('search')->toString(),
            ],
        ]);
    }

    public function create(): Response
    {
        $products = Product::with('category')
            ->orderBy('name')
            ->get(['id', 'category_id', 'name', 'sku', 'price', 'stock'])
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'price' => (float) $p->price,
                'stock' => $p->stock,
                'category' => $p->category?->name,
                'is_low_stock' => $p->isLowStock(),
            ]);

        $customers = Customer::orderBy('name')->get(['id', 'name', 'email']);

        $taxes = Tax::active()->orderBy('code')
            ->get(['id', 'code', 'name', 'rate', 'type'])
            ->map(fn (Tax $t) => [
                'id' => $t->id,
                'code' => $t->code,
                'name' => $t->name,
                'rate' => (float) $t->rate,
                'percent' => $t->percentRate(),
            ]);

        return Inertia::render('Sales/Create', [
            'products' => $products,
            'customers' => $customers,
            'taxes' => $taxes,
        ]);
    }

    public function store(StoreSaleRequest $request, StockLedger $ledger, PostingService $posting): RedirectResponse
    {
        $sale = DB::transaction(function () use ($request, $ledger) {
            $subtotal = 0;
            $taxesTotal = 0;
            $prepared = [];

            foreach ($request->items as $item) {
                $product = Product::lockForUpdate()->find($item['product_id']);

                if (! $product) {
                    throw ValidationException::withMessages([
                        'items' => 'Uno de los productos ya no existe.',
                    ]);
                }

                $quantity = (int) $item['quantity'];
                $price = (float) $item['price'];
                $lineSubtotal = round($price * $quantity, 2);

                $taxId = $item['tax_id'] ?? null;
                $taxRate = 0;
                $taxAmount = 0;
                if ($taxId) {
                    $tax = Tax::find($taxId);
                    if ($tax && $tax->is_active) {
                        $taxRate = (float) $tax->rate;
                        $taxAmount = round($lineSubtotal * $taxRate, 2);
                    }
                }

                $subtotal += $lineSubtotal;
                $taxesTotal += $taxAmount;
                $prepared[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'price' => $price,
                    'subtotal' => $lineSubtotal,
                    'tax_id' => $taxId,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $taxAmount,
                ];
            }

            $total = round($subtotal + $taxesTotal, 2);

            $sale = Sale::create([
                'folio' => FolioGenerator::nextSaleFolio(),
                'user_id' => $request->user()->id,
                'customer_id' => $request->customer_id,
                'sale_date' => $request->sale_date,
                'total' => $total,
                'paid_amount' => 0,
                'balance' => $total,
            ]);

            foreach ($prepared as $row) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $row['product']->id,
                    'quantity' => $row['quantity'],
                    'price' => $row['price'],
                    'subtotal' => $row['subtotal'],
                    'tax_id' => $row['tax_id'],
                    'tax_rate' => $row['tax_rate'],
                    'tax_amount' => $row['tax_amount'],
                ]);

                try {
                    $ledger->record(
                        product: $row['product'],
                        signedQuantity: -$row['quantity'],
                        type: StockMovementType::Out,
                        reason: "Venta #{$sale->id}",
                        reference: $sale,
                        user: $request->user(),
                        occurredAt: $sale->sale_date,
                    );
                } catch (InsufficientStockException $e) {
                    throw ValidationException::withMessages([
                        'items' => $e->getMessage(),
                    ]);
                }
            }

            return $sale;
        });

        $posting->postSale($sale->fresh(), $request->user());

        return to_route('sales.show', $sale)
            ->with('success', 'Venta registrada correctamente.');
    }

    public function show(Sale $sale): Response
    {
        $sale->load(['customer', 'user', 'items.product.category', 'items.tax', 'payments.user']);

        return Inertia::render('Sales/Show', [
            'sale' => [
                'id' => $sale->id,
                'folio' => $sale->folio,
                'sale_date' => $sale->sale_date->toDateTimeString(),
                'subtotal' => (float) $sale->items->sum(fn ($i) => (float) $i->subtotal),
                'tax' => (float) $sale->items->sum(fn ($i) => (float) $i->tax_amount),
                'total' => (float) $sale->total,
                'paid_amount' => (float) $sale->paid_amount,
                'balance' => (float) $sale->balance,
                'is_fully_paid' => $sale->isFullyPaid(),
                'customer' => $sale->customer
                    ? ['id' => $sale->customer->id, 'name' => $sale->customer->name, 'email' => $sale->customer->email]
                    : null,
                'user' => ['id' => $sale->user->id, 'name' => $sale->user->name],
                'items' => $sale->items->map(fn (SaleItem $item) => [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'product_sku' => $item->product->sku,
                    'category' => $item->product->category?->name,
                    'quantity' => $item->quantity,
                    'price' => (float) $item->price,
                    'subtotal' => (float) $item->subtotal,
                    'tax_id' => $item->tax_id,
                    'tax_name' => $item->tax?->name,
                    'tax_rate' => (float) $item->tax_rate,
                    'tax_amount' => (float) $item->tax_amount,
                ]),
                'payments' => $sale->payments->map(fn ($p) => [
                    'id' => $p->id,
                    'folio' => $p->folio,
                    'method' => $p->method->value,
                    'method_label' => $p->method->label(),
                    'amount' => (float) $p->amount,
                    'reference' => $p->reference,
                    'paid_at' => $p->paid_at->toDateTimeString(),
                    'notes' => $p->notes,
                    'user' => ['id' => $p->user->id, 'name' => $p->user->name],
                ]),
            ],
        ]);
    }
}
