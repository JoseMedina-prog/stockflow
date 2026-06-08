<?php

namespace App\Enums;

enum CreditNoteStatus: string
{
    case Active = 'active';
    case Used = 'used';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activa',
            self::Used => 'Usada',
            self::Expired => 'Vencida',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Used => 'secondary',
            self::Expired => 'destructive',
        };
    }

    public function isUsable(): bool
    {
        return $this === self::Active && $this !== self::Expired;
    }
}
