<?php

namespace Database\Factories;

use App\Enums\OpportunityStage;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Opportunity>
 */
class OpportunityFactory extends Factory
{
    protected $model = Opportunity::class;

    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->words(3, true)),
            'customer_id' => Customer::factory(),
            'lead_id' => null,
            'owner_id' => User::factory(),
            'stage' => OpportunityStage::Prospecting,
            'amount' => fake()->randomFloat(2, 1000, 50000),
            'probability' => fake()->numberBetween(10, 80),
            'expected_close_date' => fake()->dateTimeBetween('-15 days', '+60 days'),
            'closed_at' => null,
            'lost_reason' => null,
            'notes' => null,
        ];
    }

    public function qualification(): static
    {
        return $this->state(fn () => [
            'stage' => OpportunityStage::Qualification,
            'probability' => fake()->numberBetween(30, 60),
        ]);
    }

    public function proposal(): static
    {
        return $this->state(fn () => [
            'stage' => OpportunityStage::Proposal,
            'probability' => fake()->numberBetween(50, 70),
        ]);
    }

    public function negotiation(): static
    {
        return $this->state(fn () => [
            'stage' => OpportunityStage::Negotiation,
            'probability' => fake()->numberBetween(60, 90),
        ]);
    }

    public function won(): static
    {
        return $this->state(fn () => [
            'stage' => OpportunityStage::ClosedWon,
            'probability' => 100,
            'closed_at' => now(),
        ]);
    }

    public function lost(): static
    {
        return $this->state(fn () => [
            'stage' => OpportunityStage::ClosedLost,
            'closed_at' => now(),
            'lost_reason' => 'Fuera de presupuesto',
        ]);
    }

    public function fromLead(Lead $lead): static
    {
        return $this->state(fn () => [
            'lead_id' => $lead->id,
            'customer_id' => $lead->converted_to_customer_id,
            'name' => 'Venta: '.$lead->name,
            'amount' => $lead->estimated_value ?? fake()->randomFloat(2, 5000, 30000),
            'probability' => $lead->score ?? 50,
        ]);
    }
}
