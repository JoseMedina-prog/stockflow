<?php

namespace App\Enums;

enum RefundMethod: string
{
    case Cash = 'cash';
    case Card = 'card';
    case Transfer = 'transfer';
    case CreditNote = 'credit_note';
    case OriginalPayment = 'original_payment';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Efectivo',
            self::Card => 'Tarjeta',
            self::Transfer => 'Transferencia',
            self::CreditNote => 'Nota de crédito',
            self::OriginalPayment => 'Mismo método de pago',
        };
    }
}
