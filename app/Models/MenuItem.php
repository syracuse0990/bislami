<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'restaurant_id',
    'name',
    'slug',
    'category',
    'description',
    'image_path',
    'price',
    'promo_price',
    'is_available',
    'availability_starts_at',
    'availability_ends_at',
    'variants',
    'add_ons',
    'modifiers',
    'bundle_items',
])]
class MenuItem extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
            'variants' => 'array',
            'add_ons' => 'array',
            'modifiers' => 'array',
            'bundle_items' => 'array',
        ];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function imageUrl(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        if (str_starts_with($this->image_path, 'seed://')) {
            return '/images/'.ltrim(substr($this->image_path, strlen('seed://')), '/');
        }

        if (filter_var($this->image_path, FILTER_VALIDATE_URL)) {
            return $this->image_path;
        }

        return Storage::disk('wasabi')->url($this->image_path);
    }

    public function effectivePriceValue(): int
    {
        return (int) ($this->promo_price ?: $this->price);
    }
}