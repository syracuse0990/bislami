<?php

namespace App\Http\Resources\Api;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Order */
class OperationsOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currentUser = $request->user();
        $isCourierContext = $currentUser?->role === 'courier';
        $isAssignedToCurrentCourier = $isCourierContext && $this->courier_id === $currentUser?->id;
        $hasCoordinates = $this->delivery_latitude !== null && $this->delivery_longitude !== null;

        return [
            'id' => $this->id,
            'orderNumber' => '#BL-'.str_pad((string) $this->id, 4, '0', STR_PAD_LEFT),
            'restaurant' => [
                'id' => $this->restaurant_id,
                'name' => $this->restaurant->name,
            ],
            'customer' => [
                'id' => $this->user_id,
                'name' => $this->user->name,
            ],
            'status' => [
                'key' => $this->status,
                'label' => str($this->status)->replace('_', ' ')->title()->toString(),
            ],
            'summary' => $this->orderItems
                ->map(fn ($item) => $item->quantity.'x '.$item->name)
                ->join(', '),
            'placedAt' => $this->placed_at?->toIso8601String(),
            'placedAgo' => $this->placed_at?->diffForHumans(),
            'total' => [
                'value' => (int) $this->total,
                'formatted' => \App\Support\MoneyFormatter::format((int) $this->total),
            ],
            'driverNotes' => $this->driver_notes,
            'assignment' => [
                'courierId' => $this->courier_id,
                'courierName' => $this->courier?->name,
                'claimed' => $this->courier_id !== null,
                'canClaim' => $isCourierContext && $this->status === 'on_the_way' && $this->courier_id === null,
                'canComplete' => $isCourierContext && $this->status === 'on_the_way' && $isAssignedToCurrentCourier,
                'isAssignedToCurrentCourier' => $isAssignedToCurrentCourier,
            ],
            'destination' => [
                'address' => $this->delivery_address,
                'shortLabel' => $this->shortAddress(),
                'latitude' => $this->delivery_latitude,
                'longitude' => $this->delivery_longitude,
                'hasCoordinates' => $hasCoordinates,
                'mapsUrl' => $this->mapsUrl($hasCoordinates),
            ],
        ];
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

    private function mapsUrl(bool $hasCoordinates): ?string
    {
        if ($hasCoordinates) {
            return 'https://www.google.com/maps/search/?api=1&query='
                .rawurlencode($this->delivery_latitude.','.$this->delivery_longitude);
        }

        if (! $this->delivery_address) {
            return null;
        }

        return 'https://www.google.com/maps/search/?api=1&query='
            .rawurlencode($this->delivery_address);
    }
}