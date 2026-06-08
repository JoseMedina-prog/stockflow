<?php

namespace Database\Factories;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(StockMovementType::cases());

        return [
            'product_id' => Product::factory(),
            'type' => $type,
            'quantity' => $type === StockMovementType::Out
                ? $this->faker->numberBetween(1, 5)
                : $this->faker->numberBetween(1, 20),
            'reason' => $this->faker->sentence(3),
            'reference_type' => null,
            'reference_id' => null,
            'user_id' => User::factory(),
            'notes' => null,
            'occurred_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }

    public function incoming(int $quantity = 10): static
    {
        return $this->state(fn () => [
            'type' => StockMovementType::In,
            'quantity' => $quantity,
        ]);
    }

    public function outgoing(int $quantity = 2): static
    {
        return $this->state(fn () => [
            'type' => StockMovementType::Out,
            'quantity' => $quantity,
        ]);
    }
}
