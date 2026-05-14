<?php

namespace App\Http\Resources\Api\Guest;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscoveryFoodDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => data_get($this->resource, 'id'),
            'slug' => data_get($this->resource, 'slug'),
            'name' => data_get($this->resource, 'name'),
            'category' => data_get($this->resource, 'category'),
            'description' => data_get($this->resource, 'description'),
            'imageUrl' => data_get($this->resource, 'imageUrl'),
            'price' => data_get($this->resource, 'price'),
            'priceValue' => data_get($this->resource, 'priceValue'),
            'restaurant' => DiscoveryRestaurantContextResource::make(data_get($this->resource, 'restaurant', [])),
            'relatedItems' => DiscoveryFoodResource::collection(collect(data_get($this->resource, 'relatedItems', []))),
        ];
    }
}