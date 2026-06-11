<?php

namespace App\Http\Controllers;

use App\Enums\ActivityType;
use App\Http\Requests\Activity\StoreActivityRequest;
use App\Models\Activity;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Inertia\Inertia;
use Inertia\Response;

class ActivityController extends Controller
{
    public const CUSTOM_ACTIONS = [
        ['destroy', 'delete', 'activities/{activity}', 'activities.update', 'activities.destroy'],
        ['storeForCustomer', 'post', 'customers/{customer}/activities', 'activities.create', 'customers.activities.store'],
        ['storeForLead', 'post', 'leads/{lead}/activities', 'activities.create', 'leads.activities.store'],
        ['storeForOpportunity', 'post', 'opportunities/{opportunity}/activities', 'activities.create', 'opportunities.activities.store'],
        ['storeForSale', 'post', 'sales/{sale}/activities', 'activities.create', 'sales.activities.store'],
    ];

    public function index(Request $request): Response
    {
        $query = Activity::query()
            ->with(['user:id,name', 'subject']);

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('outcome', 'like', "%{$search}%");
            });
        }

        if ($typeValue = $request->string('type')->toString()) {
            $type = ActivityType::tryFrom($typeValue);
            if ($type !== null) {
                $query->where('type', $type->value);
            }
        }

        $query->inDateRange(
            $request->string('from')->toString() ?: null,
            $request->string('to')->toString() ?: null,
        );

        $activities = $query
            ->latest('occurred_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Activity $a) => [
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
                'subject_href' => $this->subjectHref($a),
                'subject_label' => $this->subjectLabel($a),
            ]);

        return Inertia::render('Activities/Index', [
            'activities' => $activities,
            'types' => array_map(fn (ActivityType $t) => ['value' => $t->value, 'label' => $t->label()], ActivityType::cases()),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'type' => $request->string('type')->toString() ?: null,
                'from' => $request->string('from')->toString() ?: null,
                'to' => $request->string('to')->toString() ?: null,
            ],
        ]);
    }

    public function storeForCustomer(StoreActivityRequest $request, Customer $customer): RedirectResponse
    {
        $this->createActivity($request, $customer);

        return to_route('customers.show', $customer)
            ->with('success', 'Actividad registrada.');
    }

    public function storeForLead(StoreActivityRequest $request, Lead $lead): RedirectResponse
    {
        $this->createActivity($request, $lead);

        return to_route('leads.show', $lead)
            ->with('success', 'Actividad registrada.');
    }

    public function storeForOpportunity(StoreActivityRequest $request, Opportunity $opportunity): RedirectResponse
    {
        $this->createActivity($request, $opportunity);

        return to_route('opportunities.show', $opportunity)
            ->with('success', 'Actividad registrada.');
    }

    public function storeForSale(StoreActivityRequest $request, Sale $sale): RedirectResponse
    {
        $this->createActivity($request, $sale);

        return to_route('sales.show', $sale)
            ->with('success', 'Actividad registrada.');
    }

    public function destroy(Activity $activity): RedirectResponse
    {
        $subjectType = $activity->subject_type;
        $subjectId = $activity->subject_id;
        $activity->delete();

        return $this->redirectToSubject($subjectType, $subjectId)
            ->with('success', 'Actividad eliminada.');
    }

    private function createActivity(StoreActivityRequest $request, Model $subject): void
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;
        $data['subject_type'] = $subject->getMorphClass();
        $data['subject_id'] = $subject->getKey();

        if (empty($data['duration_minutes'])) {
            $data['duration_minutes'] = null;
        }

        Activity::create($data);
    }

    private function redirectToSubject(?string $type, ?int $id): Redirector
    {
        if (! $type || ! $id) {
            return redirect();
        }

        return match ($type) {
            'App\\Models\\Customer' => redirect()->route('customers.show', ['customer' => $id]),
            'App\\Models\\Lead' => redirect()->route('leads.show', ['lead' => $id]),
            'App\\Models\\Opportunity' => redirect()->route('opportunities.show', ['opportunity' => $id]),
            'App\\Models\\Sale' => redirect()->route('sales.show', ['sale' => $id]),
            default => redirect(),
        };
    }

    private function subjectHref(Activity $activity): ?string
    {
        return match ($activity->subject_type) {
            'App\\Models\\Customer' => route('customers.show', $activity->subject_id),
            'App\\Models\\Lead' => route('leads.show', $activity->subject_id),
            'App\\Models\\Opportunity' => route('opportunities.show', $activity->subject_id),
            'App\\Models\\Sale' => route('sales.show', $activity->subject_id),
            default => null,
        };
    }

    private function subjectLabel(Activity $activity): string
    {
        $subject = $activity->subject;

        if (! $subject) {
            return '#'.$activity->subject_id;
        }

        return match (true) {
            $subject instanceof Sale => 'V-'.str_pad((string) $subject->id, 6, '0', STR_PAD_LEFT),
            default => $subject->name ?? '#'.$subject->id,
        };
    }
}
