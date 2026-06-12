<?php

namespace Database\Seeders;

use App\Enums\RefundMethod;
use App\Enums\SaleReturnStatus;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SaleReturnSeeder extends Seeder
{
    public function run(): void
    {
        $sales = Sale::with('items')->get();

        if ($sales->isEmpty()) {
            $this->command?->warn('No hay ventas para asociar devoluciones. Ejecuta primero DatabaseSeeder.');

            return;
        }

        $admin = User::where('email', 'admin@stockflow.test')->first()
            ?? User::role('admin')->first()
            ?? User::first();

        if (! $admin) {
            $this->command?->warn('No hay usuarios disponibles para asignar devoluciones.');

            return;
        }

        $specs = [
            [
                'status' => SaleReturnStatus::Pending,
                'refund_method' => null,
                'reason' => 'Cliente solicita devolución por defecto de fábrica en una unidad.',
                'notes' => null,
            ],
            [
                'status' => SaleReturnStatus::Pending,
                'refund_method' => null,
                'reason' => 'Producto equivocado, se entregó SKU distinto al pedido.',
                'notes' => 'Pendiente de revisión con almacén.',
            ],
            [
                'status' => SaleReturnStatus::Approved,
                'refund_method' => RefundMethod::Cash,
                'reason' => 'Empaque dañado, cliente no acepta el producto.',
                'notes' => 'Reembolso entregado en caja.',
            ],
            [
                'status' => SaleReturnStatus::Approved,
                'refund_method' => RefundMethod::CreditNote,
                'reason' => 'Devolución parcial por cancelación de pedido.',
                'notes' => 'Nota de crédito generada para aplicar a próxima compra.',
            ],
            [
                'status' => SaleReturnStatus::Approved,
                'refund_method' => RefundMethod::OriginalPayment,
                'reason' => 'Cliente cambió de opinión dentro del período de garantía.',
                'notes' => 'Reembolso al mismo método de pago original.',
            ],
            [
                'status' => SaleReturnStatus::Rejected,
                'refund_method' => null,
                'reason' => 'Solicitud fuera del período permitido para devoluciones.',
                'notes' => null,
            ],
        ];

        $folioCounter = (int) (SaleReturn::withTrashed()->max('id') ?? 0) + 1;

        DB::transaction(function () use ($specs, $sales, $admin, &$folioCounter) {
            foreach ($specs as $index => $spec) {
                $sale = $sales->get($index % $sales->count());
                $items = $sale->items;

                if ($items->isEmpty()) {
                    continue;
                }

                $saleReturn = SaleReturn::create([
                    'folio' => 'D-'.str_pad((string) $folioCounter++, 6, '0', STR_PAD_LEFT),
                    'sale_id' => $sale->id,
                    'customer_id' => $sale->customer_id,
                    'user_id' => $admin->id,
                    'subtotal' => 0,
                    'tax' => 0,
                    'total' => 0,
                    'reason' => $spec['reason'],
                    'status' => $spec['status'],
                    'refund_method' => $spec['refund_method'],
                    'notes' => $spec['notes'],
                ]);

                $this->applyStatusTimestamps($saleReturn, $spec['status'], $admin);

                $selectedItems = $this->pickReturnItems($items, $index);
                $this->attachReturnItems($saleReturn, $selectedItems, (float) ($sale->items->first()->tax_rate ?? 0));
            }
        });
    }

    /**
     * @param  Collection<int, SaleItem>  $items
     * @return Collection<int, SaleItem>
     */
    protected function pickReturnItems(Collection $items, int $seed): Collection
    {
        $take = match ($seed % 3) {
            0 => 1,
            1 => min(2, $items->count()),
            default => min(3, $items->count()),
        };

        return $items->shuffle()->take($take)->values();
    }

    /**
     * @param  Collection<int, SaleItem>  $items
     */
    protected function attachReturnItems(SaleReturn $saleReturn, Collection $items, float $taxRate): void
    {
        $subtotal = 0.0;
        $taxTotal = 0.0;

        foreach ($items as $item) {
            $maxQty = max(1, (int) $item->quantity);
            $qty = max(1, min($maxQty, random_int(1, $maxQty)));
            $unitPrice = (float) $item->price;
            $lineSubtotal = round($unitPrice * $qty, 2);
            $lineTax = round($lineSubtotal * $taxRate, 2);

            SaleReturnItem::create([
                'sale_return_id' => $saleReturn->id,
                'sale_item_id' => $item->id,
                'product_id' => $item->product_id,
                'quantity_returned' => $qty,
                'unit_price' => $unitPrice,
                'tax_rate_snapshot' => $taxRate,
                'tax_amount' => $lineTax,
                'subtotal' => $lineSubtotal,
                'line_total' => round($lineSubtotal + $lineTax, 2),
            ]);

            $subtotal += $lineSubtotal;
            $taxTotal += $lineTax;
        }

        $saleReturn->update([
            'subtotal' => round($subtotal, 2),
            'tax' => round($taxTotal, 2),
            'total' => round($subtotal + $taxTotal, 2),
        ]);
    }

    protected function applyStatusTimestamps(SaleReturn $saleReturn, SaleReturnStatus $status, User $admin): void
    {
        if ($status === SaleReturnStatus::Approved) {
            $saleReturn->forceFill([
                'approved_at' => $saleReturn->created_at ?? now(),
                'approved_by' => $admin->id,
            ])->save();
        }

        if ($status === SaleReturnStatus::Rejected) {
            $saleReturn->forceFill([
                'rejected_at' => $saleReturn->created_at ?? now(),
                'rejection_reason' => 'Fuera del período permitido de devoluciones.',
            ])->save();
        }
    }
}
