<?php

namespace App\Enums;

enum AccountType: string
{
    case Asset = 'asset';
    case Liability = 'liability';
    case Equity = 'equity';
    case Revenue = 'revenue';
    case Expense = 'expense';

    public function label(): string
    {
        return match ($this) {
            self::Asset => 'Activo',
            self::Liability => 'Pasivo',
            self::Equity => 'Capital',
            self::Revenue => 'Ingreso',
            self::Expense => 'Egreso / Costo',
        };
    }

    public function normalBalance(): AccountNormalBalance
    {
        return match ($this) {
            self::Asset, self::Expense => AccountNormalBalance::Debit,
            self::Liability, self::Equity, self::Revenue => AccountNormalBalance::Credit,
        };
    }

    public function isBalanceSheet(): bool
    {
        return in_array($this, [self::Asset, self::Liability, self::Equity], true);
    }

    public function isIncomeStatement(): bool
    {
        return in_array($this, [self::Revenue, self::Expense], true);
    }
}
