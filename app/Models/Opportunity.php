<?php

namespace App\Models;

use App\Enums\OpportunityStage;
use Database\Factories\OpportunityFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Opportunity extends Model
{
    /** @use HasFactory<OpportunityFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'customer_id',
        'lead_id',
        'owner_id',
        'stage',
        'amount',
        'probability',
        'expected_close_date',
        'closed_at',
        'lost_reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'stage' => OpportunityStage::class,
            'amount' => 'decimal:2',
            'probability' => 'integer',
            'expected_close_date' => 'date',
            'closed_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function tasks(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Task::class, 'taskable');
    }

    public function activities(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Activity::class, 'subject');
    }

    public function weightedAmount(): float
    {
        return round(((float) $this->amount) * ((int) $this->probability) / 100, 2);
    }

    public function isOverdue(): bool
    {
        return $this->stage->isOpen()
            && $this->expected_close_date !== null
            && $this->expected_close_date->isPast();
    }

    /**
     * @param  Builder<Opportunity>  $query
     * @return Builder<Opportunity>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('stage', [
            OpportunityStage::Prospecting->value,
            OpportunityStage::Qualification->value,
            OpportunityStage::Proposal->value,
            OpportunityStage::Negotiation->value,
        ]);
    }

    /**
     * @param  Builder<Opportunity>  $query
     * @return Builder<Opportunity>
     */
    public function scopeClosed(Builder $query): Builder
    {
        return $query->whereIn('stage', [
            OpportunityStage::ClosedWon->value,
            OpportunityStage::ClosedLost->value,
        ]);
    }

    /**
     * @param  Builder<Opportunity>  $query
     * @return Builder<Opportunity>
     */
    public function scopeInStage(Builder $query, OpportunityStage $stage): Builder
    {
        return $query->where('stage', $stage->value);
    }

    /**
     * @param  Builder<Opportunity>  $query
     * @return Builder<Opportunity>
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('notes', 'like', "%{$term}%")
                ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', "%{$term}%"));
        });
    }
}
