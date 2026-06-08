<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductLowStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_low_stock_when_stock_equals_min_stock(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock' => 5,
            'min_stock' => 5,
        ]);

        $this->assertTrue($product->isLowStock());
    }

    public function test_low_stock_when_stock_below_min_stock(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock' => 2,
            'min_stock' => 5,
        ]);

        $this->assertTrue($product->isLowStock());
    }

    public function test_not_low_stock_when_above_min_stock(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock' => 50,
            'min_stock' => 5,
        ]);

        $this->assertFalse($product->isLowStock());
    }
}
