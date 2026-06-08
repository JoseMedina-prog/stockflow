<?php

namespace App\Services;

use App\Enums\JournalEntryStatus;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\Tax;
use App\Models\User;
use App\Support\FolioGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PostingService
{
    public function __construct(private readonly FolioGenerator $folios = new FolioGenerator()) {}

    /**
     * Resolve a system account by code, throwing if it doesn't exist.
     */
    public function account(string $code): Account
    {
        return Account::where('code', $code)->firstOrFail();
    }

    /**
     * Try to resolve an account; returns null if it doesn't exist
     * (e.g. the chart of accounts hasn't been seeded yet).
     */
    public function accountNullable(string $code): ?Account
    {
        try {
            return $this->account($code);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return null;
        }
    }

    public function isAvailable(): bool
    {
        return Account::query()->exists();
    }

    public function accountOptional(string $code): ?Account
    {
        return Account::where('code', $code)->first();
    }

    /**
     * Post a journal entry from an explicit list of lines.
     *
     * Each line is [account_code, debit, credit, description?].
     */
    public function post(
        string $concept,
        Model $source,
        User $user,
        array $lines,
        ?string $entryDate = null,
        ?string $reference = null,
    ): JournalEntry {
        return DB::transaction(function () use ($concept, $source, $user, $lines, $entryDate, $reference) {
            $entry = JournalEntry::create([
                'folio' => $this->folios->nextJournalFolio(),
                'entry_date' => $entryDate ?? now()->toDateString(),
                'concept' => $concept,
                'reference' => $reference,
                'source_type' => $source->getMorphClass(),
                'source_id' => $source->getKey(),
                'status' => JournalEntryStatus::Posted->value,
                'posted_by' => $user->id,
                'posted_at' => now(),
                'total_debit' => 0,
                'total_credit' => 0,
            ]);

            $sort = 0;
            $totalDebit = 0;
            $totalCredit = 0;
            foreach ($lines as $line) {
                [$code, $debit, $credit, $description] = $this->normalizeLine($line);
                $account = $this->account($code);

                $debit = round((float) $debit, 2);
                $credit = round((float) $credit, 2);

                JournalLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $account->id,
                    'description' => $description,
                    'debit' => $debit,
                    'credit' => $credit,
                    'sort' => $sort++,
                ]);

                $totalDebit += $debit;
                $totalCredit += $credit;
            }

            $entry->update([
                'total_debit' => round($totalDebit, 2),
                'total_credit' => round($totalCredit, 2),
            ]);

            return $entry;
        });
    }

    /**
     * Post a sale as:
     *   - Debit  Caja o Clientes           (total)
     *   - Credit Ventas                    (subtotal sin impuestos)
     *   - Credit IVA Trasladado            (taxes)
     */
    public function postSale(Sale $sale, User $user): ?JournalEntry
    {
        if ($this->alreadyPosted($sale)) {
            return null;
        }

        if (! $this->isAvailable()) {
            return null;
        }

        $subtotal = (float) $sale->items()->sum('subtotal');
        $taxes = (float) $sale->items()->sum('tax_amount');

        $total = round($subtotal + $taxes, 2);

        if ($total <= 0) {
            return null;
        }

        $lines = [];

        $debitAccount = $sale->balance > 0 ? '1103' : '1101';
        $lines[] = [$debitAccount, $total, 0, "Venta #{$sale->id}"];

        if ($subtotal > 0) {
            $lines[] = ['4101', 0, $subtotal, 'Ingreso por ventas'];
        }

        if ($taxes > 0) {
            $lines[] = ['2103', 0, $taxes, 'IVA Trasladado'];
        }

        return $this->post(
            concept: "Venta #{$sale->id}",
            source: $sale,
            user: $user,
            lines: $lines,
            entryDate: $sale->sale_date->toDateString(),
            reference: 'V-'.str_pad((string) $sale->id, 6, '0', STR_PAD_LEFT),
        );
    }

    /**
     * Post a purchase (received) as:
     *   - Debit  Inventario + IVA Acreditable
     *   - Credit Proveedores o Caja/Bancos
     */
    public function postPurchase(Purchase $purchase, User $user): ?JournalEntry
    {
        if ($this->alreadyPosted($purchase)) {
            return null;
        }

        if (! $this->isAvailable()) {
            return null;
        }

        $subtotal = (float) $purchase->items()->sum('subtotal');
        $taxes = (float) $purchase->items()->sum('tax_amount');
        $total = round($subtotal + $taxes, 2);

        if ($total <= 0) {
            return null;
        }

        $lines = [];

        if ($subtotal > 0) {
            $lines[] = ['5102', $subtotal, 0, "Compra {$purchase->folio}"];
        }

        if ($taxes > 0) {
            $lines[] = ['1105', $taxes, 0, 'IVA Acreditable'];
        }

        $creditAccount = $purchase->balance > 0 ? '2101' : '1101';
        $lines[] = [$creditAccount, 0, $total, "Compra {$purchase->folio}"];

        return $this->post(
            concept: "Compra {$purchase->folio}",
            source: $purchase,
            user: $user,
            lines: $lines,
            entryDate: $purchase->purchase_date->toDateString(),
            reference: $purchase->folio,
        );
    }

    /**
     * Post a customer payment as:
     *   - Debit  Caja
     *   - Credit Clientes
     */
    public function postPayment(Payment $payment, User $user): ?JournalEntry
    {
        if ($this->alreadyPosted($payment)) {
            return null;
        }

        if (! $this->isAvailable()) {
            return null;
        }

        $amount = round((float) $payment->amount, 2);
        if ($amount <= 0) {
            return null;
        }

        $isSalePayment = $payment->payable_type === Sale::class;

        $lines = [
            ['1101', $amount, 0, "Pago {$payment->folio}"],
            [$isSalePayment ? '1103' : '2101', 0, $amount, "Aplicación de pago {$payment->folio}"],
        ];

        return $this->post(
            concept: "Pago {$payment->folio}",
            source: $payment,
            user: $user,
            lines: $lines,
            entryDate: $payment->paid_at->toDateString(),
            reference: $payment->folio,
        );
    }

    /**
     * Post a sale return refund as:
     *   - Debit  Devoluciones sobre Ventas + IVA Trasladado (reverso)
     *   - Credit Caja (si reembolso en efectivo) o Notas de Crédito Emitidas
     */
    public function postReturn(SaleReturn $return, User $user): ?JournalEntry
    {
        if ($this->alreadyPosted($return)) {
            return null;
        }

        if ($return->status->value !== 'approved') {
            return null;
        }

        if (! $this->isAvailable()) {
            return null;
        }

        $subtotal = (float) $return->subtotal;
        $taxes = (float) $return->tax;
        $total = (float) $return->total;

        if ($total <= 0) {
            return null;
        }

        $lines = [];

        if ($subtotal > 0) {
            $lines[] = ['4102', $subtotal, 0, "Devolución {$return->folio}"];
        }
        if ($taxes > 0) {
            $lines[] = ['2103', 0, $taxes, 'Reverso IVA Trasladado'];
        }

        $creditAccount = $return->refund_method->value === 'credit_note' ? '2106' : '1101';
        $lines[] = [$creditAccount, 0, $total, "Reembolso devolución {$return->folio}"];

        return $this->post(
            concept: "Devolución {$return->folio}",
            source: $return,
            user: $user,
            lines: $lines,
            entryDate: $return->approved_at?->toDateString() ?? $return->return_date->toDateString(),
            reference: $return->folio,
        );
    }

    private function alreadyPosted(Model $source): bool
    {
        return JournalEntry::where('source_type', $source->getMorphClass())
            ->where('source_id', $source->getKey())
            ->where('status', JournalEntryStatus::Posted->value)
            ->exists();
    }

    /**
     * Normalize a line into [code, debit, credit, description].
     */
    private function normalizeLine(array $line): array
    {
        if (count($line) === 4) {
            return $line;
        }
        if (count($line) === 3) {
            return [$line[0], $line[1], $line[2], null];
        }
        if (count($line) === 2) {
            return [$line[0], $line[1], 0, null];
        }

        throw new \InvalidArgumentException('Invalid journal line format. Expected [code, debit, credit, description?].');
    }
}
