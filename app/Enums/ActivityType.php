<?php

namespace App\Enums;

enum ActivityType: string
{
    case Call = 'call';
    case Email = 'email';
    case Meeting = 'meeting';
    case Note = 'note';
    case Message = 'message';
    case WhatsApp = 'whatsapp';

    public function label(): string
    {
        return match ($this) {
            self::Call => 'Llamada',
            self::Email => 'Correo',
            self::Meeting => 'Reunión',
            self::Note => 'Nota',
            self::Message => 'Mensaje',
            self::WhatsApp => 'WhatsApp',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Call => 'Llamada',
            self::Email => 'Email',
            self::Meeting => 'Reunión',
            self::Note => 'Nota',
            self::Message => 'Mensaje',
            self::WhatsApp => 'WhatsApp',
        };
    }

    public function iconName(): string
    {
        return match ($this) {
            self::Call => 'phone',
            self::Email => 'mail',
            self::Meeting => 'users',
            self::Note => 'file-text',
            self::Message => 'message-square',
            self::WhatsApp => 'message-circle',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Call => 'info',
            self::Email => 'secondary',
            self::Meeting => 'warning',
            self::Note => 'secondary',
            self::Message => 'info',
            self::WhatsApp => 'success',
        };
    }

    public function hasDuration(): bool
    {
        return in_array($this, [self::Call, self::Meeting], true);
    }
}
