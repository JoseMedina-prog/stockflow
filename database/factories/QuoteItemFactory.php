<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuoteItem>
 */
class QuoteItemFactory extends Factory
{
    protected $model = QuoteItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = fake()->randomFloat(2, 50, 5000);
        $quantity = fake()->numberBetween(1, 10);
        $discount = fake()->randomElement([0, 0, 0, 5, 10]);
        $subtotal = round($price * $quantity * (1 - $discount / 100), 2);

        return [
            'quote_id' => Quote::factory(),
            'product_id' => Product::factory(),
            'description' => null,
            'quantity' => $quantity,
            'price' => $price,
            'discount_percent' => $discount,
            'subtotal' => $subtotal,
            'sort' => 0,
        ];
    }
}
