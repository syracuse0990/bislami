<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckoutSessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'cartOrderId' => $this['cartOrderId'],
            'restaurant' => $this['restaurant'],
            'summary' => $this['summary'],
            'deliveryAddress' => $this['deliveryAddress'],
            'deliveryLatitude' => $this['deliveryLatitude'],
            'deliveryLongitude' => $this['deliveryLongitude'],
            'idempotencyKey' => $this['idempotencyKey'],
            'paymentMethod' => $this['paymentMethod'],
            'driverNotes' => $this['driverNotes'],
            'total' => [
                'value' => $this['totalValue'],
                'formatted' => $this['totalFormatted'],
            ],
        ];
    }
}