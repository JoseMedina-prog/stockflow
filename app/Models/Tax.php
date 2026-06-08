<?php

namespace App\Models;

use App\Enums\AccountType;
use App\Enums\TaxType;
use Database\Factories\TaxFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tax extends Model
{
    /** @use HasFactory<TaxFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'rate',
        'account_id',
        'is_active',
        'is_inclusive',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'type' => TaxType::class,
            'rate' => 'decimal:4',
            'is_active' => 'boolean',
            'is_inclusive' => 'boolean',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function percentRate(): float
    {
        return round(((float) $this->rate) * 100, 2);
    }

    /**
     * @param  Builder<Tax>  $query
     * @return Builder<Tax>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<Tax>  $query
     * @return Builder<Tax>
     */
    public function scopeForSales(Builder $query): Builder
    {
        return $query->whereIn('type', [
            TaxType::IvaTrasladado->value,
            TaxType::Ieps->value,
            TaxType::RetencionIva->value,
        ]);
    }

    /**
     * @param  Builder<Tax>  $query
     * @return Builder<Tax>
     */
    public function scopeForPurchases(Builder $query): Builder
    {
        return $query->whereIn('type', [
            TaxType::IvaAcreditable->value,
            TaxType::Ieps->value,
            TaxType::Isr->value,
            TaxType::RetencionIsr->value,
        ]);
    }

    public function isSalesTax(): bool
    {
        return $this->type->isSales();
    }

    public function isPurchaseTax(): bool
    {
        return $this->type->isPurchase();
    }

    public function defaultAccountType(): ?AccountType
    {
        return match ($this->type) {
            TaxType::IvaTrasladado, TaxType::IvaAcreditable, TaxType::Ieps => AccountType::Liability,
            TaxType::Isr, TaxType::RetencionIva, TaxType::RetencionIsr => AccountType::Liability,
            default => null,
        };
    }
}
