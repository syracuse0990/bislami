<?php

namespace App\Http\Resources\Api\Guest;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscoveryIndexResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'foods' => DiscoveryFoodResource::collection(collect(data_get($this->resource, 'foods', []))),
            'restaurants' => DiscoveryRestaurantSummaryResource::collection(collect(data_get($this->resource, 'restaurants', []))),
            'stats' => data_get($this->resource, 'stats', []),
        ];
    }
}