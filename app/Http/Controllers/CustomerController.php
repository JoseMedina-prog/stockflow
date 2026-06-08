<?php

namespace App\Http\Controllers;

use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Customer::query()
            ->withCount('sales')
            ->withSum('sales as total_spent', 'total');

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $customers = $query
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Customer $customer) => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'address' => $customer->address,
                'sales_count' => (int) $customer->sales_count,
                'total_spent' => (float) ($customer->total_spent ?? 0),
            ]);

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
            'filters' => [
                'search' => $request->string('search')->toString(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Customers/Create');
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $customer = Customer::create($request->validated());

        return to_route('customers.show', $customer)
            ->with('success', 'Cliente creado correctamente.');
    }

    public function show(Customer $customer): Response
    {
        $customer->load([
            'sales' => fn ($q) => $q->latest('sale_date')->limit(15),
            'sales.user:id,name',
            'returns' => fn ($q) => $q->latest('return_date')->limit(10),
            'creditNotes' => fn ($q) => $q->latest('issue_date')->limit(10),
            'opportunities' => fn ($q) => $q->latest('updated_at')->limit(10),
            'leads' => fn ($q) => $q->latest('updated_at')->limit(10),
            'tasks' => fn ($q) => $q->latest('due_date')->limit(15),
            'activities' => fn ($q) => $q->latest('occurred_at')->limit(20),
        ]);

        $kpis = [
            'lifetime_value' => (float) $customer->sales()->sum('total'),
            'sales_count' => (int) $customer->sales()->count(),
            'avg_ticket' => (float) ($customer->sales()->avg('total') ?? 0),
            'open_balance' => (float) $customer->sales()->sum('balance'),
            'pending_sales' => (int) $customer->sales()->where('balance', '>', 0)->count(),
            'returns_count' => (int) $customer->returns()->count(),
            'returns_total' => (float) $customer->returns()->sum('total'),
            'credit_notes_active' => (int) $customer->creditNotes()->where('status', 'active')->count(),
            'credit_notes_balance' => (float) $customer->creditNotes()->where('status', 'active')->sum('balance_remaining'),
            'open_opportunities' => (int) $customer->opportunities()->whereIn('stage', [
                'prospecting', 'qualification', 'proposal', 'negotiation',
            ])->count(),
            'open_opportunities_value' => (float) $customer->opportunities()->whereIn('stage', [
                'prospecting', 'qualification', 'proposal', 'negotiation',
            ])->sum('amount'),
            'open_tasks' => (int) $customer->tasks()->whereIn('status', ['pending', 'in_progress'])->count(),
            'last_purchase_at' => optional($customer->sales()->latest('sale_date')->first())->sale_date?->toDateTimeString(),
        ];

        $recentSales = $customer->sales->map(fn ($s) => [
            'id' => $s->id,
            'folio' => 'V-'.str_pad((string) $s->id, 6, '0', STR_PAD_LEFT),
            'sale_date' => $s->sale_date->toDateTimeString(),
            'total' => (float) $s->total,
            'paid_amount' => (float) $s->paid_amount,
            'balance' => (float) $s->balance,
            'is_fully_paid' => (float) $s->balance <= 0,
            'items_count' => $s->items()->count(),
            'user' => ['id' => $s->user->id, 'name' => $s->user->name],
        ])->values();

        $recentReturns = $customer->returns->map(fn ($r) => [
            'id' => $r->id,
            'folio' => $r->folio,
            'return_date' => $r->return_date->toDateString(),
            'total' => (float) $r->total,
            'status' => $r->status->value,
            'status_label' => $r->status->label(),
            'status_badge' => $r->status->badgeVariant(),
        ])->values();

        $creditNotes = $customer->creditNotes->map(fn ($c) => [
            'id' => $c->id,
            'folio' => $c->folio,
            'issue_date' => $c->issue_date->toDateString(),
            'original_amount' => (float) $c->original_amount,
            'balance_remaining' => (float) $c->balance_remaining,
            'status' => $c->status->value,
            'status_label' => $c->status->label(),
            'status_badge' => $c->status->badgeVariant(),
        ])->values();

        $opportunities = $customer->opportunities->map(fn ($o) => [
            'id' => $o->id,
            'name' => $o->name,
            'stage' => $o->stage->value,
            'stage_label' => $o->stage->label(),
            'stage_badge' => $o->stage->badgeVariant(),
            'amount' => (float) $o->amount,
            'probability' => (int) $o->probability,
            'expected_close_date' => $o->expected_close_date?->toDateString(),
            'is_overdue' => $o->isOverdue(),
        ])->values();

        $convertedLeads = $customer->leads->map(fn ($l) => [
            'id' => $l->id,
            'name' => $l->name,
            'source_label' => $l->source->label(),
            'converted_at' => $l->converted_at?->toDateTimeString(),
        ])->values();

        $openTasks = $customer->tasks->whereIn('status', ['pending', 'in_progress'])->map(fn ($t) => [
            'id' => $t->id,
            'title' => $t->title,
            'due_date' => $t->due_date?->toDateString(),
            'is_overdue' => $t->isOverdue(),
            'is_due_today' => $t->isDueToday(),
            'priority' => $t->priority->value,
            'priority_label' => $t->priority->label(),
            'priority_badge' => $t->priority->badgeVariant(),
            'status' => $t->status->value,
            'status_label' => $t->status->label(),
            'status_badge' => $t->status->badgeVariant(),
            'href' => route('tasks.show', $t->id),
        ])->values();

        $activities = $customer->activities->map(fn ($a) => [
            'id' => $a->id,
            'type' => $a->type->value,
            'type_label' => $a->type->label(),
            'type_badge' => $a->type->badgeVariant(),
            'description' => $a->description,
            'outcome' => $a->outcome,
            'occurred_at' => $a->occurred_at->toDateTimeString(),
            'duration_minutes' => $a->duration_minutes,
        ])->values();

        return Inertia::render('Customers/Show', [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'address' => $customer->address,
                'created_at' => $customer->created_at->toDateTimeString(),
            ],
            'kpis' => $kpis,
            'recentSales' => $recentSales,
            'recentReturns' => $recentReturns,
            'creditNotes' => $creditNotes,
            'opportunities' => $opportunities,
            'convertedLeads' => $convertedLeads,
            'openTasks' => $openTasks,
            'activities' => $activities,
        ]);
    }

    public function edit(Customer $customer): Response
    {
        return Inertia::render('Customers/Edit', [
            'customer' => $customer,
        ]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        return to_route('customers.show', $customer)
            ->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        if ($customer->sales()->exists()) {
            return to_route('customers.index')
                ->with('error', 'No se puede eliminar: el cliente tiene ventas asociadas.');
        }

        $customer->delete();

        return to_route('customers.index')
            ->with('success', 'Cliente eliminado correctamente.');
    }
}
