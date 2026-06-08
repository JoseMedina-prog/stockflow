<?php

namespace App\Enums;

enum OpportunityStage: string
{
    case Prospecting = 'prospecting';
    case Qualification = 'qualification';
    case Proposal = 'proposal';
    case Negotiation = 'negotiation';
    case ClosedWon = 'closed_won';
    case ClosedLost = 'closed_lost';

    public function label(): string
    {
        return match ($this) {
            self::Prospecting => 'Prospección',
            self::Qualification => 'Calificación',
            self::Proposal => 'Propuesta',
            self::Negotiation => 'Negociación',
            self::ClosedWon => 'Ganado',
            self::ClosedLost => 'Perdido',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Prospecting => 'Prospecto',
            self::Qualification => 'Calificado',
            self::Proposal => 'Propuesta',
            self::Negotiation => 'Negociación',
            self::ClosedWon => 'Ganado',
            self::ClosedLost => 'Perdido',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Prospecting => 'secondary',
            self::Qualification => 'info',
            self::Proposal => 'warning',
            self::Negotiation => 'warning',
            self::ClosedWon => 'success',
            self::ClosedLost => 'destructive',
        };
    }

    public function isOpen(): bool
    {
        return ! in_array($this, [self::ClosedWon, self::ClosedLost], true);
    }

    public function isFinal(): bool
    {
        return ! $this->isOpen();
    }

    public function isWon(): bool
    {
        return $this === self::ClosedWon;
    }

    public function isLost(): bool
    {
        return $this === self::ClosedLost;
    }

    public function canBeEdited(): bool
    {
        return $this->isOpen();
    }

    /**
     * Stages the user can advance to from the current one.
     *
     * @return array<int, self>
     */
    public function nextStages(): array
    {
        if (! $this->isOpen()) {
            return [];
        }

        return match ($this) {
            self::Prospecting => [self::Qualification, self::ClosedLost],
            self::Qualification => [self::Proposal, self::ClosedLost],
            self::Proposal => [self::Negotiation, self::ClosedLost],
            self::Negotiation => [self::ClosedWon, self::ClosedLost],
        };
    }

    public function isValidTransition(self $target): bool
    {
        foreach ($this->nextStages() as $next) {
            if ($next === $target) {
                return true;
            }
        }

        return false;
    }
}
