<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'restaurant_id',
    'user_id',
    'action',
    'subject_type',
    'subject_id',
    'description',
    'metadata',
])]
class ActivityLog extends Model
{
    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record an activity log entry.
     *
     * @param  array<string, mixed>  $metadata
     */
    public static function record(
        User $actor,
        int $restaurantId,
        string $action,
        string $description,
        ?Model $subject = null,
        array $metadata = [],
    ): void {
        static::create([
            'restaurant_id' => $restaurantId,
            'user_id' => $actor->id,
            'action' => $action,
            'subject_type' => $subject !== null ? class_basename($subject) : null,
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'metadata' => $metadata !== [] ? $metadata : null,
        ]);
    }
}
