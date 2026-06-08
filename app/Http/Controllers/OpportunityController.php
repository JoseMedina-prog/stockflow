<?php

namespace App\Http\Controllers;

use App\Enums\OpportunityStage;
use App\Http\Requests\Opportunity\AdvanceOpportunityRequest;
use App\Http\Requests\Opportunity\StoreOpportunityRequest;
use App\Http\Requests\Opportunity\UpdateOpportunityRequest;
use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\User;
use App\Services\PipelineAdvanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OpportunityController extends Controller
{
    public function __construct(private readonly PipelineAdvanceService $advancer) {}

    public function index(Request $request): Response
    {
        $query = Opportunity::query()
            ->with(['customer:id,name', 'lead:id,name', 'owner:id,name']);

        if ($search = $request->string('search')->toString()) {
            $query->search($search);
        }

        if ($stageValue = $request->string('stage')->toString()) {
            $stage = OpportunityStage::tryFrom($stageValue);
            if ($stage !== null) {
                $query->where('stage', $stage->value);
            }
        }

        if ($ownerId = $request->integer('owner_id')) {
            $query->where('owner_id', $ownerId);
        }

        if ($customerId = $request->integer('customer_id')) {
            $query->where('customer_id', $customerId);
        }

        $kanban = $query
            ->orderBy('expected_close_date')
            ->get()
            ->groupBy(fn (Opportunity $o) => $o->stage->value);

        $kanbanData = collect(OpportunityStage::cases())
            ->mapWithKeys(fn (OpportunityStage $s) => [
                $s->value => $kanban->get($s->value, collect())->map(fn (Opportunity $o) => [
                    'id' => $o->id,
                    'name' => $o->name,
                    'amount' => (float) $o->amount,
                    'weighted_amount' => $o->weightedAmount(),
                    'probability' => (int) $o->probability,
                    'expected_close_date' => $o->expected_close_date?->toDateString(),
                    'is_overdue' => $o->isOverdue(),
                    'customer' => $o->customer ? ['id' => $o->customer->id, 'name' => $o->customer->name] : null,
                    'lead' => $o->lead ? ['id' => $o->lead->id, 'name' => $o->lead->name] : null,
                    'owner' => $o->owner ? ['id' => $o->owner->id, 'name' => $o->owner->name] : null,
                ])->values()->all(),
            ])
            ->all();

        $summary = [
            'open_count' => Opportunity::open()->count(),
            'open_value' => (float) Opportunity::open()->sum('amount'),
            'weighted_value' => (float) Opportunity::open()->get()->sum(fn ($o) => $o->weightedAmount()),
            'won_count' => Opportunity::where('stage', OpportunityStage::ClosedWon->value)->count(),
            'won_value' => (float) Opportunity::where('stage', OpportunityStage::ClosedWon->value)->sum('amount'),
            'lost_count' => Opportunity::where('stage', OpportunityStage::ClosedLost->value)->count(),
        ];

        $opportunities = Opportunity::query()
            ->with(['customer:id,name', 'lead:id,name', 'owner:id,name'])
            ->when($request->string('search')->toString(), fn ($q, $s) => $q->search($s))
            ->when($request->string('stage')->toString() && ($st = OpportunityStage::tryFrom($request->string('stage')->toString())), fn ($q) => $q->where('stage', $st->value))
            ->when($request->integer('owner_id'), fn ($q, $id) => $q->where('owner_id', $id))
            ->when($request->integer('customer_id'), fn ($q, $id) => $q->where('customer_id', $id))
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Opportunity $o) => [
                'id' => $o->id,
                'name' => $o->name,
                'amount' => (float) $o->amount,
                'weighted_amount' => $o->weightedAmount(),
                'probability' => (int) $o->probability,
                'stage' => $o->stage->value,
                'stage_label' => $o->stage->label(),
                'stage_badge' => $o->stage->badgeVariant(),
                'expected_close_date' => $o->expected_close_date?->toDateString(),
                'is_overdue' => $o->isOverdue(),
                'is_final' => $o->stage->isFinal(),
                'customer' => $o->customer ? ['id' => $o->customer->id, 'name' => $o->customer->name] : null,
                'lead' => $o->lead ? ['id' => $o->lead->id, 'name' => $o->lead->name] : null,
                'owner' => $o->owner ? ['id' => $o->owner->id, 'name' => $o->owner->name] : null,
            ]);

        return Inertia::render('Opportunities/Index', [
            'kanban' => $kanbanData,
            'opportunities' => $opportunities,
            'stages' => array_map(
                fn (OpportunityStage $s) => ['value' => $s->value, 'label' => $s->label(), 'badge' => $s->badgeVariant(), 'open' => $s->isOpen()],
                OpportunityStage::cases(),
            ),
            'customers' => Customer::orderBy('name')->get(['id', 'name'])->map(fn (Customer $c) => ['id' => $c->id, 'name' => $c->name]),
            'owners' => User::orderBy('name')->get(['id', 'name'])->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name]),
            'summary' => $summary,
            'filters' => [
                'search' => $request->string('search')->toString(),
                'stage' => $request->string('stage')->toString() ?: null,
                'owner_id' => $request->integer('owner_id') ?: null,
                'customer_id' => $request->integer('customer_id') ?: null,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Opportunities/Create', [
            'stages' => array_map(
                fn (OpportunityStage $s) => ['value' => $s->value, 'label' => $s->label()],
                array_filter(OpportunityStage::cases(), fn ($s) => $s->isOpen()),
            ),
            'customers' => Customer::orderBy('name')->get(['id', 'name'])->map(fn (Customer $c) => ['id' => $c->id, 'name' => $c->name]),
            'users' => User::orderBy('name')->get(['id', 'name'])->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name]),
        ]);
    }

    public function store(StoreOpportunityRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['owner_id'] = $data['owner_id'] ?? $request->user()->id;

        $opportunity = Opportunity::create($data);

        return to_route('opportunities.show', $opportunity)
            ->with('success', "Oportunidad «{$opportunity->name}» creada.");
    }

    public function show(Opportunity $opportunity): Response
    {
        $opportunity->load(['customer:id,name,email,phone', 'lead:id,name', 'owner:id,name', 'activities.user:id,name', 'tasks.assignee:id,name']);

        $activities = $opportunity->activities->map(fn ($a) => [
            'id' => $a->id,
            'type' => $a->type->value,
            'type_label' => $a->type->label(),
            'type_badge' => $a->type->badgeVariant(),
            'description' => $a->description,
            'outcome' => $a->outcome,
            'occurred_at' => $a->occurred_at->toDateTimeString(),
            'duration_minutes' => $a->duration_minutes,
            'user' => $a->user ? ['id' => $a->user->id, 'name' => $a->user->name] : null,
            'subject_type' => $a->subjectTypeLabel(),
            'subject_href' => route('opportunities.show', $opportunity->id),
            'subject_label' => $opportunity->name,
        ])->map(fn ($a) => array_merge($a, ['kind' => 'activity']));

        $tasks = $opportunity->tasks->map(fn ($t) => [
            'id' => $t->id,
            'title' => $t->title,
            'priority' => $t->priority->value,
            'priority_badge' => $t->priority->badgeVariant(),
            'status' => $t->status->value,
            'status_badge' => $t->status->badgeVariant(),
            'due_date' => $t->due_date?->toDateString(),
            'is_overdue' => $t->isOverdue(),
            'is_due_today' => $t->isDueToday(),
            'is_open' => $t->status->isOpen(),
            'assignee' => $t->assignee ? ['id' => $t->assignee->id, 'name' => $t->assignee->name] : null,
            'href' => route('tasks.show', $t->id),
        ])->map(fn ($t) => array_merge($t, ['kind' => 'task']));

        $timeline = collect($activities)->merge($tasks)->sortByDesc(function ($item) {
            return $item['kind'] === 'activity' ? strtotime($item['occurred_at']) : strtotime($item['due_date'] ?? '1970-01-01');
        })->values()->all();

        return Inertia::render('Opportunities/Show', [
            'opportunity' => [
                'id' => $opportunity->id,
                'name' => $opportunity->name,
                'stage' => $opportunity->stage->value,
                'stage_label' => $opportunity->stage->label(),
                'stage_badge' => $opportunity->stage->badgeVariant(),
                'amount' => (float) $opportunity->amount,
                'weighted_amount' => $opportunity->weightedAmount(),
                'probability' => (int) $opportunity->probability,
                'expected_close_date' => $opportunity->expected_close_date?->toDateString(),
                'is_overdue' => $opportunity->isOverdue(),
                'is_final' => $opportunity->stage->isFinal(),
                'is_won' => $opportunity->stage->isWon(),
                'is_lost' => $opportunity->stage->isLost(),
                'can_edit' => $opportunity->stage->canBeEdited(),
                'can_advance' => $opportunity->stage->isOpen() && count($opportunity->stage->nextStages()) > 0,
                'closed_at' => $opportunity->closed_at?->toDateTimeString(),
                'lost_reason' => $opportunity->lost_reason,
                'notes' => $opportunity->notes,
                'next_stages' => array_map(
                    fn (OpportunityStage $s) => ['value' => $s->value, 'label' => $s->label(), 'badge' => $s->badgeVariant()],
                    $opportunity->stage->nextStages(),
                ),
                'customer' => $opportunity->customer ? [
                    'id' => $opportunity->customer->id,
                    'name' => $opportunity->customer->name,
                    'email' => $opportunity->customer->email,
                    'phone' => $opportunity->customer->phone,
                ] : null,
                'lead' => $opportunity->lead ? ['id' => $opportunity->lead->id, 'name' => $opportunity->lead->name] : null,
                'owner' => $opportunity->owner ? ['id' => $opportunity->owner->id, 'name' => $opportunity->owner->name] : null,
                'created_at' => $opportunity->created_at->toDateTimeString(),
            ],
            'timeline' => $timeline,
            'activity_types' => array_map(
                fn (\App\Enums\ActivityType $t) => [
                    'value' => $t->value,
                    'label' => $t->label(),
                    'badge' => $t->badgeVariant(),
                    'has_duration' => $t->hasDuration(),
                ],
                \App\Enums\ActivityType::cases(),
            ),
        ]);
    }

    public function edit(Opportunity $opportunity): Response|RedirectResponse
    {
        if ($opportunity->stage->isFinal()) {
            return to_route('opportunities.show', $opportunity)
                ->with('error', 'No se puede editar una oportunidad cerrada.');
        }

        $opportunity->load(['customer:id,name', 'lead:id,name', 'owner:id,name']);

        return Inertia::render('Opportunities/Edit', [
            'opportunity' => [
                'id' => $opportunity->id,
                'name' => $opportunity->name,
                'customer_id' => $opportunity->customer_id,
                'lead_id' => $opportunity->lead_id,
                'owner_id' => $opportunity->owner_id,
                'stage' => $opportunity->stage->value,
                'amount' => (float) $opportunity->amount,
                'probability' => $opportunity->probability,
                'expected_close_date' => $opportunity->expected_close_date?->toDateString(),
                'notes' => $opportunity->notes,
            ],
            'stages' => array_map(
                fn (OpportunityStage $s) => ['value' => $s->value, 'label' => $s->label()],
                array_filter(OpportunityStage::cases(), fn ($s) => $s->isOpen()),
            ),
            'customers' => Customer::orderBy('name')->get(['id', 'name'])->map(fn (Customer $c) => ['id' => $c->id, 'name' => $c->name]),
            'users' => User::orderBy('name')->get(['id', 'name'])->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name]),
        ]);
    }

    public function update(UpdateOpportunityRequest $request, Opportunity $opportunity): RedirectResponse
    {
        $opportunity->update($request->validated());

        return to_route('opportunities.show', $opportunity)
            ->with('success', "Oportunidad «{$opportunity->name}» actualizada.");
    }

    public function destroy(Opportunity $opportunity): RedirectResponse
    {
        if ($opportunity->stage->isWon()) {
            return to_route('opportunities.index')
                ->with('error', 'No se puede eliminar una oportunidad ganada. Márcala como perdida primero.');
        }

        $name = $opportunity->name;
        $opportunity->delete();

        return to_route('opportunities.index')
            ->with('success', "Oportunidad «{$name}» eliminada.");
    }

    public function advance(AdvanceOpportunityRequest $request, Opportunity $opportunity): RedirectResponse
    {
        $targetStage = OpportunityStage::from($request->string('target_stage')->toString());

        try {
            $this->advancer->advance(
                opportunity: $opportunity,
                targetStage: $targetStage,
                user: $request->user(),
                lostReason: $request->input('lost_reason'),
                probability: $request->integer('probability') ?: null,
            );
        } catch (\DomainException $e) {
            return to_route('opportunities.show', $opportunity)
                ->withErrors(['target_stage' => $e->getMessage()])
                ->withInput();
        }

        $message = match (true) {
            $targetStage->isWon() => "¡Oportunidad ganada! {$opportunity->name} cerrada con éxito.",
            $targetStage->isLost() => "Oportunidad marcada como perdida.",
            default => "Oportunidad movida a «{$targetStage->label()}».",
        };

        return to_route('opportunities.show', $opportunity)->with('success', $message);
    }
}
