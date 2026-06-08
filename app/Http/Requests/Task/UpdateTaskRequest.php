<?php

namespace App\Http\Requests\Task;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('tasks.update');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'due_date' => ['nullable', 'date'],
            'due_time' => ['nullable', 'date_format:H:i'],
            'priority' => ['required', Rule::enum(TaskPriority::class)],
            'status' => ['required', Rule::enum(TaskStatus::class)],
            'assigned_to' => ['required', 'integer', 'exists:users,id'],
            'taskable_type' => ['nullable', 'string', 'in:customer,lead,opportunity,sale'],
            'taskable_id' => ['nullable', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'El título es obligatorio.',
            'assigned_to.required' => 'Asigna la tarea a un responsable.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function resolvedTaskable(): array
    {
        if (! $this->taskable_type) {
            return [];
        }

        $map = [
            'customer' => \App\Models\Customer::class,
            'lead' => \App\Models\Lead::class,
            'opportunity' => \App\Models\Opportunity::class,
            'sale' => \App\Models\Sale::class,
        ];

        $class = $map[$this->taskable_type] ?? null;
        if (! $class || ! $this->taskable_id) {
            return [];
        }

        $model = $class::find($this->taskable_id);
        if (! $model) {
            return [];
        }

        return [
            'taskable_type' => $model->getMorphClass(),
            'taskable_id' => $model->getKey(),
        ];
    }
}
