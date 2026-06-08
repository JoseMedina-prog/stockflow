<?php

namespace App\Enums;

enum PurchaseStatus: string
{
    case Pending = 'pending';
    case Received = 'received';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Received => 'Recibida',
            self::Cancelled => 'Cancelada',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Received => 'success',
            self::Cancelled => 'destructive',
        };
    }

    public function canEditItems(): bool
    {
        return $this === self::Pending;
    }

    public function canReceive(): bool
    {
        return $this === self::Pending;
    }

    public function canCancel(): bool
    {
        return $this === self::Pending || $this === self::Received;
    }

    public function canDelete(): bool
    {
        return $this === self::Pending;
    }

    public function affectsStock(): bool
    {
        return $this === self::Received;
    }
}
