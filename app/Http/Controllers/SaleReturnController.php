<?php

namespace App\Http\Controllers;

use App\Enums\RefundMethod;
use App\Enums\SaleReturnStatus;
use App\Http\Requests\SaleReturn\ApproveSaleReturnRequest;
use App\Http\Requests\SaleReturn\RejectSaleReturnRequest;
use App\Http\Requests\SaleReturn\StoreSaleReturnRequest;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Services\ReturnService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SaleReturnController extends Controller
{
    public function __construct(private readonly ReturnService $service) {}

    public function index(Request $request): Response
    {
        $query = SaleReturn::query()
            ->with(['sale:id,folio', 'customer:id,name', 'user:id,name', 'approver:id,name'])
            ->withCount('items');

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('folio', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('sale', fn ($q) => $q->where('folio', 'like', "%{$search}%"));
            });
        }

        if ($statusValue = $request->string('status')->toString()) {
            $status = SaleReturnStatus::tryFrom($statusValue);
            if ($status !== null) {
                $query->where('status', $status->value);
            }
        }

        if ($from = $request->string('from')->toString()) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->string('to')->toString()) {
            $query->whereDate('created_at', '<=', $to);
        }

        $returns = $query
            ->latest('created_at')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (SaleReturn $r) => [
                'id' => $r->id,
                'folio' => $r->folio,
                'status' => $r->status->value,
                'status_label' => $r->status->label(),
                'status_badge' => $r->status->badgeVariant(),
                'total' => (float) $r->total,
                'items_count' => $r->items_count,
                'reason' => $r->reason,
                'created_at' => $r->created_at->toDateTimeString(),
                'sale' => $r->sale ? ['id' => $r->sale->id, 'folio' => $r->sale->folio] : null,
                'customer' => $r->customer ? ['id' => $r->customer->id, 'name' => $r->customer->name] : null,
                'user' => ['id' => $r->user->id, 'name' => $r->user->name],
                'approver' => $r->approver ? ['id' => $r->approver->id, 'name' => $r->approver->name] : null,
            ]);

        return Inertia::render('SaleReturns/Index', [
            'returns' => $returns,
            'statuses' => array_map(
                fn (SaleReturnStatus $s) => ['value' => $s->value, 'label' => $s->label()],
                SaleReturnStatus::cases(),
            ),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'status' => $request->string('status')->toString() ?: null,
                'from' => $request->string('from')->toString() ?: null,
                'to' => $request->string('to')->toString() ?: null,
            ],
        ]);
    }

    public function create(Sale $sale): Response|RedirectResponse
    {
        $sale->load(['customer', 'user', 'items.product.category']);

        $items = $sale->items->map(function ($item) {
            $returnable = $item->returnableQuantity();

            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'product_sku' => $item->product->sku,
                'category' => $item->product->category?->name,
                'quantity_sold' => $item->quantity,
                'quantity_returned' => $item->returnedQuantity(),
                'returnable_quantity' => $returnable,
                'unit_price' => (float) $item->price,
                'subtotal' => (float) $item->subtotal,
            ];
        })->filter(fn ($i) => $i['returnable_quantity'] > 0)->values();

        if ($items->isEmpty()) {
            return to_route('sales.show', $sale)
                ->with('error', 'Todos los productos de esta venta ya fueron devueltos.');
        }

        return Inertia::render('SaleReturns/Create', [
            'sale' => [
                'id' => $sale->id,
                'folio' => 'V-'.str_pad((string) $sale->id, 6, '0', STR_PAD_LEFT),
                'sale_date' => $sale->sale_date->toDateString(),
                'total' => (float) $sale->total,
                'customer' => $sale->customer ? ['id' => $sale->customer->id, 'name' => $sale->customer->name] : null,
            ],
            'items' => $items,
        ]);
    }

    public function store(StoreSaleReturnRequest $request, Sale $sale): RedirectResponse
    {
        try {
            $saleReturn = $this->service->create(
                sale: $sale,
                user: $request->user(),
                items: $request->input('items'),
                reason: $request->input('reason'),
                notes: $request->input('notes'),
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['items' => $e->getMessage()])->withInput();
        }

        return to_route('sale-returns.show', $saleReturn)
            ->with('success', "Devolución {$saleReturn->folio} creada como pendiente.");
    }

    public function show(SaleReturn $saleReturn): Response
    {
        $saleReturn->load([
            'sale.customer',
            'customer',
            'user',
            'approver',
            'items.product.category',
            'items.saleItem',
            'creditNote',
        ]);

        return Inertia::render('SaleReturns/Show', [
            'saleReturn' => [
                'id' => $saleReturn->id,
                'folio' => $saleReturn->folio,
                'status' => $saleReturn->status->value,
                'status_label' => $saleReturn->status->label(),
                'status_badge' => $saleReturn->status->badgeVariant(),
                'subtotal' => (float) $saleReturn->subtotal,
                'tax' => (float) $saleReturn->tax,
                'total' => (float) $saleReturn->total,
                'reason' => $saleReturn->reason,
                'notes' => $saleReturn->notes,
                'rejection_reason' => $saleReturn->rejection_reason,
                'approved_at' => $saleReturn->approved_at?->toDateTimeString(),
                'rejected_at' => $saleReturn->rejected_at?->toDateTimeString(),
                'refund_method' => $saleReturn->refund_method?->value,
                'refund_method_label' => $saleReturn->refund_method?->label(),
                'can_approve' => $saleReturn->status->canApprove(),
                'can_reject' => $saleReturn->status->canReject(),
                'sale' => [
                    'id' => $saleReturn->sale->id,
                    'folio' => 'V-'.str_pad((string) $saleReturn->sale->id, 6, '0', STR_PAD_LEFT),
                    'sale_date' => $saleReturn->sale->sale_date->toDateString(),
                    'total' => (float) $saleReturn->sale->total,
                ],
                'customer' => $saleReturn->customer ? [
                    'id' => $saleReturn->customer->id,
                    'name' => $saleReturn->customer->name,
                ] : null,
                'user' => ['id' => $saleReturn->user->id, 'name' => $saleReturn->user->name],
                'approver' => $saleReturn->approver ? ['id' => $saleReturn->approver->id, 'name' => $saleReturn->approver->name] : null,
                'items' => $saleReturn->items->map(fn ($item) => [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'product_sku' => $item->product->sku,
                    'category' => $item->product->category?->name,
                    'quantity_returned' => $item->quantity_returned,
                    'unit_price' => (float) $item->unit_price,
                    'subtotal' => (float) $item->subtotal,
                ]),
                'credit_note' => $saleReturn->creditNote ? [
                    'id' => $saleReturn->creditNote->id,
                    'folio' => $saleReturn->creditNote->folio,
                    'amount' => (float) $saleReturn->creditNote->amount,
                    'balance_remaining' => (float) $saleReturn->creditNote->balance_remaining,
                    'status' => $saleReturn->creditNote->status->value,
                    'status_label' => $saleReturn->creditNote->status->label(),
                ] : null,
            ],
            'refund_methods' => array_map(
                fn (RefundMethod $m) => ['value' => $m->value, 'label' => $m->label()],
                RefundMethod::cases(),
            ),
        ]);
    }

    public function approve(ApproveSaleReturnRequest $request, SaleReturn $saleReturn): RedirectResponse
    {
        try {
            $refundMethod = RefundMethod::from($request->string('refund_method')->toString());
        } catch (\ValueError) {
            return back()->withErrors(['refund_method' => 'Método de reembolso inválido.']);
        }

        $this->service->approve(
            saleReturn: $saleReturn,
            approver: $request->user(),
            refundMethod: $refundMethod,
            notes: $request->input('notes'),
        );

        $message = $refundMethod === RefundMethod::CreditNote
            ? "Devolución {$saleReturn->folio} aprobada. Stock actualizado y nota de crédito emitida."
            : "Devolución {$saleReturn->folio} aprobada. Stock actualizado.";

        return to_route('sale-returns.show', $saleReturn)->with('success', $message);
    }

    public function reject(RejectSaleReturnRequest $request, SaleReturn $saleReturn): RedirectResponse
    {
        $this->service->reject(
            saleReturn: $saleReturn,
            approver: $request->user(),
            rejectionReason: $request->input('rejection_reason'),
        );

        return to_route('sale-returns.show', $saleReturn)
            ->with('success', "Devolución {$saleReturn->folio} rechazada.");
    }
}
