<?php

namespace App\Http\Resources\Api\Guest;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscoveryRestaurantSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => data_get($this->resource, 'id'),
            'slug' => data_get($this->resource, 'slug'),
            'category' => data_get($this->resource, 'category'),
            'menuItemId' => data_get($this->resource, 'menuItemId'),
            'name' => data_get($this->resource, 'name'),
            'cuisine' => data_get($this->resource, 'cuisine'),
            'eta' => data_get($this->resource, 'eta'),
            'rating' => data_get($this->resource, 'rating'),
            'deliveryFee' => data_get($this->resource, 'deliveryFee'),
            'featured' => data_get($this->resource, 'featured'),
            'featuredItem' => data_get($this->resource, 'featuredItem'),
            'featuredImageUrl' => data_get($this->resource, 'featuredImageUrl'),
            'featuredPrice' => data_get($this->resource, 'featuredPrice'),
        ];
    }
}