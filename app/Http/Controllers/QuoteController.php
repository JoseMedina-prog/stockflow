<?php

namespace App\Http\Controllers;

use App\Enums\OpportunityStage;
use App\Enums\QuoteStatus;
use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Http\Requests\Quote\StoreQuoteRequest;
use App\Http\Requests\Quote\UpdateQuoteRequest;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\StockLedger;
use App\Support\FolioGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class QuoteController extends Controller
{
    public const CUSTOM_ACTIONS = [
        ['send', 'post', 'quotes/{quote}/send', 'quotes.update', 'quotes.send'],
        ['accept', 'post', 'quotes/{quote}/accept', 'quotes.update', 'quotes.accept'],
        ['reject', 'post', 'quotes/{quote}/reject', 'quotes.update', 'quotes.reject'],
        ['convert', 'post', 'quotes/{quote}/convert', 'sales.create', 'quotes.convert'],
    ];

    public function index(Request $request): Response
    {
        $query = Quote::query()
            ->with(['customer:id,name', 'user:id,name', 'opportunity:id,name'])
            ->withCount('items');

        if ($search = $request->string('search')->toString()) {
            $query->search($search);
        }

        if ($statusValue = $request->string('status')->toString()) {
            $status = QuoteStatus::tryFrom($statusValue);
            if ($status !== null) {
                $query->where('status', $status->value);
            }
        }

        if ($customerId = $request->integer('customer_id')) {
            $query->where('customer_id', $customerId);
        }

        if ($from = $request->string('from')->toString()) {
            $query->where('quote_date', '>=', $from);
        }

        if ($to = $request->string('to')->toString()) {
            $query->where('quote_date', '<=', $to);
        }

        $quotes = $query
            ->latest('quote_date')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Quote $q) => [
                'id' => $q->id,
                'folio' => $q->folio,
                'quote_date' => $q->quote_date->toDateString(),
                'valid_until' => $q->valid_until?->toDateString(),
                'is_expired' => $q->isExpired(),
                'subtotal' => (float) $q->subtotal,
                'tax' => (float) $q->tax,
                'total' => (float) $q->total,
                'status' => $q->status->value,
                'status_label' => $q->status->label(),
                'status_badge' => $q->status->badgeVariant(),
                'items_count' => $q->items_count,
                'customer' => $q->customer ? ['id' => $q->customer->id, 'name' => $q->customer->name] : null,
                'opportunity' => $q->opportunity ? ['id' => $q->opportunity->id, 'name' => $q->opportunity->name] : null,
                'user' => ['id' => $q->user->id, 'name' => $q->user->name],
            ]);

        $summary = [
            'open_count' => (int) Quote::open()->count(),
            'open_value' => (float) Quote::open()->sum('total'),
            'accepted_count' => (int) Quote::where('status', QuoteStatus::Accepted->value)->count(),
            'accepted_value' => (float) Quote::where('status', QuoteStatus::Accepted->value)->sum('total'),
            'converted_count' => (int) Quote::where('status', QuoteStatus::Converted->value)->count(),
        ];

        return Inertia::render('Quotes/Index', [
            'quotes' => $quotes,
            'summary' => $summary,
            'statuses' => array_map(
                fn (QuoteStatus $s) => ['value' => $s->value, 'label' => $s->label()],
                QuoteStatus::cases(),
            ),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'status' => $request->string('status')->toString() ?: null,
                'customer_id' => $request->integer('customer_id') ?: null,
                'from' => $request->string('from')->toString() ?: null,
                'to' => $request->string('to')->toString() ?: null,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Quotes/Create', [
            'products' => $this->productsList(),
            'customers' => Customer::orderBy('name')->get(['id', 'name', 'email', 'phone'])
                ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'email' => $c->email, 'phone' => $c->phone]),
            'opportunity' => $request->integer('opportunity_id') ? Opportunity::with('customer:id,name,email,phone')->find($request->integer('opportunity_id'))?->toArray() : null,
            'defaults' => [
                'customer_id' => $request->integer('customer_id'),
                'opportunity_id' => $request->integer('opportunity_id'),
                'quote_date' => now()->toDateString(),
                'valid_until' => now()->addDays(15)->toDateString(),
                'status' => QuoteStatus::Draft->value,
            ],
        ]);
    }

    public function store(StoreQuoteRequest $request): RedirectResponse
    {
        $quote = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $data['folio'] = FolioGenerator::nextQuoteFolio();
            $data['user_id'] = $request->user()->id;
            $data['status'] = $data['status'] ?? QuoteStatus::Draft->value;

            $quote = Quote::create([
                'folio' => $data['folio'],
                'opportunity_id' => $data['opportunity_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'user_id' => $data['user_id'],
                'quote_date' => $data['quote_date'],
                'valid_until' => $data['valid_until'] ?? null,
                'discount' => $data['discount'] ?? 0,
                'tax' => $data['tax'] ?? 0,
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,
            ]);

            $sort = 0;
            foreach ($data['items'] as $row) {
                QuoteItem::create([
                    'quote_id' => $quote->id,
                    'product_id' => $row['product_id'] ?? null,
                    'description' => $row['description'] ?? null,
                    'quantity' => (int) $row['quantity'],
                    'price' => (float) $row['price'],
                    'discount_percent' => (float) ($row['discount_percent'] ?? 0),
                    'subtotal' => round(((float) $row['price']) * ((int) $row['quantity']) * (1 - ((float) ($row['discount_percent'] ?? 0)) / 100), 2),
                    'sort' => $sort++,
                ]);
            }

            $quote->recalculateTotals();

            return $quote;
        });

        return to_route('quotes.show', $quote)
            ->with('success', "Cotización {$quote->folio} creada.");
    }

    public function show(Quote $quote): Response
    {
        $quote->load(['customer', 'user', 'opportunity.lead', 'items.product.category']);

        return Inertia::render('Quotes/Show', [
            'quote' => [
                'id' => $quote->id,
                'folio' => $quote->folio,
                'quote_date' => $quote->quote_date->toDateString(),
                'valid_until' => $quote->valid_until?->toDateString(),
                'is_expired' => $quote->isExpired(),
                'subtotal' => (float) $quote->subtotal,
                'discount' => (float) $quote->discount,
                'tax' => (float) $quote->tax,
                'total' => (float) $quote->total,
                'status' => $quote->status->value,
                'status_label' => $quote->status->label(),
                'status_badge' => $quote->status->badgeVariant(),
                'notes' => $quote->notes,
                'terms' => $quote->terms,
                'sent_at' => $quote->sent_at?->toDateTimeString(),
                'accepted_at' => $quote->accepted_at?->toDateTimeString(),
                'rejected_at' => $quote->rejected_at?->toDateTimeString(),
                'converted_at' => $quote->converted_at?->toDateTimeString(),
                'converted_sale_id' => $quote->converted_sale_id,
                'can_edit' => $quote->status->canBeEdited(),
                'can_send' => $quote->status->canBeSent(),
                'can_convert' => $quote->status->canBeConverted() && $quote->customer_id !== null,
                'opportunity' => $quote->opportunity ? ['id' => $quote->opportunity->id, 'name' => $quote->opportunity->name] : null,
                'customer' => $quote->customer ? [
                    'id' => $quote->customer->id,
                    'name' => $quote->customer->name,
                    'email' => $quote->customer->email,
                    'phone' => $quote->customer->phone,
                ] : null,
                'user' => ['id' => $quote->user->id, 'name' => $quote->user->name],
                'items' => $quote->items->map(fn (QuoteItem $i) => [
                    'id' => $i->id,
                    'product_id' => $i->product_id,
                    'product_name' => $i->product?->name,
                    'product_sku' => $i->product?->sku,
                    'category' => $i->product?->category?->name,
                    'description' => $i->description,
                    'quantity' => (int) $i->quantity,
                    'price' => (float) $i->price,
                    'discount_percent' => (float) $i->discount_percent,
                    'subtotal' => (float) $i->subtotal,
                    'line_total' => $i->lineTotal(),
                ]),
            ],
        ]);
    }

    public function edit(Quote $quote): Response|RedirectResponse
    {
        if (! $quote->status->canBeEdited()) {
            return to_route('quotes.show', $quote)
                ->with('error', "No se puede editar una cotización en estado {$quote->status->label()}.");
        }

        $quote->load(['items', 'customer:id,name,email,phone', 'opportunity:id,name']);

        return Inertia::render('Quotes/Edit', [
            'quote' => [
                'id' => $quote->id,
                'folio' => $quote->folio,
                'customer_id' => $quote->customer_id,
                'opportunity_id' => $quote->opportunity_id,
                'customer' => $quote->customer,
                'opportunity' => $quote->opportunity,
                'quote_date' => $quote->quote_date->toDateString(),
                'valid_until' => $quote->valid_until?->toDateString(),
                'discount' => (float) $quote->discount,
                'tax' => (float) $quote->tax,
                'notes' => $quote->notes,
                'terms' => $quote->terms,
                'items' => $quote->items->map(fn (QuoteItem $i) => [
                    'product_id' => $i->product_id,
                    'description' => $i->description,
                    'quantity' => (int) $i->quantity,
                    'price' => (float) $i->price,
                    'discount_percent' => (float) $i->discount_percent,
                ])->toArray(),
            ],
            'products' => $this->productsList(),
            'customers' => Customer::orderBy('name')->get(['id', 'name', 'email', 'phone'])
                ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'email' => $c->email, 'phone' => $c->phone]),
        ]);
    }

    public function update(UpdateQuoteRequest $request, Quote $quote): RedirectResponse
    {
        if (! $quote->status->canBeEdited()) {
            return to_route('quotes.show', $quote)
                ->with('error', "No se puede editar una cotización en estado {$quote->status->label()}.");
        }

        DB::transaction(function () use ($request, $quote) {
            $data = $request->validated();
            $quote->update([
                'opportunity_id' => $data['opportunity_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'quote_date' => $data['quote_date'],
                'valid_until' => $data['valid_until'] ?? null,
                'discount' => $data['discount'] ?? 0,
                'tax' => $data['tax'] ?? 0,
                'notes' => $data['notes'] ?? null,
                'terms' => $data['terms'] ?? null,
            ]);

            $quote->items()->delete();

            $sort = 0;
            foreach ($data['items'] as $row) {
                QuoteItem::create([
                    'quote_id' => $quote->id,
                    'product_id' => $row['product_id'] ?? null,
                    'description' => $row['description'] ?? null,
                    'quantity' => (int) $row['quantity'],
                    'price' => (float) $row['price'],
                    'discount_percent' => (float) ($row['discount_percent'] ?? 0),
                    'subtotal' => round(((float) $row['price']) * ((int) $row['quantity']) * (1 - ((float) ($row['discount_percent'] ?? 0)) / 100), 2),
                    'sort' => $sort++,
                ]);
            }

            $quote->recalculateTotals();
        });

        return to_route('quotes.show', $quote)
            ->with('success', "Cotización {$quote->folio} actualizada.");
    }

    public function destroy(Quote $quote): RedirectResponse
    {
        if ($quote->status === QuoteStatus::Converted) {
            return to_route('quotes.index')
                ->with('error', 'No se puede eliminar una cotización ya convertida en venta.');
        }

        $folio = $quote->folio;
        $quote->delete();

        return to_route('quotes.index')
            ->with('success', "Cotización {$folio} eliminada.");
    }

    public function send(Request $request, Quote $quote): RedirectResponse
    {
        if (! $quote->status->canBeSent()) {
            return to_route('quotes.show', $quote)
                ->with('error', 'Solo se pueden enviar cotizaciones en borrador.');
        }

        $quote->update([
            'status' => QuoteStatus::Sent,
            'sent_at' => now(),
        ]);

        return to_route('quotes.show', $quote)
            ->with('success', "Cotización {$quote->folio} marcada como enviada.");
    }

    public function accept(Request $request, Quote $quote): RedirectResponse
    {
        if (! $quote->status->isOpen()) {
            return to_route('quotes.show', $quote)
                ->with('error', 'Esta cotización ya no se puede aceptar.');
        }

        $quote->update([
            'status' => QuoteStatus::Accepted,
            'accepted_at' => now(),
        ]);

        return to_route('quotes.show', $quote)
            ->with('success', "Cotización {$quote->folio} aceptada por el cliente.");
    }

    public function reject(Request $request, Quote $quote): RedirectResponse
    {
        $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        if (! $quote->status->isOpen()) {
            return to_route('quotes.show', $quote)
                ->with('error', 'Esta cotización ya no se puede rechazar.');
        }

        $quote->update([
            'status' => QuoteStatus::Rejected,
            'rejected_at' => now(),
            'notes' => $quote->notes
                ? $quote->notes."\n\nMotivo de rechazo: ".$request->input('reason')
                : 'Motivo de rechazo: '.$request->input('reason'),
        ]);

        return to_route('quotes.show', $quote)
            ->with('success', "Cotización {$quote->folio} marcada como rechazada.");
    }

    public function convert(Request $request, Quote $quote, StockLedger $ledger): RedirectResponse
    {
        if (! $quote->status->canBeConverted()) {
            return to_route('quotes.show', $quote)
                ->with('error', 'Esta cotización no se puede convertir en venta.');
        }

        if (! $quote->customer_id) {
            return to_route('quotes.show', $quote)
                ->with('error', 'La cotización necesita un cliente para convertirse en venta.');
        }

        $sale = DB::transaction(function () use ($request, $quote, $ledger) {
            $total = (float) $quote->total;
            $items = [];

            foreach ($quote->items()->whereNotNull('product_id')->get() as $qi) {
                $product = Product::lockForUpdate()->find($qi->product_id);

                if (! $product) {
                    throw ValidationException::withMessages([
                        'items' => "El producto «{$qi->description}» ya no existe.",
                    ]);
                }

                $items[] = ['product' => $product, 'quantity' => (int) $qi->quantity, 'price' => (float) $qi->price];
            }

            $sale = Sale::create([
                'user_id' => $request->user()->id,
                'customer_id' => $quote->customer_id,
                'sale_date' => now(),
                'total' => $total,
                'paid_amount' => 0,
                'balance' => $total,
            ]);

            foreach ($items as $row) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $row['product']->id,
                    'quantity' => $row['quantity'],
                    'price' => $row['price'],
                    'subtotal' => round($row['price'] * $row['quantity'], 2),
                ]);

                try {
                    $ledger->record(
                        product: $row['product'],
                        signedQuantity: -$row['quantity'],
                        type: StockMovementType::Out,
                        reason: "Venta #{$sale->id} (Cotización {$quote->folio})",
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

            $quote->update([
                'status' => QuoteStatus::Converted,
                'converted_at' => now(),
                'converted_sale_id' => $sale->id,
            ]);

            if ($quote->opportunity_id) {
                $quote->opportunity?->update([
                    'stage' => OpportunityStage::ClosedWon,
                    'closed_at' => now(),
                ]);
            }

            return $sale;
        });

        return to_route('sales.show', $sale)
            ->with('success', "Cotización {$quote->folio} convertida en venta #{$sale->id}.");
    }

    private function productsList(): array
    {
        return Product::with('category')
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'price', 'stock'])
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'price' => (float) $p->price,
                'stock' => $p->stock,
                'category' => $p->category?->name,
            ])->all();
    }
}
