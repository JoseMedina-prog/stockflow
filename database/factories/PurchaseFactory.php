<?php

namespace Database\Factories;

use App\Enums\PurchaseStatus;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Purchase>
 */
class PurchaseFactory extends Factory
{
    protected $model = Purchase::class;

    public function definition(): array
    {
        return [
            'folio' => 'TEMP-'.strtoupper(uniqid()),
            'supplier_id' => Supplier::factory(),
            'user_id' => User::factory(),
            'subtotal' => 0,
            'tax' => 0,
            'total' => 0,
            'status' => PurchaseStatus::Pending,
            'purchase_date' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'received_at' => null,
            'notes' => null,
        ];
    }

    public function received(): static
    {
        return $this->state(fn () => [
            'status' => PurchaseStatus::Received,
            'received_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => PurchaseStatus::Cancelled]);
    }
}
