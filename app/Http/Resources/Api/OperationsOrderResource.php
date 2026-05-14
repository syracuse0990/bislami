<?php

namespace App\Http\Resources\Api;

use App\Support\OrderLifecycle;
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
        $fulfillmentType = $this->fulfillment_type ?? 'delivery';
        $isAssignedToCurrentCourier = $isCourierContext && $this->courier_id === $currentUser?->id;
        $hasCoordinates = $this->delivery_latitude !== null && $this->delivery_longitude !== null;
        $normalizedStatus = OrderLifecycle::normalize($this->status);
        $canClaim = $isCourierContext
            && $this->courier_id === null
            && (OrderLifecycle::isCourierClaimable($this->status, $fulfillmentType)
                || ($this->status === OrderLifecycle::LEGACY_ON_THE_WAY && $fulfillmentType === 'delivery'));
        $canComplete = $isCourierContext
            && OrderLifecycle::isCourierCompletable($this->status, $fulfillmentType)
            && $isAssignedToCurrentCourier;

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
                'stageKey' => $normalizedStatus,
                'label' => OrderLifecycle::label($this->status, $fulfillmentType),
                'accent' => OrderLifecycle::accent($this->status),
            ],
            'fulfillment' => [
                'key' => $fulfillmentType,
                'label' => str($fulfillmentType)->title()->toString(),
            ],
            'summary' => $this->orderItems
                ->map(fn ($item) => $item->quantity.'x '.$item->name)
                ->join(', '),
            'placedAt' => $this->placed_at?->toIso8601String(),
            'placedAgo' => $this->placed_at?->diffForHumans(),
            'scheduledFor' => $this->scheduled_for?->toIso8601String(),
            'scheduledForLabel' => $this->scheduled_for?->diffForHumans(),
            'total' => [
                'value' => (int) $this->total,
                'formatted' => \App\Support\MoneyFormatter::format((int) $this->total),
            ],
            'driverNotes' => $this->driver_notes,
            'customerNotes' => $this->customer_notes,
            'merchantNotes' => $this->merchant_notes,
            'notes' => [
                'customer' => $this->customer_notes,
                'delivery' => $this->driver_notes,
                'merchant' => $this->merchant_notes,
            ],
            'rejection' => [
                'code' => $this->rejection_reason_code,
                'note' => $this->rejection_reason_note,
            ],
            'actions' => [
                'canAccept' => $normalizedStatus === OrderLifecycle::PENDING,
                'canReject' => in_array($normalizedStatus, [OrderLifecycle::PENDING, OrderLifecycle::ACCEPTED], true),
                'canEdit' => OrderLifecycle::isMerchantEditable($this->status),
                'canStartPreparing' => $normalizedStatus === OrderLifecycle::ACCEPTED,
                'canMarkReady' => $normalizedStatus === OrderLifecycle::PREPARING,
                'canCompletePickup' => $fulfillmentType === 'pickup' && $normalizedStatus === OrderLifecycle::READY,
            ],
            'assignment' => [
                'courierId' => $this->courier_id,
                'courierName' => $this->courier?->name,
                'label' => $this->assignmentLabel($fulfillmentType),
                'claimed' => $this->courier_id !== null,
                'canClaim' => $canClaim,
                'canComplete' => $canComplete,
                'isAssignedToCurrentCourier' => $isAssignedToCurrentCourier,
            ],
            'destination' => [
                'address' => $this->delivery_address ?: ($fulfillmentType === 'pickup' ? 'Customer pickup at restaurant' : 'Address pending'),
                'shortLabel' => $this->shortAddress($fulfillmentType),
                'latitude' => $this->delivery_latitude,
                'longitude' => $this->delivery_longitude,
                'hasCoordinates' => $hasCoordinates,
                'mapsUrl' => $this->mapsUrl($hasCoordinates),
            ],
        ];
    }

    private function shortAddress(string $fulfillmentType): string
    {
        if (! $this->delivery_address) {
            return $fulfillmentType === 'pickup' ? 'Customer pickup' : 'Address pending';
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

    private function assignmentLabel(string $fulfillmentType): string
    {
        if ($fulfillmentType === 'pickup') {
            return 'Customer pickup';
        }

        if ($this->status === OrderLifecycle::LEGACY_ON_THE_WAY && $this->courier_id === null) {
            return 'Available to claim';
        }

        if ($this->courier) {
            return $this->courier->name;
        }

        return match (OrderLifecycle::normalize($this->status)) {
            OrderLifecycle::READY => 'Available to claim',
            OrderLifecycle::PICKED_UP => 'Courier assigned',
            default => 'Awaiting kitchen progress',
        };
    }
}