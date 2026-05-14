<?php

namespace Tests\Feature;

use App\Events\MerchantOrderChanged;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class MerchantRealtimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_merchant_order_dispatches_created_realtime_event(): void
    {
        $merchant = User::factory()->merchant()->create();
        $customer = User::factory()->customer()->create();

        $restaurant = Restaurant::create([
            'user_id' => $merchant->id,
            'name' => 'Dhaka Grill',
            'slug' => 'dhaka-grill',
            'category' => 'Bengali',
            'cuisine' => 'Biryani, Kebabs, Grill',
            'min_delivery_time' => 20,
            'max_delivery_time' => 30,
            'rating' => 4.8,
            'delivery_fee' => 0,
            'featured_text' => 'Dinner bundles and charcoal-grilled platters.',
        ]);

        Event::fake([MerchantOrderChanged::class]);

        $order = Order::create([
            'user_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'status' => 'pending',
            'fulfillment_type' => 'delivery',
            'subtotal' => 450,
            'delivery_fee' => 0,
            'service_fee' => 25,
            'total' => 475,
            'payment_method' => 'Cash on delivery',
            'delivery_address' => 'House 12, Road 7, Dhanmondi, Dhaka',
            'placed_at' => now(),
        ]);

        Event::assertDispatched(MerchantOrderChanged::class, function (MerchantOrderChanged $event) use ($order) {
            return $event->eventType === 'created'
                && $event->order->id === $order->id
                && $event->order->restaurant_id === $order->restaurant_id;
        });
    }

    public function test_order_status_change_dispatches_updated_realtime_event(): void
    {
        $merchant = User::factory()->merchant()->create();
        $customer = User::factory()->customer()->create();

        $restaurant = Restaurant::create([
            'user_id' => $merchant->id,
            'name' => 'Spice Lane',
            'slug' => 'spice-lane',
            'category' => 'Fast Food',
            'cuisine' => 'Street Food, Wraps, Rice Bowls',
            'min_delivery_time' => 18,
            'max_delivery_time' => 25,
            'rating' => 4.7,
            'delivery_fee' => 49,
            'featured_text' => 'Popular for quick lunch drops and office meal boxes.',
        ]);

        $order = Order::create([
            'user_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'status' => 'pending',
            'fulfillment_type' => 'delivery',
            'subtotal' => 320,
            'delivery_fee' => 49,
            'service_fee' => 25,
            'total' => 394,
            'payment_method' => 'Cash on delivery',
            'delivery_address' => 'Office Tower, Banani 11, Dhaka',
            'placed_at' => now()->subMinutes(5),
        ]);

        Event::fake([MerchantOrderChanged::class]);

        $order->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        Event::assertDispatched(MerchantOrderChanged::class, function (MerchantOrderChanged $event) use ($order) {
            return $event->eventType === 'updated'
                && $event->order->id === $order->id
                && $event->order->status === 'accepted';
        });
    }
}