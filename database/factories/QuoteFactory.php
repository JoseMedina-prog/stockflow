<?php

namespace Database\Factories;

use App\Enums\QuoteStatus;
use App\Models\Customer;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Quote>
 */
class QuoteFactory extends Factory
{
    protected $model = Quote::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quoteDate = fake()->dateTimeBetween('-60 days', '+15 days');
        $validUntil = (clone $quoteDate)->modify('+15 days');

        return [
            'folio' => 'COT-'.str_pad((string) fake()->unique()->numberBetween(1, 999999)), 6, '0', STR_PAD_LEFT),
            'customer_id' => Customer::factory(),
            'user_id' => User::factory(),
            'quote_date' => $quoteDate,
            'valid_until' => $validUntil,
            'subtotal' => 0,
            'discount' => 0,
            'tax' => 0,
            'total' => 0,
            'status' => fake()->randomElement(QuoteStatus::cases())->value,
            'notes' => fake()->optional()->sentence(),
            'terms' => fake()->optional()->sentence(),
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => QuoteStatus::Draft->value]);
    }

    public function sent(): static
    {
        return $this->state([
            'status' => QuoteStatus::Sent->value,
            'sent_at' => now(),
        ]);
    }

    public function accepted(): static
    {
        return $this->state([
            'status' => QuoteStatus::Accepted->value,
            'sent_at' => now()->subDay(),
            'accepted_at' => now(),
        ]);
    }
}
