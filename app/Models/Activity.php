<?php

namespace App\Models;

use App\Enums\ActivityType;
use App\Support\SubjectRegistry;
use Database\Factories\ActivityFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{
    /** @use HasFactory<ActivityFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'type',
        'subject_type',
        'subject_id',
        'user_id',
        'description',
        'occurred_at',
        'duration_minutes',
        'outcome',
    ];

    protected function casts(): array
    {
        return [
            'type' => ActivityType::class,
            'occurred_at' => 'datetime',
            'duration_minutes' => 'integer',
        ];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subjectTypeLabel(): string
    {
        $label = SubjectRegistry::label($this->subject_type);

        return $label ?? class_basename($this->subject_type ?? '');
    }

    /**
     * @param  Builder<Activity>  $query
     * @return Builder<Activity>
     */
    public function scopeInDateRange(Builder $query, ?string $from, ?string $to): Builder
    {
        if ($from) {
            $query->where('occurred_at', '>=', $from);
        }

        if ($to) {
            $query->where('occurred_at', '<=', $to);
        }

        return $query;
    }
}
