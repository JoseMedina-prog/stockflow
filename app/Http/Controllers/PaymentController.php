<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Http\Requests\Payment\StorePaymentRequest;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Services\PaymentService;
use App\Support\SubjectRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public const CUSTOM_ACTIONS = [
        ['storeForSale', 'post', 'sales/{sale}/payments', 'payments.create', 'sales.payments.store'],
        ['storeForPurchase', 'post', 'purchases/{purchase}/payments', 'payments.create', 'purchases.payments.store'],
    ];

    public function __construct(private readonly PaymentService $service) {}

    public function index(Request $request): Response
    {
        $query = Payment::query()->with(['payable', 'user:id,name']);

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('folio', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if ($methodValue = $request->string('method')->toString()) {
            $method = PaymentMethod::tryFrom($methodValue);
            if ($method !== null) {
                $query->where('method', $method->value);
            }
        }

        $from = $request->string('from')->toString();
        $to = $request->string('to')->toString();
        if ($from || $to) {
            $query->where(function ($q) use ($from, $to) {
                if ($from) {
                    $q->where('paid_at', '>=', $from);
                }
                if ($to) {
                    $q->where('paid_at', '<=', $to.' 23:59:59');
                }
            });
        }

        if ($payableType = $request->string('type')->toString()) {
            $reverseMap = [
                SubjectRegistry::TYPE_SALE => Sale::class,
                SubjectRegistry::TYPE_PURCHASE => Purchase::class,
            ];
            $class = $reverseMap[$payableType] ?? null;
            if ($class) {
                $query->where('payable_type', $class);
            }
        }

        $payments = $query
            ->latest('paid_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Payment $p) => [
                'id' => $p->id,
                'folio' => $p->folio,
                'method' => $p->method->value,
                'method_label' => $p->method->label(),
                'amount' => (float) $p->amount,
                'reference' => $p->reference,
                'notes' => $p->notes,
                'paid_at' => $p->paid_at->toDateTimeString(),
                'payable_type' => SubjectRegistry::type($p->payable_type) ?? $p->payable_type,
                'payable_folio' => $this->payableFolio($p),
                'payable_href' => $this->payableHref($p),
                'user' => ['id' => $p->user->id, 'name' => $p->user->name],
            ]);

        $totals = (clone $query)->reorder()->selectRaw('method, COUNT(*) as count, SUM(amount) as total')->groupBy('method')->get();
        $summary = [
            'total_amount' => (float) $totals->sum('total'),
            'total_count' => (int) $totals->sum('count'),
            'by_method' => $totals->map(fn ($row) => [
                'method' => $row->method instanceof PaymentMethod ? $row->method->value : $row->method,
                'method_label' => PaymentMethod::from($row->method instanceof PaymentMethod ? $row->method->value : $row->method)->label(),
                'count' => (int) $row->count,
                'total' => (float) $row->total,
            ])->values()->all(),
        ];

        return Inertia::render('Payments/Index', [
            'payments' => $payments,
            'summary' => $summary,
            'methods' => array_map(
                fn (PaymentMethod $m) => ['value' => $m->value, 'label' => $m->label()],
                PaymentMethod::cases(),
            ),
            'types' => [
                ['value' => 'sale', 'label' => 'Ventas'],
                ['value' => 'purchase', 'label' => 'Compras'],
            ],
            'filters' => [
                'search' => $request->string('search')->toString(),
                'method' => $request->string('method')->toString() ?: null,
                'type' => $request->string('type')->toString() ?: null,
                'from' => $from ?: null,
                'to' => $to ?: null,
            ],
        ]);
    }

    public function storeForSale(StorePaymentRequest $request, Sale $sale): RedirectResponse
    {
        try {
            $method = PaymentMethod::from($request->string('method')->toString());

            $payment = $this->service->register(
                payable: $sale,
                amount: (float) $request->input('amount'),
                method: $method,
                user: $request->user(),
                paidAt: Carbon::parse($request->input('paid_at')),
                reference: $request->input('reference'),
                notes: $request->input('notes'),
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        }

        return to_route('sales.show', $sale)
            ->with('success', "Pago {$payment->folio} registrado correctamente.");
    }

    public function storeForPurchase(StorePaymentRequest $request, Purchase $purchase): RedirectResponse
    {
        try {
            $method = PaymentMethod::from($request->string('method')->toString());

            $payment = $this->service->register(
                payable: $purchase,
                amount: (float) $request->input('amount'),
                method: $method,
                user: $request->user(),
                paidAt: Carbon::parse($request->input('paid_at')),
                reference: $request->input('reference'),
                notes: $request->input('notes'),
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        }

        return to_route('purchases.index')
            ->with('success', "Pago {$payment->folio} registrado correctamente.");
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $payableClass = $payment->payable_type;
        $payableId = $payment->payable_id;
        $folio = $payment->folio;
        $payable = $payableClass::find($payableId);
        $redirectRoute = $payable instanceof Sale
            ? 'sales.show'
            : 'purchases.index';

        $this->service->void($payment, request()->user());

        return to_route($redirectRoute, $payable)
            ->with('success', "Pago {$folio} anulado.");
    }

    private function payableFolio(Payment $payment): ?string
    {
        $label = SubjectRegistry::labelForInstance($payment->payable);

        return $label ?? '#'.$payment->payable_id;
    }

    private function payableHref(Payment $payment): ?string
    {
        return SubjectRegistry::href($payment->payable_type, $payment->payable_id);
    }
}
