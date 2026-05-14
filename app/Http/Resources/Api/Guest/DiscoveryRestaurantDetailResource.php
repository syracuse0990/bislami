<?php

namespace App\Http\Resources\Api\Guest;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscoveryRestaurantDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => data_get($this->resource, 'name'),
            'slug' => data_get($this->resource, 'slug'),
            'cuisine' => data_get($this->resource, 'cuisine'),
            'category' => data_get($this->resource, 'category'),
            'featured' => data_get($this->resource, 'featured'),
            'rating' => data_get($this->resource, 'rating'),
            'eta' => data_get($this->resource, 'eta'),
            'deliveryFee' => data_get($this->resource, 'deliveryFee'),
            'categories' => data_get($this->resource, 'categories', []),
            'menuItems' => DiscoveryMenuItemResource::collection(collect(data_get($this->resource, 'menuItems', []))),
        ];
    }
}