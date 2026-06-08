<?php

namespace App\Enums;

enum LeadStage: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Qualified = 'qualified';
    case Proposal = 'proposal';
    case Won = 'won';
    case Lost = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Nuevo',
            self::Contacted => 'Contactado',
            self::Qualified => 'Calificado',
            self::Proposal => 'Propuesta',
            self::Won => 'Ganado',
            self::Lost => 'Perdido',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::New => 'Nuevo',
            self::Contacted => 'Contactado',
            self::Qualified => 'Calificado',
            self::Proposal => 'Propuesta',
            self::Won => 'Ganado',
            self::Lost => 'Perdido',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::New => 'secondary',
            self::Contacted => 'info',
            self::Qualified => 'warning',
            self::Proposal => 'warning',
            self::Won => 'success',
            self::Lost => 'destructive',
        };
    }

    public function isOpen(): bool
    {
        return ! in_array($this, [self::Won, self::Lost], true);
    }

    public function isFinal(): bool
    {
        return ! $this->isOpen();
    }

    public function canBeEdited(): bool
    {
        return $this->isOpen();
    }
}
