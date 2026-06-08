<?php

namespace App\Enums;

enum StockMovementType: string
{
    case In = 'in';
    case Out = 'out';
    case Adjustment = 'adjustment';
    case Transfer = 'transfer';

    public function label(): string
    {
        return match ($this) {
            self::In => 'Entrada',
            self::Out => 'Salida',
            self::Adjustment => 'Ajuste',
            self::Transfer => 'Transferencia',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::In => 'Entrada',
            self::Out => 'Salida',
            self::Adjustment => 'Ajuste',
            self::Transfer => 'Transf.',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::In => 'success',
            self::Out => 'destructive',
            self::Adjustment => 'warning',
            self::Transfer => 'info',
        };
    }

    public function isAddition(): bool
    {
        return $this === self::In || $this === self::Adjustment;
    }
}
