<?php

namespace App\Http\Resources\Api\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'restaurant' => $this['restaurant'],
            'idempotencyKey' => $this['idempotencyKey'],
            'items' => $this['items'],
            'totals' => $this['totals'],
            'total' => [
                'value' => $this['totalValue'],
                'formatted' => $this['totalFormatted'],
            ],
        ];
    }
}