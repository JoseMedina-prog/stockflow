<?php

namespace App\Support;

use App\Models\CreditNote;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Quote;
use App\Models\Sale;
use App\Models\SaleReturn;
use Illuminate\Support\Facades\DB;

class FolioGenerator
{
    private const PAD_LENGTH = 6;

    public static function nextPurchaseFolio(): string
    {
        return self::nextFor('C-', Purchase::class);
    }

    public static function nextSaleFolio(): string
    {
        return self::nextFor('V-', Sale::class);
    }

    public static function nextReturnFolio(): string
    {
        return self::nextFor('D-', SaleReturn::class);
    }

    public static function nextCreditNoteFolio(): string
    {
        return self::nextFor('NC-', CreditNote::class);
    }

    public static function nextPaymentFolio(): string
    {
        return self::nextFor('P-', Payment::class);
    }

    public static function nextQuoteFolio(): string
    {
        return self::nextFor('COT-', Quote::class);
    }

    public static function nextJournalFolio(): string
    {
        return self::nextFor('POL-', JournalEntry::class);
    }

    private static function nextFor(string $prefix, string $model): string
    {
        return DB::transaction(function () use ($prefix, $model) {
            $query = $model::query();

            if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($model))) {
                $query->withTrashed();
            }

            $lastNumber = (int) $query
                ->where('folio', 'like', $prefix.'%')
                ->lockForUpdate()
                ->selectRaw('MAX(CAST(SUBSTRING(folio, ?) AS SIGNED)) as max_num', [strlen($prefix) + 1])
                ->value('max_num');

            $next = $lastNumber + 1;

            return $prefix.str_pad((string) $next, self::PAD_LENGTH, '0', STR_PAD_LEFT);
        });
    }
}
