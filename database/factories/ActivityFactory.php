<?php

namespace Database\Factories;

use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    public function definition(): array
    {
        return [
            'type' => ActivityType::Note,
            'subject_type' => Customer::class,
            'subject_id' => Customer::factory(),
            'user_id' => User::factory(),
            'description' => fake()->sentence(),
            'occurred_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'duration_minutes' => null,
            'outcome' => null,
        ];
    }

    public function call(): static
    {
        return $this->state(fn () => [
            'type' => ActivityType::Call,
            'duration_minutes' => fake()->numberBetween(2, 45),
            'outcome' => fake()->boolean(60) ? fake()->sentence() : null,
        ]);
    }

    public function email(): static
    {
        return $this->state(fn () => [
            'type' => ActivityType::Email,
            'duration_minutes' => null,
        ]);
    }

    public function meeting(): static
    {
        return $this->state(fn () => [
            'type' => ActivityType::Meeting,
            'duration_minutes' => fake()->numberBetween(15, 120),
        ]);
    }

    public function whatsapp(): static
    {
        return $this->state(fn () => [
            'type' => ActivityType::WhatsApp,
            'duration_minutes' => null,
        ]);
    }
}
