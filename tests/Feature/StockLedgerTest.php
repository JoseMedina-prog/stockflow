<?php

namespace Tests\Feature;

use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\StockLedger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockLedgerTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(int $stock = 10): Product
    {
        return Product::factory()->create([
            'category_id' => Category::factory(),
            'stock' => $stock,
        ]);
    }

    public function test_in_movement_increments_stock_and_persists_movement(): void
    {
        $product = $this->makeProduct(stock: 5);
        $user = $this->admin();
        $ledger = app(StockLedger::class);

        $movement = $ledger->record(
            product: $product,
            signedQuantity: 7,
            type: StockMovementType::In,
            reason: 'Compra inicial',
            user: $user,
        );

        $this->assertSame(12, $product->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', [
            'id' => $movement->id,
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => 7,
            'reason' => 'Compra inicial',
            'user_id' => $user->id,
        ]);
    }

    public function test_out_movement_decrements_stock(): void
    {
        $product = $this->makeProduct(stock: 10);
        $ledger = app(StockLedger::class);

        $ledger->record(
            product: $product,
            signedQuantity: -3,
            type: StockMovementType::Out,
            reason: 'Venta #1',
        );

        $this->assertSame(7, $product->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'out',
            'quantity' => 3,
        ]);
    }

    public function test_out_movement_throws_when_stock_insufficient(): void
    {
        $product = $this->makeProduct(stock: 2);
        $ledger = app(StockLedger::class);

        $this->expectException(InsufficientStockException::class);

        try {
            $ledger->record(
                product: $product,
                signedQuantity: -5,
                type: StockMovementType::Out,
                reason: 'Venta #1',
            );
        } finally {
            $this->assertSame(2, $product->fresh()->stock);
            $this->assertDatabaseCount('stock_movements', 0);
        }
    }

    public function test_zero_quantity_throws_invalid_argument(): void
    {
        $product = $this->makeProduct();
        $ledger = app(StockLedger::class);

        $this->expectException(\InvalidArgumentException::class);

        $ledger->record(
            product: $product,
            signedQuantity: 0,
            type: StockMovementType::In,
        );
    }

    public function test_signed_quantity_helper_returns_correct_sign(): void
    {
        $in = StockMovement::factory()->incoming(5)->create();
        $out = StockMovement::factory()->outgoing(3)->create();

        $this->assertSame(5, $in->signedQuantity());
        $this->assertSame(-3, $out->signedQuantity());
    }

    public function test_reverse_creates_counter_movement(): void
    {
        $product = $this->makeProduct(stock: 10);
        $ledger = app(StockLedger::class);

        $original = $ledger->record(
            product: $product,
            signedQuantity: -4,
            type: StockMovementType::Out,
            reason: 'Venta #1',
        );

        $this->assertSame(6, $product->fresh()->stock);

        $ledger->reverse($original);

        $this->assertSame(10, $product->fresh()->stock);
        $this->assertDatabaseCount('stock_movements', 2);
    }

    public function test_audit_reports_no_drift_when_balanced(): void
    {
        $product = $this->makeProduct(stock: 0);
        $ledger = app(StockLedger::class);

        $ledger->record($product, 20, StockMovementType::In, 'Compra inicial');
        $ledger->record($product, -5, StockMovementType::Out, 'Venta #1');
        $ledger->record($product, -3, StockMovementType::Out, 'Venta #2');

        $audit = $ledger->audit($product->fresh());

        $this->assertSame(12, $audit['computed']);
        $this->assertSame(12, $audit['stored']);
        $this->assertSame(0, $audit['drift']);
    }

    public function test_audit_detects_drift_when_persisted_stock_out_of_sync(): void
    {
        $product = $this->makeProduct(stock: 0);
        $ledger = app(StockLedger::class);

        $ledger->record($product, 10, StockMovementType::In, 'Compra');
        $product->update(['stock' => 99]);

        $audit = $ledger->audit($product->fresh());

        $this->assertSame(10, $audit['computed']);
        $this->assertSame(99, $audit['stored']);
        $this->assertSame(89, $audit['drift']);
    }

    public function test_movement_records_polymorphic_reference(): void
    {
        $product = $this->makeProduct();
        $sale = \App\Models\Sale::factory()->create();
        $ledger = app(StockLedger::class);

        $movement = $ledger->record(
            product: $product,
            signedQuantity: 1,
            type: StockMovementType::Out,
            reason: 'Venta #'.$sale->id,
            reference: $sale,
        );

        $this->assertSame($sale->getMorphClass(), $movement->reference_type);
        $this->assertSame($sale->id, $movement->reference_id);
        $this->assertInstanceOf(\App\Models\Sale::class, $movement->reference);
    }

    public function test_stock_movement_index_page_renders(): void
    {
        $this->actingAsAdmin()
            ->get(route('stock-movements.index'))
            ->assertOk();
    }

    public function test_stock_movement_index_filter_by_type(): void
    {
        $product = $this->makeProduct();
        \App\Models\StockMovement::factory()->incoming(5)->create(['product_id' => $product->id]);
        \App\Models\StockMovement::factory()->outgoing(2)->create(['product_id' => $product->id]);

        $response = $this->actingAsAdmin()
            ->get(route('stock-movements.index', ['type' => 'in']))
            ->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->component('StockMovements/Index')
            ->has('movements.data', 1)
            ->where('movements.data.0.type', 'in'));
    }

    public function test_stock_movement_index_filter_by_product(): void
    {
        $product1 = $this->makeProduct();
        $product2 = $this->makeProduct();

        \App\Models\StockMovement::factory()->incoming(5)->create(['product_id' => $product1->id]);
        \App\Models\StockMovement::factory()->incoming(3)->create(['product_id' => $product2->id]);

        $this->actingAsAdmin()
            ->get(route('stock-movements.index', ['product_id' => $product1->id]))
            ->assertInertia(fn ($page) => $page
                ->has('movements.data', 1)
                ->where('movements.data.0.product.id', $product1->id));
    }

    public function test_user_without_permission_cannot_view_stock_movements(): void
    {
        $vendedor = $this->vendedor();
        $this->assertFalse($vendedor->can('stock_movements.view_any'));

        $this->actingAs($vendedor)
            ->get(route('stock-movements.index'))
            ->assertForbidden();
    }
}
