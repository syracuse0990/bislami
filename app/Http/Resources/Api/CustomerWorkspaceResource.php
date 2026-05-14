<?php

namespace App\Http\Resources\Api;

use App\Http\Resources\Api\Customer\CartResource;
use App\Http\Resources\Api\Guest\DiscoveryFoodResource;
use App\Http\Resources\Api\Guest\DiscoveryRestaurantSummaryResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerWorkspaceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'overview' => data_get($this->resource, 'overview', []),
            'spotlightRestaurants' => DiscoveryRestaurantSummaryResource::collection(collect(data_get($this->resource, 'spotlightRestaurants', []))),
            'spotlightFoods' => DiscoveryFoodResource::collection(collect(data_get($this->resource, 'spotlightFoods', []))),
            'cart' => CartResource::make(data_get($this->resource, 'cart', [
                'restaurant' => null,
                'idempotencyKey' => null,
                'items' => [],
                'totals' => [],
                'totalValue' => 0,
                'totalFormatted' => \App\Support\MoneyFormatter::format(0),
            ])),
            'recentOrders' => CustomerWorkspaceOrderResource::collection(collect(data_get($this->resource, 'recentOrders', []))),
        ];
    }
}