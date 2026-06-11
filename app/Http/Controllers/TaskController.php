<?php

namespace App\Http\Controllers;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Sale;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskCompletionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public const CUSTOM_ACTIONS = [
        ['complete', 'post', 'tasks/{task}/complete', 'tasks.update', 'tasks.complete'],
    ];

    public function __construct(private readonly TaskCompletionService $completer) {}

    public function index(Request $request): Response
    {
        $query = Task::query()
            ->with(['assignee:id,name', 'creator:id,name', 'taskable']);

        if ($search = $request->string('search')->toString()) {
            $query->search($search);
        }

        if ($priorityValue = $request->string('priority')->toString()) {
            $priority = TaskPriority::tryFrom($priorityValue);
            if ($priority !== null) {
                $query->where('priority', $priority->value);
            }
        }

        if ($statusValue = $request->string('status')->toString()) {
            $status = TaskStatus::tryFrom($statusValue);
            if ($status !== null) {
                $query->where('status', $status->value);
            }
        }

        if ($assigneeId = $request->integer('assignee_id')) {
            $query->where('assigned_to', $assigneeId);
        }

        if ($request->boolean('mine')) {
            $query->where('assigned_to', $request->user()->id);
        }

        if ($request->boolean('overdue')) {
            $query->overdue();
        }

        $tasks = $query
            ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('due_date')
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 END")
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Task $t) => [
                'id' => $t->id,
                'title' => $t->title,
                'description' => $t->description,
                'due_date' => $t->due_date?->toDateString(),
                'due_time' => $t->due_time,
                'priority' => $t->priority->value,
                'priority_label' => $t->priority->label(),
                'priority_badge' => $t->priority->badgeVariant(),
                'status' => $t->status->value,
                'status_label' => $t->status->label(),
                'status_badge' => $t->status->badgeVariant(),
                'is_overdue' => $t->isOverdue(),
                'is_due_today' => $t->isDueToday(),
                'is_open' => $t->status->isOpen(),
                'is_final' => $t->status->isFinal(),
                'completed_at' => $t->completed_at?->toDateTimeString(),
                'assignee' => $t->assignee ? ['id' => $t->assignee->id, 'name' => $t->assignee->name] : null,
                'creator' => $t->creator ? ['id' => $t->creator->id, 'name' => $t->creator->name] : null,
                'taskable_type' => $this->taskableTypeLabel($t->taskable_type),
                'taskable_href' => $this->taskableHref($t),
            ]);

        $summary = [
            'open_count' => Task::open()->count(),
            'mine_open' => Task::open()->assignedTo($request->user()->id)->count(),
            'mine_overdue' => Task::overdue()->assignedTo($request->user()->id)->count(),
            'mine_today' => Task::query()
                ->assignedTo($request->user()->id)
                ->open()
                ->whereDate('due_date', now()->toDateString())
                ->count(),
        ];

        $users = User::orderBy('name')->get(['id', 'name'])->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name]);

        return Inertia::render('Tasks/Index', [
            'tasks' => $tasks,
            'summary' => $summary,
            'priorities' => array_map(fn (TaskPriority $p) => ['value' => $p->value, 'label' => $p->label()], TaskPriority::cases()),
            'statuses' => array_map(fn (TaskStatus $s) => ['value' => $s->value, 'label' => $s->label()], TaskStatus::cases()),
            'users' => $users,
            'current_user_id' => $request->user()->id,
            'filters' => [
                'search' => $request->string('search')->toString(),
                'priority' => $request->string('priority')->toString() ?: null,
                'status' => $request->string('status')->toString() ?: null,
                'assignee_id' => $request->integer('assignee_id') ?: null,
                'mine' => $request->boolean('mine'),
                'overdue' => $request->boolean('overdue'),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Tasks/Create', [
            'users' => User::orderBy('name')->get(['id', 'name'])->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name]),
            'priorities' => array_map(fn (TaskPriority $p) => ['value' => $p->value, 'label' => $p->label()], TaskPriority::cases()),
            'statuses' => array_map(fn (TaskStatus $s) => ['value' => $s->value, 'label' => $s->label()], TaskStatus::cases()),
            'taskable_options' => $this->buildTaskableOptions($request),
        ]);
    }

    public function store(StoreTaskRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;
        $data = array_merge($data, $request->resolvedTaskable());

        $task = Task::create($data);

        return to_route('tasks.show', $task)
            ->with('success', "Tarea «{$task->title}» creada.");
    }

    public function show(Task $task): Response
    {
        $task->load(['assignee:id,name', 'creator:id,name', 'completer:id,name', 'taskable']);

        return Inertia::render('Tasks/Show', [
            'task' => [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'due_date' => $task->due_date?->toDateString(),
                'due_time' => $task->due_time,
                'priority' => $task->priority->value,
                'priority_label' => $task->priority->label(),
                'priority_badge' => $task->priority->badgeVariant(),
                'status' => $task->status->value,
                'status_label' => $task->status->label(),
                'status_badge' => $task->status->badgeVariant(),
                'is_overdue' => $task->isOverdue(),
                'is_due_today' => $task->isDueToday(),
                'is_open' => $task->status->isOpen(),
                'is_final' => $task->status->isFinal(),
                'completed_at' => $task->completed_at?->toDateTimeString(),
                'assignee' => $task->assignee ? ['id' => $task->assignee->id, 'name' => $task->assignee->name] : null,
                'creator' => $task->creator ? ['id' => $task->creator->id, 'name' => $task->creator->name] : null,
                'completer' => $task->completer ? ['id' => $task->completer->id, 'name' => $task->completer->name] : null,
                'taskable_type' => $this->taskableTypeLabel($task->taskable_type),
                'taskable_href' => $this->taskableHref($task),
                'taskable_label' => $this->taskableLabel($task->taskable),
                'created_at' => $task->created_at->toDateTimeString(),
            ],
        ]);
    }

    public function edit(Task $task, Request $request): Response
    {
        return Inertia::render('Tasks/Edit', [
            'task' => [
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'due_date' => $task->due_date?->toDateString(),
                'due_time' => $task->due_time,
                'priority' => $task->priority->value,
                'status' => $task->status->value,
                'assigned_to' => $task->assigned_to,
                'taskable_type' => $task->taskable_type
                    ? $this->friendlyType($task->taskable_type)
                    : null,
                'taskable_id' => $task->taskable_id,
            ],
            'users' => User::orderBy('name')->get(['id', 'name'])->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name]),
            'priorities' => array_map(fn (TaskPriority $p) => ['value' => $p->value, 'label' => $p->label()], TaskPriority::cases()),
            'statuses' => array_map(fn (TaskStatus $s) => ['value' => $s->value, 'label' => $s->label()], TaskStatus::cases()),
            'taskable_options' => $this->buildTaskableOptions($request),
        ]);
    }

    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        $data = $request->validated();
        $data = array_merge($data, $request->resolvedTaskable());

        $task->update($data);

        return to_route('tasks.show', $task)
            ->with('success', "Tarea «{$task->title}» actualizada.");
    }

    public function destroy(Task $task): RedirectResponse
    {
        $title = $task->title;
        $task->delete();

        return to_route('tasks.index')
            ->with('success', "Tarea «{$title}» eliminada.");
    }

    public function complete(Request $request, Task $task): RedirectResponse
    {
        try {
            $this->completer->toggle($task, $request->user());
        } catch (\DomainException $e) {
            return to_route('tasks.show', $task)
                ->with('error', $e->getMessage());
        }

        $isCompleted = $task->fresh()->status === TaskStatus::Completed;

        return to_route('tasks.index')
            ->with('success', $isCompleted ? "Tarea «{$task->title}» marcada como completada." : "Tarea «{$task->title}» reabierta.");
    }

    /**
     * @return array<int, array{type: string, id: int, label: string}>
     */
    private function buildTaskableOptions(Request $request): array
    {
        $options = [];

        $type = $request->string('taskable_type')->toString();
        $id = $request->integer('taskable_id');

        if ($type === 'customer' || ! $type) {
            $model = $type === 'customer' && $id ? Customer::find($id) : null;
            $options[] = [
                'type' => 'customer',
                'id' => $model?->id ?? 0,
                'label' => $model?->name ?? '',
            ];
        }
        if ($type === 'lead' || ! $type) {
            $model = $type === 'lead' && $id ? Lead::find($id) : null;
            $options[] = [
                'type' => 'lead',
                'id' => $model?->id ?? 0,
                'label' => $model?->name ?? '',
            ];
        }
        if ($type === 'opportunity' || ! $type) {
            $model = $type === 'opportunity' && $id ? Opportunity::find($id) : null;
            $options[] = [
                'type' => 'opportunity',
                'id' => $model?->id ?? 0,
                'label' => $model?->name ?? '',
            ];
        }
        if ($type === 'sale' || ! $type) {
            $model = $type === 'sale' && $id ? Sale::find($id) : null;
            $options[] = [
                'type' => 'sale',
                'id' => $model?->id ?? 0,
                'label' => $model ? 'V-'.str_pad((string) $model->id, 6, '0', STR_PAD_LEFT) : '',
            ];
        }

        return $options;
    }

    private function taskableTypeLabel(?string $type): ?string
    {
        return match ($type) {
            'App\\Models\\Customer', 'customer' => 'Cliente',
            'App\\Models\\Lead', 'lead' => 'Lead',
            'App\\Models\\Opportunity', 'opportunity' => 'Oportunidad',
            'App\\Models\\Sale', 'sale' => 'Venta',
            default => null,
        };
    }

    private function friendlyType(string $fqcn): string
    {
        return match ($fqcn) {
            'App\\Models\\Customer' => 'customer',
            'App\\Models\\Lead' => 'lead',
            'App\\Models\\Opportunity' => 'opportunity',
            'App\\Models\\Sale' => 'sale',
            default => '',
        };
    }

    private function taskableHref(Task $task): ?string
    {
        if (! $task->taskable_type || ! $task->taskable_id) {
            return null;
        }

        return match ($task->taskable_type) {
            'App\\Models\\Customer' => route('customers.show', $task->taskable_id),
            'App\\Models\\Lead' => route('leads.show', $task->taskable_id),
            'App\\Models\\Opportunity' => route('opportunities.show', $task->taskable_id),
            'App\\Models\\Sale' => route('sales.show', $task->taskable_id),
            default => null,
        };
    }

    private function taskableLabel($model): ?string
    {
        if (! $model) {
            return null;
        }

        return match (true) {
            $model instanceof Customer => $model->name,
            $model instanceof Lead => $model->name,
            $model instanceof Opportunity => $model->name,
            $model instanceof Sale => 'V-'.str_pad((string) $model->id, 6, '0', STR_PAD_LEFT),
            default => '#'.$model->id,
        };
    }
}
