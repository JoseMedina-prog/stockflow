<?php

namespace App\Http\Controllers;

use App\Enums\LeadSource;
use App\Enums\LeadStage;
use App\Http\Requests\Lead\StoreLeadRequest;
use App\Http\Requests\Lead\UpdateLeadRequest;
use App\Models\Lead;
use App\Models\User;
use App\Services\LeadConversionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeadController extends Controller
{
    public function __construct(private readonly LeadConversionService $converter) {}

    public function index(Request $request): Response
    {
        $query = Lead::query()
            ->with(['owner:id,name', 'customer:id,name'])
            ->withCount(['activities']);

        if ($search = $request->string('search')->toString()) {
            $query->search($search);
        }

        if ($stageValue = $request->string('stage')->toString()) {
            $stage = LeadStage::tryFrom($stageValue);
            if ($stage !== null) {
                $query->where('stage', $stage->value);
            }
        }

        if ($sourceValue = $request->string('source')->toString()) {
            $query->where('source', $sourceValue);
        }

        if ($ownerId = $request->integer('owner_id')) {
            $query->where('owner_id', $ownerId);
        }

        $leads = $query
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Lead $lead) => [
                'id' => $lead->id,
                'name' => $lead->name,
                'company' => $lead->company,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'source' => $lead->source->value,
                'source_label' => $lead->source->label(),
                'stage' => $lead->stage->value,
                'stage_label' => $lead->stage->label(),
                'stage_badge' => $lead->stage->badgeVariant(),
                'estimated_value' => (float) ($lead->estimated_value ?? 0),
                'score' => $lead->score,
                'is_converted' => $lead->isConverted(),
                'converted_at' => $lead->converted_at?->toDateTimeString(),
                'owner' => $lead->owner ? ['id' => $lead->owner->id, 'name' => $lead->owner->name] : null,
                'customer' => $lead->customer ? ['id' => $lead->customer->id, 'name' => $lead->customer->name] : null,
            ]);

        $kanban = Lead::query()
            ->with('owner:id,name')
            ->withCount(['activities'])
            ->open()
            ->get()
            ->groupBy(fn (Lead $l) => $l->stage->value);

        $kanbanData = collect(LeadStage::cases())
            ->mapWithKeys(fn (LeadStage $s) => [
                $s->value => $kanban->get($s->value, collect())->map(fn (Lead $l) => [
                    'id' => $l->id,
                    'name' => $l->name,
                    'company' => $l->company,
                    'estimated_value' => (float) ($l->estimated_value ?? 0),
                    'score' => $l->score,
                    'owner' => $l->owner ? ['id' => $l->owner->id, 'name' => $l->owner->name] : null,
                ])->values()->all(),
            ])
            ->all();

        $owners = User::orderBy('name')->get(['id', 'name'])->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name]);

        return Inertia::render('Leads/Index', [
            'leads' => $leads,
            'kanban' => $kanbanData,
            'stages' => array_map(
                fn (LeadStage $s) => ['value' => $s->value, 'label' => $s->label(), 'badge' => $s->badgeVariant()],
                LeadStage::cases(),
            ),
            'sources' => array_map(
                fn (LeadSource $s) => ['value' => $s->value, 'label' => $s->label()],
                LeadSource::cases(),
            ),
            'owners' => $owners,
            'filters' => [
                'search' => $request->string('search')->toString(),
                'stage' => $request->string('stage')->toString() ?: null,
                'source' => $request->string('source')->toString() ?: null,
                'owner_id' => $request->integer('owner_id') ?: null,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Leads/Create', [
            'users' => User::orderBy('name')->get(['id', 'name'])->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name]),
            'sources' => array_map(fn (LeadSource $s) => ['value' => $s->value, 'label' => $s->label()], LeadSource::cases()),
            'stages' => array_map(fn (LeadStage $s) => ['value' => $s->value, 'label' => $s->label()], LeadStage::cases()),
        ]);
    }

    public function store(StoreLeadRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['owner_id'] = $data['owner_id'] ?? $request->user()->id;

        $lead = Lead::create($data);

        return to_route('leads.show', $lead)
            ->with('success', "Lead «{$lead->name}» creado correctamente.");
    }

    public function show(Lead $lead): Response
    {
        $lead->load(['owner:id,name', 'customer:id,name,email,phone', 'activities.user:id,name', 'tasks.assignee:id,name']);

        $activities = $lead->activities->map(fn ($a) => [
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
            'subject_href' => route('leads.show', $lead->id),
            'subject_label' => $lead->name,
        ])->map(fn ($a) => array_merge($a, ['kind' => 'activity']));

        $tasks = $lead->tasks->map(fn ($t) => [
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

        return Inertia::render('Leads/Show', [
            'lead' => [
                'id' => $lead->id,
                'name' => $lead->name,
                'company' => $lead->company,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'source' => $lead->source->value,
                'source_label' => $lead->source->label(),
                'stage' => $lead->stage->value,
                'stage_label' => $lead->stage->label(),
                'stage_badge' => $lead->stage->badgeVariant(),
                'estimated_value' => (float) ($lead->estimated_value ?? 0),
                'score' => $lead->score,
                'weighted_value' => $lead->weightedValue(),
                'notes' => $lead->notes,
                'lost_reason' => $lead->lost_reason,
                'is_converted' => $lead->isConverted(),
                'is_final' => $lead->stage->isFinal(),
                'can_edit' => $lead->stage->canBeEdited() && ! $lead->isConverted(),
                'can_convert' => ! $lead->isConverted(),
                'converted_at' => $lead->converted_at?->toDateTimeString(),
                'owner' => $lead->owner ? ['id' => $lead->owner->id, 'name' => $lead->owner->name] : null,
                'customer' => $lead->customer ? [
                    'id' => $lead->customer->id,
                    'name' => $lead->customer->name,
                    'email' => $lead->customer->email,
                    'phone' => $lead->customer->phone,
                ] : null,
                'created_at' => $lead->created_at->toDateTimeString(),
                'updated_at' => $lead->updated_at->toDateTimeString(),
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

    public function edit(Lead $lead): Response|RedirectResponse
    {
        if ($lead->isConverted() || $lead->stage->isFinal()) {
            return to_route('leads.show', $lead)
                ->with('error', 'No se puede editar un lead en estado final o ya convertido.');
        }

        return Inertia::render('Leads/Edit', [
            'lead' => [
                'id' => $lead->id,
                'name' => $lead->name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'company' => $lead->company,
                'source' => $lead->source->value,
                'stage' => $lead->stage->value,
                'estimated_value' => $lead->estimated_value,
                'score' => $lead->score,
                'owner_id' => $lead->owner_id,
                'notes' => $lead->notes,
            ],
            'users' => User::orderBy('name')->get(['id', 'name'])->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name]),
            'sources' => array_map(fn (LeadSource $s) => ['value' => $s->value, 'label' => $s->label()], LeadSource::cases()),
            'stages' => array_map(fn (LeadStage $s) => ['value' => $s->value, 'label' => $s->label()], LeadStage::cases()),
        ]);
    }

    public function update(UpdateLeadRequest $request, Lead $lead): RedirectResponse
    {
        $lead->update($request->validated());

        return to_route('leads.show', $lead)
            ->with('success', "Lead «{$lead->name}» actualizado.");
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        if ($lead->isConverted()) {
            return to_route('leads.index')
                ->with('error', 'No se puede eliminar un lead ya convertido. Archiva en su lugar.');
        }

        $name = $lead->name;
        $lead->delete();

        return to_route('leads.index')
            ->with('success', "Lead «{$name}» eliminado.");
    }

    public function convert(Request $request, Lead $lead): RedirectResponse
    {
        if ($lead->isConverted()) {
            return to_route('leads.show', $lead)
                ->with('error', "El lead «{$lead->name}» ya fue convertido.");
        }

        $customer = $this->converter->convert($lead, $request->user(), $request->only(['name', 'email', 'phone', 'address']));

        return to_route('customers.show', $customer)
            ->with('success', "Lead convertido. Cliente «{$customer->name}» creado.");
    }

    public function markLost(Request $request, Lead $lead): RedirectResponse
    {
        if ($lead->isConverted() || $lead->stage->isFinal()) {
            return to_route('leads.show', $lead)
                ->with('error', 'No se puede modificar un lead en estado final.');
        }

        $request->validate([
            'lost_reason' => ['required', 'string', 'max:1000'],
        ]);

        $lead->update([
            'stage' => LeadStage::Lost,
            'lost_reason' => $request->input('lost_reason'),
        ]);

        return to_route('leads.show', $lead)
            ->with('success', "Lead marcado como perdido.");
    }
}
