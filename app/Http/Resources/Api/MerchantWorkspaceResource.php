<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MerchantWorkspaceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'overview' => data_get($this->resource, 'overview', []),
            'recentOrders' => OperationsOrderResource::collection(collect(data_get($this->resource, 'recentOrders', []))),
            'recentMenuItems' => MerchantWorkspaceMenuItemResource::collection(collect(data_get($this->resource, 'recentMenuItems', []))),
            'restaurants' => MerchantWorkspaceRestaurantResource::collection(collect(data_get($this->resource, 'restaurants', []))),
        ];
    }
}