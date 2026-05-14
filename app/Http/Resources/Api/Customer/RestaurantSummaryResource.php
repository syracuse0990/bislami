<?php

namespace App\Http\Resources\Api\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Restaurant */
class RestaurantSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $featuredMenuItem = $this->menuItems->first();

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'category' => $this->category,
            'cuisine' => $this->cuisine,
            'eta' => [
                'min' => $this->min_delivery_time,
                'max' => $this->max_delivery_time,
                'label' => "{$this->min_delivery_time}-{$this->max_delivery_time} min",
            ],
            'rating' => [
                'value' => (float) $this->rating,
                'label' => number_format($this->rating, 1).' rating',
            ],
            'deliveryFee' => [
                'value' => (int) $this->delivery_fee,
                'formatted' => \App\Support\MoneyFormatter::format((int) $this->delivery_fee, 'Free delivery'),
            ],
            'featuredText' => $this->featured_text,
            'featuredItem' => $featuredMenuItem?->name,
            'featuredImageUrl' => $featuredMenuItem?->imageUrl(),
            'featuredPrice' => $featuredMenuItem ? [
                'value' => (int) $featuredMenuItem->price,
                'formatted' => \App\Support\MoneyFormatter::format((int) $featuredMenuItem->price),
            ] : null,
        ];
    }
}