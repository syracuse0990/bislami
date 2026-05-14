<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourierWorkspaceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'overview' => data_get($this->resource, 'overview', []),
            'assignedDeliveries' => OperationsOrderResource::collection(collect(data_get($this->resource, 'assignedDeliveries', []))),
            'availableClaims' => OperationsOrderResource::collection(collect(data_get($this->resource, 'availableClaims', []))),
            'pickupQueue' => OperationsOrderResource::collection(collect(data_get($this->resource, 'pickupQueue', []))),
        ];
    }
}