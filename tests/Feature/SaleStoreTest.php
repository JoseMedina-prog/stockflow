<?php

namespace Tests\Feature;

use App\Enums\StockMovementType;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_decrements_stock_and_persists_items(): void
    {
        $user = $this->vendedor();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 100,
            'stock' => 10,
        ]);
        $customer = Customer::factory()->create();

        $payload = [
            'customer_id' => $customer->id,
            'sale_date' => now()->toDateTimeString(),
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3, 'price' => 100],
            ],
        ];

        $response = $this->actingAs($user)->post(route('sales.store'), $payload);

        $response->assertRedirect(route('sales.show', 1));
        $this->assertDatabaseHas('sales', ['total' => 300]);
        $this->assertDatabaseCount('sale_items', 1);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 7]);
    }

    public function test_sale_records_stock_movement(): void
    {
        $user = $this->vendedor();
        $product = Product::factory()->create([
            'category_id' => Category::factory(),
            'price' => 50,
            'stock' => 20,
        ]);

        $payload = [
            'customer_id' => null,
            'sale_date' => now()->toDateTimeString(),
            'items' => [
                ['product_id' => $product->id, 'quantity' => 4, 'price' => 50],
            ],
        ];

        $this->actingAs($user)->post(route('sales.store'), $payload);

        $saleId = \App\Models\Sale::query()->latest('id')->value('id');

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => StockMovementType::Out->value,
            'quantity' => 4,
            'reference_type' => \App\Models\Sale::class,
            'reference_id' => $saleId,
            'user_id' => $user->id,
        ]);
    }

    public function test_sale_fails_when_quantity_exceeds_stock(): void
    {
        $user = $this->vendedor();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 50,
            'stock' => 2,
        ]);

        $payload = [
            'customer_id' => null,
            'sale_date' => now()->toDateTimeString(),
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5, 'price' => 50],
            ],
        ];

        $response = $this->actingAs($user)->post(route('sales.store'), $payload);

        $response->assertSessionHasErrors('items');
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('sale_items', 0);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 2]);
    }

    public function test_sale_fails_when_required_fields_missing(): void
    {
        $user = $this->vendedor();

        $response = $this->actingAs($user)->post(route('sales.store'), [
            'items' => [],
        ]);

        $response->assertSessionHasErrors(['sale_date', 'items']);
    }

    public function test_sale_works_without_customer(): void
    {
        $user = $this->vendedor();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 20,
            'stock' => 5,
        ]);

        $response = $this->actingAs($user)->post(route('sales.store'), [
            'customer_id' => null,
            'sale_date' => now()->toDateTimeString(),
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2, 'price' => 20],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('sales', ['customer_id' => null, 'total' => 40]);
    }
}
