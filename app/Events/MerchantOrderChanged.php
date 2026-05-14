<?php

namespace App\Events;

use App\Models\Order;
use App\Support\MoneyFormatter;
use App\Support\OrderLifecycle;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MerchantOrderChanged implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Order $order,
        public string $eventType = 'updated',
    ) {
        $this->order->loadMissing(['restaurant:id,name,user_id', 'user:id,name']);
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('merchant.orders.'.$this->order->restaurant_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'merchant.order.changed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $fulfillmentType = $this->order->fulfillment_type ?? 'delivery';

        return [
            'eventType' => $this->eventType,
            'orderId' => $this->order->id,
            'orderNumber' => '#BL-'.str_pad((string) $this->order->id, 4, '0', STR_PAD_LEFT),
            'restaurantId' => $this->order->restaurant_id,
            'restaurantName' => $this->order->restaurant?->name,
            'customerName' => $this->order->user?->name,
            'status' => [
                'key' => $this->order->status,
                'stageKey' => OrderLifecycle::normalize($this->order->status),
                'label' => OrderLifecycle::label($this->order->status, $fulfillmentType),
            ],
            'fulfillment' => [
                'key' => $fulfillmentType,
                'label' => str($fulfillmentType)->title()->toString(),
            ],
            'isScheduled' => $this->order->scheduled_for !== null,
            'scheduledFor' => $this->order->scheduled_for?->toIso8601String(),
            'customerNotes' => $this->order->customer_notes,
            'total' => [
                'value' => (int) $this->order->total,
                'formatted' => MoneyFormatter::format((int) $this->order->total),
            ],
            'placedAt' => $this->order->placed_at?->toIso8601String(),
        ];
    }
}