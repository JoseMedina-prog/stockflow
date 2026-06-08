<?php

namespace Database\Factories;

use App\Enums\LeadSource;
use App\Enums\LeadStage;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('##########'),
            'company' => fake()->company(),
            'source' => LeadSource::Other,
            'stage' => LeadStage::New,
            'estimated_value' => fake()->randomFloat(2, 500, 10000),
            'score' => fake()->numberBetween(10, 90),
            'owner_id' => User::factory(),
            'notes' => null,
        ];
    }

    public function contacted(): static
    {
        return $this->state(fn () => ['stage' => LeadStage::Contacted]);
    }

    public function qualified(): static
    {
        return $this->state(fn () => ['stage' => LeadStage::Qualified, 'score' => fake()->numberBetween(50, 100)]);
    }

    public function won(): static
    {
        return $this->state(fn () => ['stage' => LeadStage::Won]);
    }

    public function lost(): static
    {
        return $this->state(fn () => [
            'stage' => LeadStage::Lost,
            'lost_reason' => 'Fuera de presupuesto',
        ]);
    }

    public function converted(): static
    {
        return $this->state(fn () => [
            'stage' => LeadStage::Won,
            'converted_at' => now(),
        ]);
    }
}
