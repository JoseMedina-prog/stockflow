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
use Illuminate\Support\Facades\DB;

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
        return self::balancesFor([$this->id], $from, $to)[$this->id] ?? 0.0;
    }

    /**
     * Compute balances for many accounts in a single query.
     *
     * @param  array<int>  $accountIds
     * @return array<int, float> map of account_id => balance
     */
    public static function balancesFor(array $accountIds, ?string $from = null, ?string $to = null): array
    {
        if (empty($accountIds)) {
            return [];
        }

        $query = DB::table('journal_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_lines.account_id')
            ->whereIn('journal_lines.account_id', $accountIds)
            ->where('journal_entries.status', 'posted');

        if ($from) {
            $query->where('journal_entries.entry_date', '>=', $from);
        }
        if ($to) {
            $query->where('journal_entries.entry_date', '<=', $to);
        }

        $rows = $query
            ->selectRaw('journal_lines.account_id as account_id, accounts.normal_balance as normal_balance, COALESCE(SUM(journal_lines.debit), 0) as debit_sum, COALESCE(SUM(journal_lines.credit), 0) as credit_sum')
            ->groupBy('journal_lines.account_id', 'accounts.normal_balance')
            ->get();

        $balances = array_fill_keys($accountIds, 0.0);

        foreach ($rows as $row) {
            $debit = (float) $row->debit_sum;
            $credit = (float) $row->credit_sum;
            $balances[(int) $row->account_id] = $row->normal_balance === AccountNormalBalance::Debit->value
                ? round($debit - $credit, 2)
                : round($credit - $debit, 2);
        }

        return $balances;
    }
}
