<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'category_id' => Category::factory(),
            'name' => ucwords($name),
            'sku' => strtoupper(Str::random(8)),
            'price' => fake()->randomFloat(2, 5, 500),
            'stock' => fake()->numberBetween(0, 100),
            'min_stock' => 5,
            'description' => fake()->sentence(),
        ];
    }

    public function lowStock(): static
    {
        return $this->state(fn () => [
            'stock' => 2,
            'min_stock' => 5,
        ]);
    }
}
