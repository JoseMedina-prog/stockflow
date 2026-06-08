<?php

namespace Database\Factories;

use App\Enums\SaleReturnStatus;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleReturn>
 */
class SaleReturnFactory extends Factory
{
    protected $model = SaleReturn::class;

    public function definition(): array
    {
        return [
            'folio' => 'D-'.strtoupper(uniqid()),
            'sale_id' => Sale::factory(),
            'customer_id' => Customer::factory(),
            'user_id' => User::factory(),
            'subtotal' => 0,
            'tax' => 0,
            'total' => 0,
            'reason' => fake()->sentence(),
            'status' => SaleReturnStatus::Pending,
            'refund_method' => null,
            'notes' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => SaleReturnStatus::Approved,
            'approved_at' => now(),
            'approved_by' => User::factory(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => SaleReturnStatus::Rejected,
            'rejected_at' => now(),
            'rejection_reason' => 'Fuera de política',
        ]);
    }
}
