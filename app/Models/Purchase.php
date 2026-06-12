<?php

namespace App\Models;

use App\Enums\PurchaseStatus;
use Database\Factories\PurchaseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    /** @use HasFactory<PurchaseFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'folio',
        'supplier_id',
        'user_id',
        'subtotal',
        'tax',
        'total',
        'paid_amount',
        'balance',
        'status',
        'purchase_date',
        'received_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'balance' => 'decimal:2',
            'status' => PurchaseStatus::class,
            'purchase_date' => 'date',
            'received_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function isFullyPaid(): bool
    {
        return (float) $this->balance <= 0;
    }

    /**
     * @param  Builder<Purchase>  $query
     * @return Builder<Purchase>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', PurchaseStatus::Pending->value);
    }

    /**
     * @param  Builder<Purchase>  $query
     * @return Builder<Purchase>
     */
    public function scopeReceived(Builder $query): Builder
    {
        return $query->where('status', PurchaseStatus::Received->value);
    }

    /**
     * @param  Builder<Purchase>  $query
     * @return Builder<Purchase>
     */
    public function scopeInDateRange(Builder $query, ?string $from, ?string $to): Builder
    {
        if ($from) {
            $query->where('purchase_date', '>=', $from);
        }

        if ($to) {
            $query->where('purchase_date', '<=', $to);
        }

        return $query;
    }
}
