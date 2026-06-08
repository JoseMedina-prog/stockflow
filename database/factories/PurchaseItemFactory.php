<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseItem>
 */
class PurchaseItemFactory extends Factory
{
    protected $model = PurchaseItem::class;

    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 10);
        $unitCost = $this->faker->randomFloat(2, 5, 100);
        $subtotal = round($unitCost * $quantity, 2);
        $taxAmount = 0;
        $lineTotal = $subtotal;

        return [
            'purchase_id' => Purchase::factory(),
            'product_id' => Product::factory(),
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'tax_rate_snapshot' => 0,
            'tax_amount' => $taxAmount,
            'subtotal' => $subtotal,
            'line_total' => $lineTotal,
        ];
    }
}
