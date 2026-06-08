<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'title' => ucfirst(fake()->sentence(4)),
            'description' => fake()->boolean(40) ? fake()->sentence() : null,
            'due_date' => fake()->boolean(70) ? fake()->dateTimeBetween('-7 days', '+30 days') : null,
            'due_time' => null,
            'priority' => fake()->randomElement(TaskPriority::cases()),
            'status' => TaskStatus::Pending,
            'assigned_to' => User::factory(),
            'created_by' => null,
            'taskable_type' => null,
            'taskable_id' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => TaskStatus::Completed,
            'completed_at' => now(),
            'completed_by' => User::factory(),
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn () => ['status' => TaskStatus::InProgress]);
    }

    public function overdue(): static
    {
        return $this->state(fn () => [
            'status' => TaskStatus::Pending,
            'due_date' => now()->subDays(rand(1, 14)),
        ]);
    }

    public function urgent(): static
    {
        return $this->state(fn () => ['priority' => TaskPriority::Urgent]);
    }
}
