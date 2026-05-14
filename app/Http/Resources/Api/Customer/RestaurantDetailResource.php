<?php

namespace App\Http\Resources\Api\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Restaurant */
class RestaurantDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'category' => $this->category,
            'cuisine' => $this->cuisine,
            'featuredText' => $this->featured_text,
            'rating' => [
                'value' => (float) $this->rating,
                'label' => number_format($this->rating, 1).' rating',
            ],
            'eta' => [
                'min' => $this->min_delivery_time,
                'max' => $this->max_delivery_time,
                'label' => "{$this->min_delivery_time}-{$this->max_delivery_time} min",
            ],
            'deliveryFee' => [
                'value' => (int) $this->delivery_fee,
                'formatted' => \App\Support\MoneyFormatter::format((int) $this->delivery_fee, 'Free delivery'),
            ],
            'categories' => $this->menuItems
                ->pluck('category')
                ->unique()
                ->values(),
            'menuItems' => $this->menuItems
                ->map(fn ($menuItem) => [
                    'id' => $menuItem->id,
                    'name' => $menuItem->name,
                    'category' => $menuItem->category,
                    'description' => $menuItem->description,
                    'imageUrl' => $menuItem->imageUrl(),
                    'isAvailable' => (bool) $menuItem->is_available,
                    'price' => [
                        'value' => (int) $menuItem->price,
                        'formatted' => \App\Support\MoneyFormatter::format((int) $menuItem->price),
                    ],
                ])
                ->values(),
        ];
    }
}