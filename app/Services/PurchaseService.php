<?php

namespace App\Services;

use App\Enums\PurchaseStatus;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\Tax;
use App\Models\User;
use App\Support\FolioGenerator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function __construct(
        private readonly StockLedger $ledger,
        private readonly PostingService $posting,
    ) {}

    /**
     * Create a purchase with items. If $receiveImmediately, stock is incremented
     * and status is set to Received. Otherwise the purchase is Pending.
     *
     * @param  array<int, array{product_id: int, quantity: int, unit_cost: float|int|string, tax_id?: int|null}>  $items
     */
    public function create(
        Supplier $supplier,
        User $user,
        Carbon $purchaseDate,
        array $items,
        bool $receiveImmediately = true,
        ?string $notes = null,
    ): Purchase {
        $purchase = DB::transaction(function () use ($supplier, $user, $purchaseDate, $items, $receiveImmediately, $notes) {
            $prepared = $this->prepareItems($items);

            $subtotal = array_sum(array_column($prepared, 'subtotal'));
            $tax = round(array_sum(array_column($prepared, 'tax_amount')), 2);
            $total = round($subtotal + $tax, 2);

            $status = $receiveImmediately ? PurchaseStatus::Received : PurchaseStatus::Pending;

            $purchase = Purchase::create([
                'folio' => FolioGenerator::nextPurchaseFolio(),
                'supplier_id' => $supplier->id,
                'user_id' => $user->id,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'status' => $status,
                'purchase_date' => $purchaseDate,
                'received_at' => $receiveImmediately ? now() : null,
                'notes' => $notes,
            ]);

            foreach ($prepared as $row) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $row['product_id'],
                    'quantity' => $row['quantity'],
                    'unit_cost' => $row['unit_cost'],
                    'tax_rate_snapshot' => $row['tax_rate'],
                    'tax_amount' => $row['tax_amount'],
                    'tax_id' => $row['tax_id'],
                    'subtotal' => $row['subtotal'],
                    'line_total' => round($row['subtotal'] + $row['tax_amount'], 2),
                ]);
            }

            if ($receiveImmediately) {
                $this->recordIncoming($purchase, $user);
            }

            return $purchase;
        });

        if ($receiveImmediately) {
            $this->posting->postPurchase($purchase->fresh(), $user);
        }

        return $purchase;
    }

    /**
     * Mark a pending purchase as received: stock increments, status flips to Received.
     */
    public function receive(Purchase $purchase, User $user): Purchase
    {
        $purchase = DB::transaction(function () use ($purchase, $user) {
            $locked = Purchase::query()->lockForUpdate()->findOrFail($purchase->id);

            if (! $locked->status->canReceive()) {
                throw new \DomainException("La compra {$locked->folio} no está en estado pendiente.");
            }

            $locked->update([
                'status' => PurchaseStatus::Received,
                'received_at' => now(),
            ]);

            $this->recordIncoming($locked, $user);

            return $locked->fresh();
        });

        $this->posting->postPurchase($purchase->fresh(), $user);

        return $purchase;
    }

    /**
     * Cancel a purchase. If it was Received, the stock is reversed.
     */
    public function cancel(Purchase $purchase, User $user, ?string $notes = null): Purchase
    {
        return DB::transaction(function () use ($purchase, $user, $notes) {
            $locked = Purchase::query()->lockForUpdate()->findOrFail($purchase->id);

            if (! $locked->status->canCancel()) {
                throw new \DomainException("La compra {$locked->folio} ya está cancelada.");
            }

            $wasReceived = $locked->status->affectsStock();

            $locked->update([
                'status' => PurchaseStatus::Cancelled,
                'notes' => $notes ? "{$locked->notes}\nCancelación: {$notes}" : $locked->notes,
            ]);

            if ($wasReceived) {
                $this->recordOutgoing($locked, $user, $notes);
            }

            return $locked->fresh();
        });
    }

    /**
     * Update a pending purchase's items and metadata.
     *
     * @param  array<int, array{product_id: int, quantity: int, unit_cost: float|int|string, tax_id?: int|null}>  $items
     */
    public function update(
        Purchase $purchase,
        Supplier $supplier,
        Carbon $purchaseDate,
        array $items,
        ?string $notes = null,
    ): Purchase {
        return DB::transaction(function () use ($purchase, $supplier, $purchaseDate, $items, $notes) {
            $locked = Purchase::query()->lockForUpdate()->findOrFail($purchase->id);

            if (! $locked->status->canEditItems()) {
                throw new \DomainException("Solo se pueden editar compras en estado pendiente.");
            }

            $prepared = $this->prepareItems($items);

            $subtotal = array_sum(array_column($prepared, 'subtotal'));
            $tax = round(array_sum(array_column($prepared, 'tax_amount')), 2);

            $locked->update([
                'supplier_id' => $supplier->id,
                'purchase_date' => $purchaseDate,
                'notes' => $notes,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => round($subtotal + $tax, 2),
            ]);

            $locked->items()->delete();

            foreach ($prepared as $row) {
                PurchaseItem::create([
                    'purchase_id' => $locked->id,
                    'product_id' => $row['product_id'],
                    'quantity' => $row['quantity'],
                    'unit_cost' => $row['unit_cost'],
                    'tax_rate_snapshot' => $row['tax_rate'],
                    'tax_amount' => $row['tax_amount'],
                    'tax_id' => $row['tax_id'],
                    'subtotal' => $row['subtotal'],
                    'line_total' => round($row['subtotal'] + $row['tax_amount'], 2),
                ]);
            }

            return $locked->fresh();
        });
    }

    /**
     * @param  array<int, array{product_id: int, quantity: int, unit_cost: float|int|string, tax_id?: int|null}>  $items
     * @return array<int, array{product_id: int, quantity: int, unit_cost: float, subtotal: float, tax_id: int|null, tax_rate: float, tax_amount: float}>
     */
    private function prepareItems(array $items): array
    {
        $prepared = [];

        foreach ($items as $row) {
            $product = Product::lockForUpdate()->find($row['product_id']);

            if (! $product) {
                throw new \InvalidArgumentException("El producto #{$row['product_id']} no existe.");
            }

            $quantity = (int) $row['quantity'];
            $unitCost = round((float) $row['unit_cost'], 2);
            $subtotal = round($unitCost * $quantity, 2);

            $taxId = $row['tax_id'] ?? null;
            $taxRate = 0;
            $taxAmount = 0;
            if ($taxId) {
                $tax = Tax::find($taxId);
                if ($tax && $tax->is_active) {
                    $taxRate = (float) $tax->rate;
                    $taxAmount = round($subtotal * $taxRate, 2);
                }
            }

            $prepared[] = [
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'subtotal' => $subtotal,
                'tax_id' => $taxId,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
            ];
        }

        return $prepared;
    }

    private function recordIncoming(Purchase $purchase, User $user): void
    {
        foreach ($purchase->items()->with('product')->get() as $item) {
            $this->ledger->record(
                product: $item->product,
                signedQuantity: $item->quantity,
                type: StockMovementType::In,
                reason: "Compra {$purchase->folio}",
                reference: $purchase,
                user: $user,
                occurredAt: $purchase->received_at ?? $purchase->purchase_date,
            );
        }
    }

    private function recordOutgoing(Purchase $purchase, User $user, ?string $notes = null): void
    {
        foreach ($purchase->items()->with('product')->get() as $item) {
            $this->ledger->record(
                product: $item->product,
                signedQuantity: -$item->quantity,
                type: StockMovementType::Out,
                reason: "Cancelación de compra {$purchase->folio}",
                reference: $purchase,
                user: $user,
                notes: $notes,
            );
        }
    }
}
