<?php

namespace App\Http\Resources\Api\Guest;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscoveryRestaurantContextResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => data_get($this->resource, 'name'),
            'slug' => data_get($this->resource, 'slug'),
            'category' => data_get($this->resource, 'category'),
            'cuisine' => data_get($this->resource, 'cuisine'),
            'featured' => data_get($this->resource, 'featured'),
            'rating' => data_get($this->resource, 'rating'),
            'eta' => data_get($this->resource, 'eta'),
            'deliveryFee' => data_get($this->resource, 'deliveryFee'),
        ];
    }
}