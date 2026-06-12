<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasDateRangeScope
{
    /**
     * Apply a between range on the given date column.
     *
     * Usage: ->inDateRange($column, $from, $to)
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeInDateRange(Builder $query, string $column, ?string $from, ?string $to): Builder
    {
        if ($from) {
            $query->where($column, '>=', $from);
        }

        if ($to) {
            $query->where($column, '<=', $to);
        }

        return $query;
    }
}
