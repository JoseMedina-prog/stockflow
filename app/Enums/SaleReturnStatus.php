<?php

namespace App\Enums;

enum SaleReturnStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Approved => 'Aprobada',
            self::Rejected => 'Rechazada',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Rejected => 'destructive',
        };
    }

    public function canApprove(): bool
    {
        return $this === self::Pending;
    }

    public function canReject(): bool
    {
        return $this === self::Pending;
    }

    public function affectsStock(): bool
    {
        return $this === self::Approved;
    }
}
