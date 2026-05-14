<?php

namespace App\Http\Resources\Api;

use App\Models\Order;
use App\Support\CustomerOrderListData;
use App\Support\CustomerOrderTrackingData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Order */
class CustomerOrderDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $tracking = app(CustomerOrderTrackingData::class)->build($this->status);
        $reorder = app(CustomerOrderListData::class)->reorderState($this->resource);

        return [
            'id' => $this->id,
            'orderNumber' => '#BL-'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT),
            'restaurant' => [
                'id' => $this->restaurant_id,
                'name' => $this->restaurant->name,
                'slug' => $this->restaurant->slug,
                'category' => $this->restaurant->category,
                'cuisine' => $this->restaurant->cuisine,
                'featured' => $this->restaurant->featured_text,
                'eta' => [
                    'min' => $this->restaurant->min_delivery_time,
                    'max' => $this->restaurant->max_delivery_time,
                    'label' => "{$this->restaurant->min_delivery_time}-{$this->restaurant->max_delivery_time} min",
                ],
                'deliveryFee' => [
                    'value' => (int) $this->restaurant->delivery_fee,
                    'formatted' => $this->formatMoney((int) $this->restaurant->delivery_fee, 'Free delivery'),
                ],
            ],
            'status' => $tracking['status'],
            'guidance' => $tracking['guidance'],
            'timeline' => $tracking['timeline'],
            'summary' => $this->orderItems
                ->map(fn ($item) => $item->quantity.'x '.$item->name)
                ->join(', '),
            'placedAt' => $this->placed_at?->toIso8601String(),
            'placedAgo' => $this->placed_at?->diffForHumans(),
            'paymentMethod' => $this->payment_method,
            'driverNotes' => $this->driver_notes,
            'canTrack' => in_array($this->status, ['preparing', 'on_the_way'], true),
            'canReorder' => $reorder['available'],
            'reorder' => $this->transformReorder($reorder),
            'items' => $this->orderItems
                ->map(fn ($item) => [
                    'name' => $item->name,
                    'quantityLabel' => $item->quantity.'x',
                    'quantityValue' => $item->quantity,
                    'unitPrice' => [
                        'value' => (int) $item->unit_price,
                        'formatted' => $this->formatMoney((int) $item->unit_price),
                    ],
                    'lineTotal' => [
                        'value' => (int) $item->line_total,
                        'formatted' => $this->formatMoney((int) $item->line_total),
                    ],
                ])
                ->values(),
            'totals' => [
                'subtotal' => [
                    'value' => (int) $this->subtotal,
                    'formatted' => $this->formatMoney((int) $this->subtotal),
                ],
                'delivery' => [
                    'value' => (int) $this->delivery_fee,
                    'formatted' => $this->formatMoney((int) $this->delivery_fee, 'Free delivery'),
                ],
                'serviceFee' => [
                    'value' => (int) $this->service_fee,
                    'formatted' => $this->formatMoney((int) $this->service_fee),
                ],
                'total' => [
                    'value' => (int) $this->total,
                    'formatted' => $this->formatMoney((int) $this->total),
                ],
            ],
            'destination' => [
                'address' => $this->delivery_address,
                'shortLabel' => $this->shortAddress(),
                'latitude' => $this->delivery_latitude,
                'longitude' => $this->delivery_longitude,
                'hasCoordinates' => $this->delivery_latitude !== null && $this->delivery_longitude !== null,
                'mapsUrl' => $this->mapsUrl(),
            ],
        ];
    }

    private function formatMoney(int $amount, ?string $zeroLabel = null): string
    {
        return \App\Support\MoneyFormatter::format($amount, $zeroLabel);
    }

    private function shortAddress(): string
    {
        if (! $this->delivery_address) {
            return 'Address pending';
        }

        return collect(explode(',', $this->delivery_address))
            ->map(fn (string $segment) => trim($segment))
            ->filter()
            ->take(2)
            ->join(', ');
    }

    private function mapsUrl(): ?string
    {
        if ($this->delivery_latitude !== null && $this->delivery_longitude !== null) {
            return 'https://www.google.com/maps/search/?api=1&query='
                .rawurlencode($this->delivery_latitude.','.$this->delivery_longitude);
        }

        if (! $this->delivery_address) {
            return null;
        }

        return 'https://www.google.com/maps/search/?api=1&query='
            .rawurlencode($this->delivery_address);
    }

    /**
     * @param  array<string, mixed>  $reorder
     * @return array<string, mixed>
     */
    private function transformReorder(array $reorder): array
    {
        return [
            'visible' => (bool) data_get($reorder, 'visible', false),
            'available' => (bool) data_get($reorder, 'available', false),
            'label' => data_get($reorder, 'label'),
            'description' => data_get($reorder, 'description'),
            'items' => collect(data_get($reorder, 'items', []))
                ->map(fn ($item) => [
                    'name' => data_get($item, 'name'),
                    'reason' => data_get($item, 'reason'),
                    'suggestions' => collect(data_get($item, 'suggestions', []))
                        ->map(fn ($suggestion) => [
                            'id' => data_get($suggestion, 'id'),
                            'slug' => data_get($suggestion, 'slug'),
                            'name' => data_get($suggestion, 'name'),
                            'category' => data_get($suggestion, 'category'),
                            'price' => [
                                'value' => data_get($suggestion, 'price.value'),
                                'formatted' => data_get($suggestion, 'price.formatted'),
                            ],
                        ])
                        ->values(),
                ])
                ->values(),
        ];
    }
}