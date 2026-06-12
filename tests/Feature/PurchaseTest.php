<?php

namespace Tests\Feature;

use App\Enums\PurchaseStatus;
use App\Enums\StockMovementType;
use App\Models\Category;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\PurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseTest extends TestCase
{
    use RefreshDatabase;

    private function makeSupplier(): Supplier
    {
        return Supplier::factory()->create();
    }

    private function makeProduct(int $stock = 10, float $price = 50): Product
    {
        return Product::factory()->create([
            'category_id' => Category::factory(),
            'stock' => $stock,
            'price' => $price,
        ]);
    }

    public function test_purchase_index_page_renders(): void
    {
        $this->actingAsAdmin()
            ->get(route('purchases.index'))
            ->assertOk();
    }

    public function test_purchase_create_page_renders(): void
    {
        $this->actingAsAdmin()
            ->get(route('purchases.create'))
            ->assertOk();
    }

    public function test_purchase_can_be_created_with_immediate_receipt(): void
    {
        $supplier = $this->makeSupplier();
        $product = $this->makeProduct(stock: 5, price: 100);
        $user = $this->comprador();

        $payload = [
            'supplier_id' => $supplier->id,
            'purchase_date' => now()->toDateString(),
            'receive_immediately' => true,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 10, 'unit_cost' => 80],
            ],
        ];

        $response = $this->actingAs($user)->post(route('purchases.store'), $payload);

        $response->assertRedirect();
        $purchase = Purchase::query()->latest('id')->first();

        $this->assertNotNull($purchase);
        $this->assertSame(PurchaseStatus::Received, $purchase->status);
        $this->assertSame($supplier->id, $purchase->supplier_id);
        $this->assertSame(800.0, (float) $purchase->subtotal);
        $this->assertSame(800.0, (float) $purchase->total);
        $this->assertDatabaseCount('purchase_items', 1);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 15]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => StockMovementType::In->value,
            'quantity' => 10,
            'reference_type' => Purchase::class,
            'reference_id' => $purchase->id,
        ]);
    }

    public function test_purchase_can_be_created_as_pending(): void
    {
        $supplier = $this->makeSupplier();
        $product = $this->makeProduct(stock: 5);

        $payload = [
            'supplier_id' => $supplier->id,
            'purchase_date' => now()->toDateString(),
            'receive_immediately' => false,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3, 'unit_cost' => 50],
            ],
        ];

        $this->actingAsAdmin()->post(route('purchases.store'), $payload);

        $purchase = Purchase::query()->latest('id')->first();
        $this->assertSame(PurchaseStatus::Pending, $purchase->status);
        $this->assertNull($purchase->received_at);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 5]);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_pending_purchase_can_be_received_later(): void
    {
        $supplier = $this->makeSupplier();
        $product = $this->makeProduct(stock: 0);
        $user = $this->comprador();

        $purchase = $this->app->make(PurchaseService::class)->create(
            supplier: $supplier,
            user: $user,
            purchaseDate: now(),
            items: [['product_id' => $product->id, 'quantity' => 25, 'unit_cost' => 50]],
            receiveImmediately: false,
        );

        $this->assertSame(PurchaseStatus::Pending, $purchase->status);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 0]);

        $this->actingAs($user)->post(route('purchases.receive', $purchase));

        $purchase->refresh();
        $this->assertSame(PurchaseStatus::Received, $purchase->status);
        $this->assertNotNull($purchase->received_at);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 25]);
    }

    public function test_cancelling_received_purchase_reverses_stock(): void
    {
        $supplier = $this->makeSupplier();
        $product = $this->makeProduct(stock: 10);
        $user = $this->comprador();

        $purchase = $this->app->make(PurchaseService::class)->create(
            supplier: $supplier,
            user: $user,
            purchaseDate: now(),
            items: [['product_id' => $product->id, 'quantity' => 5, 'unit_cost' => 50]],
        );

        $this->assertSame(15, $product->fresh()->stock);

        $this->actingAs($user)->post(route('purchases.cancel', $purchase));

        $purchase->refresh();
        $this->assertSame(PurchaseStatus::Cancelled, $purchase->status);
        $this->assertSame(10, $product->fresh()->stock);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => StockMovementType::Out->value,
            'quantity' => 5,
        ]);
    }

    public function test_cancelling_pending_purchase_does_not_affect_stock(): void
    {
        $supplier = $this->makeSupplier();
        $product = $this->makeProduct(stock: 10);

        $purchase = $this->app->make(PurchaseService::class)->create(
            supplier: $supplier,
            user: $this->comprador(),
            purchaseDate: now(),
            items: [['product_id' => $product->id, 'quantity' => 5, 'unit_cost' => 50]],
            receiveImmediately: false,
        );

        $this->actingAsAdmin()->post(route('purchases.cancel', $purchase));

        $this->assertSame(PurchaseStatus::Cancelled, $purchase->fresh()->status);
        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_purchase_items_required(): void
    {
        $supplier = $this->makeSupplier();

        $this->actingAsAdmin()
            ->post(route('purchases.store'), [
                'supplier_id' => $supplier->id,
                'purchase_date' => now()->toDateString(),
                'items' => [],
            ])
            ->assertSessionHasErrors('items');
    }

    public function test_purchase_supplier_must_exist(): void
    {
        $this->actingAsAdmin()
            ->post(route('purchases.store'), [
                'supplier_id' => 99999,
                'purchase_date' => now()->toDateString(),
                'items' => [
                    ['product_id' => 1, 'quantity' => 1, 'unit_cost' => 10],
                ],
            ])
            ->assertSessionHasErrors('supplier_id');
    }

    public function test_vendedor_cannot_access_purchases(): void
    {
        $vendedor = $this->vendedor();
        $this->assertFalse($vendedor->can('purchases.view_any'));
        $this->assertFalse($vendedor->can('purchases.create'));

        $this->actingAs($vendedor)->get(route('purchases.index'))->assertForbidden();
        $this->actingAs($vendedor)->get(route('purchases.create'))->assertForbidden();
    }

    public function test_comprador_can_create_purchase(): void
    {
        $supplier = $this->makeSupplier();
        $product = $this->makeProduct();

        $this->actingAsRole('comprador')
            ->post(route('purchases.store'), [
                'supplier_id' => $supplier->id,
                'purchase_date' => now()->toDateString(),
                'receive_immediately' => true,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 5, 'unit_cost' => 30],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('purchases', 1);
    }

    public function test_supplier_with_purchases_cannot_be_deleted(): void
    {
        $supplier = $this->makeSupplier();
        $product = $this->makeProduct();

        $this->app->make(PurchaseService::class)->create(
            supplier: $supplier,
            user: $this->comprador(),
            purchaseDate: now(),
            items: [['product_id' => $product->id, 'quantity' => 1, 'unit_cost' => 10]],
        );

        $this->actingAsAdmin()
            ->delete(route('suppliers.destroy', $supplier))
            ->assertRedirect(route('suppliers.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
    }

    public function test_folio_generator_creates_unique_folios(): void
    {
        $supplier = $this->makeSupplier();
        $product = $this->makeProduct();

        for ($i = 0; $i < 3; $i++) {
            $this->app->make(PurchaseService::class)->create(
                supplier: $supplier,
                user: $this->comprador(),
                purchaseDate: now(),
                items: [['product_id' => $product->id, 'quantity' => 1, 'unit_cost' => 10]],
            );
        }

        $folios = Purchase::query()->pluck('folio')->all();
        $this->assertCount(3, array_unique($folios));
    }

    public function test_folio_format_is_c_dash_six_digits(): void
    {
        $supplier = $this->makeSupplier();
        $product = $this->makeProduct();

        $purchase = $this->app->make(PurchaseService::class)->create(
            supplier: $supplier,
            user: $this->comprador(),
            purchaseDate: now(),
            items: [['product_id' => $product->id, 'quantity' => 1, 'unit_cost' => 10]],
        );

        $this->assertMatchesRegularExpression('/^C-\d{6}$/', $purchase->folio);
    }

    public function test_index_filter_by_status(): void
    {
        $supplier = $this->makeSupplier();
        Purchase::factory()->count(2)->received()->create(['supplier_id' => $supplier->id, 'user_id' => $this->admin()->id]);
        Purchase::factory()->count(1)->create(['supplier_id' => $supplier->id, 'user_id' => $this->admin()->id]);

        $this->actingAsAdmin()
            ->get(route('purchases.index', ['status' => 'pending']))
            ->assertInertia(fn ($page) => $page->has('purchases.data', 1));
    }
}
