<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'user_id',
    'name',
    'slug',
    'category',
    'cuisine',
    'min_delivery_time',
    'max_delivery_time',
    'rating',
    'delivery_fee',
    'featured_text',
    'contact_phone',
    'logo_path',
    'location_address',
    'location_latitude',
    'location_longitude',
    'delivery_radius_km',
    'delivery_area_coordinates',
    'minimum_order_value',
    'preparation_time_min',
    'preparation_time_max',
    'operating_hours',
    'closure_dates',
    'is_visible',
    'order_settings',
])]
class Restaurant extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'location_latitude' => 'float',
            'location_longitude' => 'float',
            'delivery_radius_km' => 'float',
            'delivery_area_coordinates' => 'array',
            'minimum_order_value' => 'integer',
            'preparation_time_min' => 'integer',
            'preparation_time_max' => 'integer',
            'operating_hours' => 'array',
            'closure_dates' => 'array',
            'order_settings' => 'array',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function defaultOperatingHours(): array
    {
        return collect([
            'monday',
            'tuesday',
            'wednesday',
            'thursday',
            'friday',
            'saturday',
            'sunday',
        ])->map(fn (string $day) => [
            'day' => $day,
            'label' => Str::headline($day),
            'enabled' => true,
            'open' => '07:00',
            'close' => '17:00',
        ])->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function normalizedOperatingHours(): array
    {
        $storedHours = collect($this->operating_hours ?? [])->keyBy('day');

        return collect(self::defaultOperatingHours())
            ->map(function (array $defaultHour) use ($storedHours): array {
                $currentHour = $storedHours->get($defaultHour['day'], []);

                return [
                    'day' => $defaultHour['day'],
                    'label' => $defaultHour['label'],
                    'enabled' => (bool) ($currentHour['enabled'] ?? $defaultHour['enabled']),
                    'open' => $currentHour['open'] ?? $defaultHour['open'],
                    'close' => $currentHour['close'] ?? $defaultHour['close'],
                ];
            })
            ->values()
            ->all();
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function normalizedOrderSettings(): array
    {
        return [
            ...\App\Support\OrderLifecycle::defaultOrderSettings(),
            ...($this->order_settings ?? []),
        ];
    }

    public function orderSetting(string $key, mixed $default = null): mixed
    {
        return $this->normalizedOrderSettings()[$key] ?? $default;
    }

    public function autoAcceptsOrders(): bool
    {
        return (bool) $this->orderSetting('auto_accept_enabled', false);
    }

    public function autoRejectsUnavailableItems(): bool
    {
        return (bool) $this->orderSetting('auto_reject_unavailable_items', true);
    }

    public function logoUrl(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        if (filter_var($this->logo_path, FILTER_VALIDATE_URL)) {
            return $this->logo_path;
        }

        return \Illuminate\Support\Facades\Storage::disk('wasabi')->url($this->logo_path);
    }

    public function mapsUrl(): ?string
    {
        if ($this->location_latitude !== null && $this->location_longitude !== null) {
            return 'https://www.google.com/maps/search/?api=1&query='
                .rawurlencode($this->location_latitude.','.$this->location_longitude);
        }

        if (! $this->location_address) {
            return null;
        }

        return 'https://www.google.com/maps/search/?api=1&query='
            .rawurlencode($this->location_address);
    }
}