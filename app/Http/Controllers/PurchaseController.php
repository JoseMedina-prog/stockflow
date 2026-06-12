<?php

namespace App\Http\Controllers;

use App\Enums\PurchaseStatus;
use App\Http\Requests\Purchase\StorePurchaseRequest;
use App\Http\Requests\Purchase\UpdatePurchaseRequest;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Tax;
use App\Services\PurchaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseController extends Controller
{
    public const CUSTOM_ACTIONS = [
        ['receive', 'post', 'purchases/{purchase}/receive', 'purchases.update', 'purchases.receive'],
        ['cancel', 'post', 'purchases/{purchase}/cancel', 'purchases.update', 'purchases.cancel'],
    ];

    public function __construct(private readonly PurchaseService $service) {}

    public function index(Request $request): Response
    {
        $query = Purchase::query()
            ->with(['supplier:id,name', 'user:id,name'])
            ->withCount('items');

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('folio', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        if ($supplierId = $request->integer('supplier_id')) {
            $query->where('supplier_id', $supplierId);
        }

        if ($statusValue = $request->string('status')->toString()) {
            $status = PurchaseStatus::tryFrom($statusValue);
            if ($status !== null) {
                $query->where('status', $status->value);
            }
        }

        if ($from = $request->string('from')->toString()) {
            $query->where('purchase_date', '>=', $from);
        }

        if ($to = $request->string('to')->toString()) {
            $query->where('purchase_date', '<=', $to);
        }

        $purchases = $query
            ->latest('purchase_date')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Purchase $p) => [
                'id' => $p->id,
                'folio' => $p->folio,
                'purchase_date' => $p->purchase_date->toDateString(),
                'total' => (float) $p->total,
                'status' => $p->status->value,
                'status_label' => $p->status->label(),
                'status_badge' => $p->status->badgeVariant(),
                'items_count' => $p->items_count,
                'supplier' => ['id' => $p->supplier->id, 'name' => $p->supplier->name],
                'user' => ['id' => $p->user->id, 'name' => $p->user->name],
            ]);

        $suppliers = Supplier::orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Supplier $s) => ['id' => $s->id, 'name' => $s->name]);

        return Inertia::render('Purchases/Index', [
            'purchases' => $purchases,
            'suppliers' => $suppliers,
            'statuses' => array_map(
                fn (PurchaseStatus $s) => ['value' => $s->value, 'label' => $s->label()],
                PurchaseStatus::cases(),
            ),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'supplier_id' => $request->integer('supplier_id') ?: null,
                'status' => $request->string('status')->toString() ?: null,
                'from' => $request->string('from')->toString() ?: null,
                'to' => $request->string('to')->toString() ?: null,
            ],
        ]);
    }

    public function create(): Response
    {
        $products = Product::with('category')
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'price', 'stock'])
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'price' => (float) $p->price,
                'stock' => $p->stock,
                'category' => $p->category?->name,
            ]);

        $suppliers = Supplier::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'tax_id'])
            ->map(fn (Supplier $s) => ['id' => $s->id, 'name' => $s->name, 'tax_id' => $s->tax_id]);

        $taxes = Tax::active()->orderBy('code')
            ->get(['id', 'code', 'name', 'rate', 'type'])
            ->map(fn ($t) => [
                'id' => $t->id,
                'code' => $t->code,
                'name' => $t->name,
                'rate' => (float) $t->rate,
                'percent' => $t->percentRate(),
            ]);

        return Inertia::render('Purchases/Create', [
            'products' => $products,
            'suppliers' => $suppliers,
            'taxes' => $taxes,
        ]);
    }

    public function store(StorePurchaseRequest $request): RedirectResponse
    {
        $supplier = Supplier::findOrFail($request->integer('supplier_id'));

        $purchase = $this->service->create(
            supplier: $supplier,
            user: $request->user(),
            purchaseDate: Carbon::parse($request->input('purchase_date')),
            items: $request->input('items'),
            receiveImmediately: $request->boolean('receive_immediately', true),
            notes: $request->input('notes'),
        );

        return to_route('purchases.index')
            ->with('success', $purchase->status === PurchaseStatus::Received
                ? "Compra {$purchase->folio} registrada y stock actualizado."
                : "Compra {$purchase->folio} registrada como pendiente.");
    }

    public function show(Purchase $purchase): Response
    {
        $purchase->load(['supplier', 'user', 'items.product.category', 'payments.user']);

        return Inertia::render('Purchases/Show', [
            'purchase' => [
                'id' => $purchase->id,
                'folio' => $purchase->folio,
                'status' => $purchase->status->value,
                'status_label' => $purchase->status->label(),
                'status_badge' => $purchase->status->badgeVariant(),
                'purchase_date' => $purchase->purchase_date->toDateString(),
                'received_at' => $purchase->received_at?->toDateTimeString(),
                'subtotal' => (float) $purchase->subtotal,
                'tax' => (float) $purchase->tax,
                'total' => (float) $purchase->total,
                'paid_amount' => (float) $purchase->paid_amount,
                'balance' => (float) $purchase->balance,
                'is_fully_paid' => $purchase->isFullyPaid(),
                'notes' => $purchase->notes,
                'can_edit' => $purchase->status->canEditItems(),
                'can_receive' => $purchase->status->canReceive(),
                'can_cancel' => $purchase->status->canCancel(),
                'can_delete' => $purchase->status->canDelete(),
                'supplier' => [
                    'id' => $purchase->supplier->id,
                    'name' => $purchase->supplier->name,
                    'tax_id' => $purchase->supplier->tax_id,
                    'email' => $purchase->supplier->email,
                    'phone' => $purchase->supplier->phone,
                ],
                'user' => ['id' => $purchase->user->id, 'name' => $purchase->user->name],
                'items' => $purchase->items->map(fn ($item) => [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'product_sku' => $item->product->sku,
                    'category' => $item->product->category?->name,
                    'quantity' => $item->quantity,
                    'unit_cost' => (float) $item->unit_cost,
                    'subtotal' => (float) $item->subtotal,
                    'line_total' => (float) $item->line_total,
                ]),
                'payments' => $purchase->payments->map(fn ($p) => [
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

    public function edit(Purchase $purchase): Response|RedirectResponse
    {
        if (! $purchase->status->canEditItems()) {
            return to_route('purchases.index')
                ->with('error', "No se puede editar una compra en estado {$purchase->status->label()}.");
        }

        $purchase->load(['items.product']);

        $products = Product::with('category')
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'price', 'stock'])
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'price' => (float) $p->price,
                'stock' => $p->stock,
                'category' => $p->category?->name,
            ]);

        $suppliers = Supplier::orderBy('name')
            ->get(['id', 'name', 'tax_id'])
            ->map(fn (Supplier $s) => ['id' => $s->id, 'name' => $s->name, 'tax_id' => $s->tax_id]);

        return Inertia::render('Purchases/Edit', [
            'purchase' => [
                'id' => $purchase->id,
                'folio' => $purchase->folio,
                'supplier_id' => $purchase->supplier_id,
                'purchase_date' => $purchase->purchase_date->toDateString(),
                'notes' => $purchase->notes,
                'items' => $purchase->items->map(fn ($item) => [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_cost' => (float) $item->unit_cost,
                ])->toArray(),
            ],
            'products' => $products,
            'suppliers' => $suppliers,
        ]);
    }

    public function update(UpdatePurchaseRequest $request, Purchase $purchase): RedirectResponse
    {
        $supplier = Supplier::findOrFail($request->integer('supplier_id'));

        $this->service->update(
            purchase: $purchase,
            supplier: $supplier,
            purchaseDate: Carbon::parse($request->input('purchase_date')),
            items: $request->input('items'),
            notes: $request->input('notes'),
        );

        return to_route('purchases.index')
            ->with('success', "Compra {$purchase->folio} actualizada.");
    }

    public function receive(Request $request, Purchase $purchase): RedirectResponse
    {
        if (! $purchase->status->canReceive()) {
            return to_route('purchases.index')
                ->with('error', "La compra ya está en estado {$purchase->status->label()}.");
        }

        $this->service->receive($purchase, $request->user());

        return to_route('purchases.index')
            ->with('success', "Compra {$purchase->folio} marcada como recibida. Stock actualizado.");
    }

    public function cancel(Request $request, Purchase $purchase): RedirectResponse
    {
        if (! $purchase->status->canCancel()) {
            return to_route('purchases.index')
                ->with('error', 'La compra ya está cancelada.');
        }

        $this->service->cancel($purchase, $request->user(), $request->input('notes'));

        return to_route('purchases.index')
            ->with('success', "Compra {$purchase->folio} cancelada. Stock revertido.");
    }
}
