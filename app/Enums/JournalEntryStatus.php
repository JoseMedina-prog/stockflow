<?php

namespace App\Enums;

enum JournalEntryStatus: string
{
    case Draft = 'draft';
    case Posted = 'posted';
    case Voided = 'voided';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Posted => 'Aplicado',
            self::Voided => 'Cancelado',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Draft => 'secondary',
            self::Posted => 'success',
            self::Voided => 'destructive',
        };
    }

    public function isPosted(): bool
    {
        return $this === self::Posted;
    }
}
