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
            'fulfillmentType' => $this['fulfillmentType'],
            'deliveryAddress' => $this['deliveryAddress'],
            'deliveryLatitude' => $this['deliveryLatitude'],
            'deliveryLongitude' => $this['deliveryLongitude'],
            'idempotencyKey' => $this['idempotencyKey'],
            'paymentMethod' => $this['paymentMethod'],
            'driverNotes' => $this['driverNotes'],
            'customerNotes' => $this['customerNotes'],
            'scheduledFor' => $this['scheduledFor'],
            'total' => [
                'value' => $this['totalValue'],
                'formatted' => $this['totalFormatted'],
            ],
        ];
    }
}