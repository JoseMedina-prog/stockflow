<?php

namespace App\Enums;

enum TaxType: string
{
    case IvaTrasladado = 'iva_trasladado';
    case IvaAcreditable = 'iva_acreditable';
    case Ieps = 'ieps';
    case Isr = 'isr';
    case RetencionIva = 'retencion_iva';
    case RetencionIsr = 'retencion_isr';
    case Otro = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::IvaTrasladado => 'IVA Trasladado',
            self::IvaAcreditable => 'IVA Acreditable',
            self::Ieps => 'IEPS',
            self::Isr => 'ISR',
            self::RetencionIva => 'Retención de IVA',
            self::RetencionIsr => 'Retención de ISR',
            self::Otro => 'Otro',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::IvaTrasladado => 'IVA T.',
            self::IvaAcreditable => 'IVA A.',
            self::Ieps => 'IEPS',
            self::Isr => 'ISR',
            self::RetencionIva => 'Ret. IVA',
            self::RetencionIsr => 'Ret. ISR',
            self::Otro => 'Otro',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::IvaTrasladado, self::RetencionIva => 'info',
            self::IvaAcreditable => 'success',
            self::Ieps => 'warning',
            self::Isr, self::RetencionIsr => 'destructive',
            self::Otro => 'secondary',
        };
    }

    public function isSales(): bool
    {
        return in_array($this, [self::IvaTrasladado, self::RetencionIva, self::Ieps], true);
    }

    public function isPurchase(): bool
    {
        return in_array($this, [self::IvaAcreditable, self::Isr, self::Ieps, self::RetencionIsr], true);
    }
}
