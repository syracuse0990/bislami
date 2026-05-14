<?php

namespace App\Observers;

use App\Events\MerchantOrderChanged;
use App\Models\Order;
use App\Support\OrderLifecycle;

class OrderObserver
{
    public bool $afterCommit = true;

    public function created(Order $order): void
    {
        if (! $this->shouldBroadcastCreated($order)) {
            return;
        }

        event(new MerchantOrderChanged($order->fresh(['restaurant:id,name,user_id', 'user:id,name']), 'created'));
    }

    public function updated(Order $order): void
    {
        if (! $this->shouldBroadcastUpdated($order)) {
            return;
        }

        $eventType = $order->getOriginal('status') === OrderLifecycle::CART ? 'created' : 'updated';

        event(new MerchantOrderChanged($order->fresh(['restaurant:id,name,user_id', 'user:id,name']), $eventType));
    }

    private function shouldBroadcastCreated(Order $order): bool
    {
        return $order->restaurant_id !== null && $order->status !== OrderLifecycle::CART;
    }

    private function shouldBroadcastUpdated(Order $order): bool
    {
        if ($order->restaurant_id === null || $order->status === OrderLifecycle::CART) {
            return false;
        }

        $relevantAttributes = [
            'status',
            'fulfillment_type',
            'scheduled_for',
            'customer_notes',
            'merchant_notes',
            'driver_notes',
            'courier_id',
            'rejection_reason_code',
            'rejection_reason_note',
            'delivery_address',
            'delivery_latitude',
            'delivery_longitude',
            'accepted_at',
            'preparing_at',
            'ready_at',
            'picked_up_at',
            'delivered_at',
            'rejected_at',
        ];

        return count(array_intersect(array_keys($order->getChanges()), $relevantAttributes)) > 0;
    }
}