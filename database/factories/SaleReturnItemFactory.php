<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleReturnItem>
 */
class SaleReturnItemFactory extends Factory
{
    protected $model = SaleReturnItem::class;

    public function definition(): array
    {
        $quantity = $this->faker->numberBetween(1, 3);
        $unitPrice = $this->faker->randomFloat(2, 5, 100);
        $subtotal = round($unitPrice * $quantity, 2);

        return [
            'sale_return_id' => SaleReturn::factory(),
            'sale_item_id' => SaleItem::factory(),
            'product_id' => Product::factory(),
            'quantity_returned' => $quantity,
            'unit_price' => $unitPrice,
            'tax_rate_snapshot' => 0,
            'tax_amount' => 0,
            'subtotal' => $subtotal,
            'line_total' => $subtotal,
        ];
    }
}
