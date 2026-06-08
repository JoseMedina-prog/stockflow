<?php

namespace App\Services;

use App\Enums\CreditNoteStatus;
use App\Enums\RefundMethod;
use App\Enums\SaleReturnStatus;
use App\Enums\StockMovementType;
use App\Models\CreditNote;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\User;
use App\Support\FolioGenerator;
use Illuminate\Support\Facades\DB;

class ReturnService
{
    public function __construct(
        private readonly StockLedger $ledger,
        private readonly PostingService $posting,
    ) {}

    /**
     * Create a pending return for a sale with the given items.
     *
     * @param  array<int, array{sale_item_id: int, quantity_returned: int}>  $items
     */
    public function create(
        Sale $sale,
        User $user,
        array $items,
        string $reason,
        ?string $notes = null,
    ): SaleReturn {
        return DB::transaction(function () use ($sale, $user, $items, $reason, $notes) {
            $prepared = $this->validateAndPrepare($sale, $items);

            $subtotal = array_sum(array_column($prepared, 'subtotal'));
            $tax = round(array_sum(array_column($prepared, 'tax_amount')), 2);
            $total = round($subtotal + $tax, 2);

            $saleReturn = SaleReturn::create([
                'folio' => FolioGenerator::nextReturnFolio(),
                'sale_id' => $sale->id,
                'customer_id' => $sale->customer_id,
                'user_id' => $user->id,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'reason' => $reason,
                'status' => SaleReturnStatus::Pending,
                'notes' => $notes,
            ]);

            foreach ($prepared as $row) {
                SaleReturnItem::create([
                    'sale_return_id' => $saleReturn->id,
                    'sale_item_id' => $row['sale_item']->id,
                    'product_id' => $row['sale_item']->product_id,
                    'quantity_returned' => $row['quantity'],
                    'unit_price' => $row['unit_price'],
                    'tax_rate_snapshot' => $row['tax_rate'],
                    'tax_amount' => $row['tax_amount'],
                    'subtotal' => $row['subtotal'],
                    'line_total' => round($row['subtotal'] + $row['tax_amount'], 2),
                ]);
            }

            return $saleReturn;
        });
    }

    /**
     * Approve a pending return: stock re-ingresa, optionally issue a credit note.
     */
    public function approve(
        SaleReturn $saleReturn,
        User $approver,
        RefundMethod $refundMethod,
        ?string $notes = null,
    ): SaleReturn {
        $return = DB::transaction(function () use ($saleReturn, $approver, $refundMethod, $notes) {
            $locked = SaleReturn::query()->lockForUpdate()->findOrFail($saleReturn->id);

            if (! $locked->status->canApprove()) {
                throw new \DomainException("La devolución {$locked->folio} no está en estado pendiente.");
            }

            $locked->update([
                'status' => SaleReturnStatus::Approved,
                'refund_method' => $refundMethod,
                'approved_at' => now(),
                'approved_by' => $approver->id,
                'notes' => $notes ? "{$locked->notes}\nAprobación: {$notes}" : $locked->notes,
            ]);

            $this->revertStock($locked, $approver);

            if ($refundMethod === RefundMethod::CreditNote && $locked->customer_id) {
                $this->issueCreditNote($locked);
            }

            return $locked->fresh();
        });

        $this->posting->postReturn($return, $approver);

        return $return;
    }

    /**
     * Reject a pending return without affecting stock.
     */
    public function reject(
        SaleReturn $saleReturn,
        User $approver,
        string $rejectionReason,
    ): SaleReturn {
        return DB::transaction(function () use ($saleReturn, $approver, $rejectionReason) {
            $locked = SaleReturn::query()->lockForUpdate()->findOrFail($saleReturn->id);

            if (! $locked->status->canReject()) {
                throw new \DomainException("La devolución {$locked->folio} no se puede rechazar.");
            }

            $locked->update([
                'status' => SaleReturnStatus::Rejected,
                'rejected_at' => now(),
                'rejection_reason' => $rejectionReason,
                'approved_by' => $approver->id,
            ]);

            return $locked->fresh();
        });
    }

    /**
     * @param  array<int, array{sale_item_id: int, quantity_returned: int}>  $items
     * @return array<int, array{sale_item: SaleItem, quantity: int, unit_price: float, subtotal: float, tax_rate: float, tax_amount: float}>
     */
    private function validateAndPrepare(Sale $sale, array $items): array
    {
        $prepared = [];

        foreach ($items as $row) {
            $saleItem = SaleItem::lockForUpdate()->find($row['sale_item_id']);

            if (! $saleItem || $saleItem->sale_id !== $sale->id) {
                throw new \InvalidArgumentException('Uno de los ítems no pertenece a la venta.');
            }

            $quantity = (int) $row['quantity_returned'];
            $returnable = $saleItem->returnableQuantity();

            if ($quantity < 1) {
                throw new \InvalidArgumentException('La cantidad a devolver debe ser al menos 1.');
            }

            if ($quantity > $returnable) {
                throw new \InvalidArgumentException(
                    "No se pueden devolver {$quantity} unidades del producto. Solo quedan {$returnable} disponibles para devolver."
                );
            }

            $unitPrice = (float) $saleItem->price;
            $subtotal = round($unitPrice * $quantity, 2);
            $taxRate = (float) $saleItem->tax_rate;
            $taxAmount = round($subtotal * $taxRate, 2);

            $prepared[] = [
                'sale_item' => $saleItem,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $subtotal,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
            ];
        }

        return $prepared;
    }

    private function revertStock(SaleReturn $saleReturn, User $user): void
    {
        foreach ($saleReturn->items()->with('product')->get() as $item) {
            $this->ledger->record(
                product: $item->product,
                signedQuantity: $item->quantity_returned,
                type: StockMovementType::In,
                reason: "Devolución {$saleReturn->folio}",
                reference: $saleReturn,
                user: $user,
            );
        }
    }

    private function issueCreditNote(SaleReturn $saleReturn): void
    {
        CreditNote::create([
            'folio' => FolioGenerator::nextCreditNoteFolio(),
            'customer_id' => $saleReturn->customer_id,
            'sale_return_id' => $saleReturn->id,
            'amount' => $saleReturn->total,
            'balance_remaining' => $saleReturn->total,
            'expires_at' => now()->addYear(),
            'status' => CreditNoteStatus::Active,
        ]);
    }
}
