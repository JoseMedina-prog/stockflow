<?php

namespace App\Enums;

enum QuoteStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Expired = 'expired';
    case Converted = 'converted';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Sent => 'Enviada',
            self::Accepted => 'Aceptada',
            self::Rejected => 'Rechazada',
            self::Expired => 'Vencida',
            self::Converted => 'Convertida',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Sent => 'Enviada',
            self::Accepted => 'Aceptada',
            self::Rejected => 'Rechazada',
            self::Expired => 'Vencida',
            self::Converted => 'Convertida',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Draft => 'secondary',
            self::Sent => 'info',
            self::Accepted => 'success',
            self::Rejected => 'destructive',
            self::Expired => 'destructive',
            self::Converted => 'success',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Draft, self::Sent], true);
    }

    public function isFinal(): bool
    {
        return ! $this->isOpen();
    }

    public function canBeEdited(): bool
    {
        return $this === self::Draft;
    }

    public function canBeSent(): bool
    {
        return $this === self::Draft;
    }

    public function canBeConverted(): bool
    {
        return $this === self::Accepted || $this === self::Sent;
    }
}
