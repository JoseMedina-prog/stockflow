<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
    ];

    public static function boot()
    {
        parent::boot();

        static::saved(function (Setting $setting) {
            Cache::forget('settings:all');
            if ($setting->key === 'business.name') {
                Cache::forget('app:display_name');
            }
        });

        static::deleted(function (Setting $setting) {
            Cache::forget('settings:all');
            if ($setting->key === 'business.name') {
                Cache::forget('app:display_name');
            }
        });
    }

    /**
     * @param  Builder<Setting>  $query
     * @return Builder<Setting>
     */
    public function scopeInGroup(Builder $query, string $group): Builder
    {
        return $query->where('group', $group);
    }

    public function typedValue(): mixed
    {
        return match ($this->type) {
            'int', 'integer' => (int) $this->value,
            'bool', 'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'float' => (float) $this->value,
            'json' => $this->value !== null ? json_decode($this->value, true) : null,
            default => $this->value,
        };
    }
}
