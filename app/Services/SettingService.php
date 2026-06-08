<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingService
{
    private const CACHE_KEY = 'settings:all';
    private const CACHE_TTL = 3600;

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            $result = [];
            foreach (Setting::all() as $setting) {
                $result[$setting->key] = $setting->typedValue();
            }

            return $result;
        });
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function put(string $key, mixed $value, string $type = 'string', string $group = 'general', ?string $description = null): void
    {
        $stored = match ($type) {
            'json' => $value !== null ? json_encode($value) : null,
            'bool', 'boolean' => $value ? '1' : '0',
            default => $value !== null ? (string) $value : null,
        };

        Setting::updateOrCreate(
            ['key' => $key],
            [
                'value' => $stored,
                'type' => $type,
                'group' => $group,
                'description' => $description,
            ],
        );
    }

    /**
     * Persist many values at once. Each key in $values should map to a definition in $definitions.
     *
     * @param  array<string, mixed>  $values
     * @param  array<string, array{type?: string, group?: string, description?: string}>  $definitions
     */
    public function putMany(array $values, array $definitions): void
    {
        foreach ($values as $key => $value) {
            $def = $definitions[$key] ?? [];
            $this->put(
                key: $key,
                value: $value,
                type: $def['type'] ?? 'string',
                group: $def['group'] ?? 'general',
                description: $def['description'] ?? null,
            );
        }
    }

    public function forget(string $key): void
    {
        Setting::where('key', $key)->delete();
    }

    public function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
