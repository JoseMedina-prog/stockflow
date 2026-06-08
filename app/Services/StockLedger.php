<?php

namespace App\Services;

use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StockLedger
{
    /**
     * Record a stock movement and update product stock atomically.
     *
     * @param  int  $signedQuantity  Positive for additions, negative for reductions.
     *                               The absolute value is persisted; the sign is implied by $type.
     *                               For 'out' movements, only the absolute value matters.
     */
    public function record(
        Product $product,
        int $signedQuantity,
        StockMovementType $type,
        ?string $reason = null,
        ?Model $reference = null,
        ?User $user = null,
        ?Carbon $occurredAt = null,
        ?string $notes = null,
    ): StockMovement {
        if ($signedQuantity === 0) {
            throw new \InvalidArgumentException('La cantidad no puede ser cero.');
        }

        $absQuantity = abs($signedQuantity);
        $isOut = $type === StockMovementType::Out || $signedQuantity < 0;

        return DB::transaction(function () use ($product, $absQuantity, $type, $isOut, $reason, $reference, $user, $occurredAt, $notes) {
            $locked = Product::query()->lockForUpdate()->findOrFail($product->id);

            if ($isOut && $locked->stock < $absQuantity) {
                throw new InsufficientStockException($locked, $absQuantity, $locked->stock);
            }

            $delta = $isOut ? -$absQuantity : $absQuantity;

            $locked->increment('stock', $delta);

            return StockMovement::create([
                'product_id' => $locked->id,
                'type' => $type,
                'quantity' => $absQuantity,
                'reason' => $reason,
                'reference_type' => $reference ? $reference->getMorphClass() : null,
                'reference_id' => $reference?->getKey(),
                'user_id' => $user?->id,
                'notes' => $notes,
                'occurred_at' => $occurredAt ?? now(),
            ]);
        });
    }

    /**
     * Reverse a stock movement by recording a counter-movement.
     */
    public function reverse(
        StockMovement $movement,
        ?User $user = null,
        ?string $notes = null,
    ): StockMovement {
        $reverseType = $movement->type === StockMovementType::Out
            ? StockMovementType::In
            : StockMovementType::Out;

        $signed = $movement->type === StockMovementType::Out
            ? $movement->quantity
            : -$movement->quantity;

        return $this->record(
            product: $movement->product,
            signedQuantity: $signed,
            type: $reverseType,
            reason: "Reversión de movimiento #{$movement->id}",
            reference: $movement->reference,
            user: $user,
            notes: $notes,
        );
    }

    /**
     * Audit-only: recompute the stock of a product from all recorded movements.
     * Returns the computed stock and the current persisted value for drift detection.
     *
     * @return array{computed: int, stored: int, drift: int}
     */
    public function audit(Product $product): array
    {
        $computed = (int) StockMovement::query()
            ->where('product_id', $product->id)
            ->selectRaw("SUM(CASE WHEN type = 'in' THEN quantity WHEN type = 'out' THEN -quantity ELSE 0 END) AS net")
            ->value('net');

        $stored = (int) $product->fresh()->stock;

        return [
            'computed' => $computed,
            'stored' => $stored,
            'drift' => $stored - $computed,
        ];
    }
}
