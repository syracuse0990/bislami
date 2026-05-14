<?php

namespace App\Http\Resources\Api\Guest;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscoveryFoodResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => data_get($this->resource, 'id'),
            'slug' => data_get($this->resource, 'slug'),
            'restaurantSlug' => data_get($this->resource, 'restaurantSlug'),
            'restaurantName' => data_get($this->resource, 'restaurantName'),
            'restaurantCategory' => data_get($this->resource, 'restaurantCategory'),
            'restaurantCuisine' => data_get($this->resource, 'restaurantCuisine'),
            'restaurantFeaturedText' => data_get($this->resource, 'restaurantFeaturedText'),
            'name' => data_get($this->resource, 'name'),
            'category' => data_get($this->resource, 'category'),
            'description' => data_get($this->resource, 'description'),
            'imageUrl' => data_get($this->resource, 'imageUrl'),
            'eta' => data_get($this->resource, 'eta'),
            'etaMinutes' => data_get($this->resource, 'etaMinutes'),
            'rating' => data_get($this->resource, 'rating'),
            'ratingValue' => data_get($this->resource, 'ratingValue'),
            'deliveryFee' => data_get($this->resource, 'deliveryFee'),
            'deliveryFeeValue' => data_get($this->resource, 'deliveryFeeValue'),
            'price' => data_get($this->resource, 'price'),
            'priceValue' => data_get($this->resource, 'priceValue'),
        ];
    }
}