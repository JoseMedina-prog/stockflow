<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\User;
use App\Support\FolioGenerator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(private readonly PostingService $posting) {}

    /**
     * Register a payment against a sale or purchase.
     * The amount must not exceed the document's outstanding balance.
     */
    public function register(
        Sale|Purchase $payable,
        float $amount,
        PaymentMethod $method,
        User $user,
        ?Carbon $paidAt = null,
        ?string $reference = null,
        ?string $notes = null,
    ): Payment {
        $payment = DB::transaction(function () use ($payable, $amount, $method, $user, $paidAt, $reference, $notes) {
            $locked = $payable::query()
                ->lockForUpdate()
                ->findOrFail($payable->id);

            if ($amount <= 0) {
                throw new \InvalidArgumentException('El monto debe ser mayor a cero.');
            }

            $balance = (float) $locked->balance;
            if ($amount > $balance + 0.001) {
                throw new \InvalidArgumentException(
                    "El monto ({$amount}) supera el saldo pendiente ({$balance})."
                );
            }

            $newPaid = round((float) $locked->paid_amount + $amount, 2);
            $newBalance = round((float) $locked->total - $newPaid, 2);

            $locked->update([
                'paid_amount' => $newPaid,
                'balance' => $newBalance,
            ]);

            return Payment::create([
                'folio' => FolioGenerator::nextPaymentFolio(),
                'payable_type' => $locked->getMorphClass(),
                'payable_id' => $locked->id,
                'method' => $method,
                'amount' => $amount,
                'reference' => $reference,
                'paid_at' => $paidAt ?? now(),
                'user_id' => $user->id,
                'notes' => $notes,
            ]);
        });

        $this->posting->postPayment($payment, $user);

        return $payment;
    }

    /**
     * Reverse a payment: subtract it from the document's paid_amount,
     * restore the balance, and delete the payment record.
     */
    public function void(Payment $payment, User $user): void
    {
        DB::transaction(function () use ($payment, $user) {
            $payableClass = $payment->payable_type;
            $locked = $payableClass::query()
                ->lockForUpdate()
                ->findOrFail($payment->payable_id);

            $newPaid = round((float) $locked->paid_amount - (float) $payment->amount, 2);
            $newBalance = round((float) $locked->total - $newPaid, 2);

            $locked->update([
                'paid_amount' => $newPaid,
                'balance' => $newBalance,
            ]);

            $payment->delete();
        });
    }
}
