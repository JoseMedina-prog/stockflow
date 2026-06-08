<?php

namespace Database\Factories;

use App\Enums\CreditNoteStatus;
use App\Models\CreditNote;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CreditNote>
 */
class CreditNoteFactory extends Factory
{
    protected $model = CreditNote::class;

    public function definition(): array
    {
        $amount = $this->faker->randomFloat(2, 50, 500);

        return [
            'folio' => 'NC-'.strtoupper(uniqid()),
            'customer_id' => Customer::factory(),
            'sale_return_id' => null,
            'amount' => $amount,
            'balance_remaining' => $amount,
            'expires_at' => now()->addYear(),
            'status' => CreditNoteStatus::Active,
            'notes' => null,
        ];
    }

    public function used(): static
    {
        return $this->state(fn () => [
            'status' => CreditNoteStatus::Used,
            'balance_remaining' => 0,
            'used_at' => now(),
        ]);
    }
}
