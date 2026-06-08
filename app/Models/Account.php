<?php

namespace App\Models;

use App\Enums\AccountNormalBalance;
use App\Enums\AccountType;
use Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    /** @use HasFactory<AccountFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'normal_balance',
        'parent_id',
        'category',
        'description',
        'is_active',
        'is_system',
        'sort',
    ];

    protected function casts(): array
    {
        return [
            'type' => AccountType::class,
            'normal_balance' => AccountNormalBalance::class,
            'is_active' => 'boolean',
            'is_system' => 'boolean',
            'sort' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function taxes(): HasMany
    {
        return $this->hasMany(Tax::class);
    }

    /**
     * @param  Builder<Account>  $query
     * @return Builder<Account>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<Account>  $query
     * @return Builder<Account>
     */
    public function scopeOfType(Builder $query, AccountType $type): Builder
    {
        return $query->where('type', $type->value);
    }

    /**
     * Compute the current account balance from posted journal lines.
     * Normal balance is the side that increases the balance.
     */
    public function balance(?string $from = null, ?string $to = null): float
    {
        $query = $this->journalLines()
            ->whereHas('journalEntry', fn ($q) => $q->where('status', 'posted'));

        if ($from) {
            $query->whereHas('journalEntry', fn ($q) => $q->where('entry_date', '>=', $from));
        }
        if ($to) {
            $query->whereHas('journalEntry', fn ($q) => $q->where('entry_date', '<=', $to));
        }

        $totals = $query
            ->selectRaw('SUM(debit) as debit_sum, SUM(credit) as credit_sum')
            ->first();

        $debit = (float) ($totals->debit_sum ?? 0);
        $credit = (float) ($totals->credit_sum ?? 0);

        return $this->normal_balance === AccountNormalBalance::Debit
            ? round($debit - $credit, 2)
            : round($credit - $debit, 2);
    }
}
