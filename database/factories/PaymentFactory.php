<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'folio' => 'P-'.strtoupper(uniqid()),
            'payable_type' => 'App\\Models\\Sale',
            'payable_id' => 1,
            'method' => PaymentMethod::Cash,
            'amount' => fake()->randomFloat(2, 50, 1000),
            'reference' => null,
            'paid_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'user_id' => User::factory(),
            'notes' => null,
        ];
    }

    public function cash(): static
    {
        return $this->state(fn () => ['method' => PaymentMethod::Cash]);
    }

    public function transfer(): static
    {
        return $this->state(fn () => [
            'method' => PaymentMethod::Transfer,
            'reference' => 'TR-'.fake()->numerify('######'),
        ]);
    }
}
