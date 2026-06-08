<?php

namespace App\Models;

use App\Enums\JournalEntryStatus;
use Database\Factories\JournalEntryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class JournalEntry extends Model
{
    /** @use HasFactory<JournalEntryFactory> */
    use HasFactory;

    protected $fillable = [
        'folio',
        'entry_date',
        'concept',
        'description',
        'reference',
        'source_type',
        'source_id',
        'status',
        'posted_by',
        'posted_at',
        'total_debit',
        'total_credit',
    ];

    protected function casts(): array
    {
        return [
            'status' => JournalEntryStatus::class,
            'entry_date' => 'date',
            'posted_at' => 'datetime',
            'total_debit' => 'decimal:2',
            'total_credit' => 'decimal:2',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class)->orderBy('sort');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function isBalanced(): bool
    {
        return abs((float) $this->total_debit - (float) $this->total_credit) < 0.01;
    }

    public function recalculateTotals(): void
    {
        $totals = $this->lines()
            ->selectRaw('SUM(debit) as d, SUM(credit) as c')
            ->first();
        $this->total_debit = round((float) ($totals->d ?? 0), 2);
        $this->total_credit = round((float) ($totals->c ?? 0), 2);
        $this->save();
    }

    /**
     * @param  Builder<JournalEntry>  $query
     * @return Builder<JournalEntry>
     */
    public function scopePosted(Builder $query): Builder
    {
        return $query->where('status', JournalEntryStatus::Posted->value);
    }

    /**
     * @param  Builder<JournalEntry>  $query
     * @return Builder<JournalEntry>
     */
    public function scopeInPeriod(Builder $query, ?string $from, ?string $to): Builder
    {
        if ($from) {
            $query->where('entry_date', '>=', $from);
        }
        if ($to) {
            $query->where('entry_date', '<=', $to);
        }
        return $query;
    }
}
