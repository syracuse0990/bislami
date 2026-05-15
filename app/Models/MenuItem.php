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
    'pax_min',
    'pax_max',
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
            'pax_min' => 'integer',
            'pax_max' => 'integer',
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

    public function dailyCapacities(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MenuItemDailyCapacity::class);
    }

    public function imageUrl(): ?string
    {
        $imagePath = $this->normalizedImagePath();

        if ($imagePath === null) {
            return null;
        }

        if (str_starts_with($imagePath, 'seed://')) {
            return '/images/'.ltrim(substr($imagePath, strlen('seed://')), '/');
        }

        if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
            return $imagePath;
        }

        return $this->storedImageUrl($imagePath);
    }

    public function displayImageUrl(): string
    {
        return $this->imageUrl() ?? $this->fallbackImageUrl();
    }

    private function normalizedImagePath(): ?string
    {
        if (! is_string($this->image_path)) {
            return null;
        }

        $imagePath = trim($this->image_path);

        return match ($imagePath) {
            '', '0', 'false', 'null' => null,
            default => $imagePath,
        };
    }

    private function fallbackImageUrl(): string
    {
        return match (strtolower((string) $this->category)) {
            'drinks' => '/images/demo-foods/lemon-mint-cooler.svg',
            'desserts', 'breakfast' => '/images/demo-foods/yogurt-parfait.svg',
            'wraps', 'burgers', 'sandwiches' => '/images/demo-foods/street-wrap-combo.svg',
            'protein plates' => '/images/demo-foods/chicken-protein-bowl.svg',
            default => '/images/demo-foods/loaded-rice-bowl.svg',
        };
    }

    private function storedImageUrl(string $imagePath): string
    {
        $disk = Storage::disk('wasabi');

        try {
            return $disk->temporaryUrl($imagePath, now()->addDays(7));
        } catch (\Throwable) {
            return $disk->url($imagePath);
        }
    }

    public function effectivePriceValue(): int
    {
        return (int) ($this->promo_price ?: $this->price);
    }
}