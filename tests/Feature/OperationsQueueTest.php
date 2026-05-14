<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OperationsQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_courier_dashboard_uses_live_workload_metrics_and_sections(): void
    {
        $courier = User::factory()->courier()->create();
        $otherCourier = User::factory()->courier()->create();
        $customer = User::factory()->customer()->create();

        $restaurant = Restaurant::create([
            'user_id' => User::factory()->merchant()->create()->id,
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

        $assignedOrder = Order::create([
            'user_id' => $customer->id,
            'courier_id' => $courier->id,
            'restaurant_id' => $restaurant->id,
            'status' => 'on_the_way',
            'subtotal' => 320,
            'delivery_fee' => 49,
            'service_fee' => 25,
            'total' => 394,
            'payment_method' => 'Cash on delivery',
            'delivery_address' => 'Office Tower, Banani 11, Dhaka',
            'delivery_latitude' => 23.7936,
            'delivery_longitude' => 90.4066,
            'driver_notes' => 'Reception handoff.',
            'placed_at' => now()->subMinutes(5),
        ]);
        $assignedOrder->orderItems()->create([
            'menu_item_id' => null,
            'name' => 'Street Wrap Combo',
            'quantity' => 1,
            'unit_price' => 320,
            'line_total' => 320,
        ]);

        $claimableOrder = Order::create([
            'user_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'status' => 'on_the_way',
            'subtotal' => 290,
            'delivery_fee' => 49,
            'service_fee' => 25,
            'total' => 364,
            'payment_method' => 'bKash',
            'delivery_address' => 'House 9, Dhanmondi 27, Dhaka',
            'delivery_latitude' => null,
            'delivery_longitude' => null,
            'driver_notes' => null,
            'placed_at' => now()->subMinutes(9),
        ]);
        $claimableOrder->orderItems()->create([
            'menu_item_id' => null,
            'name' => 'Loaded Rice Bowl',
            'quantity' => 1,
            'unit_price' => 290,
            'line_total' => 290,
        ]);

        $pickupReadyOrder = Order::create([
            'user_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'status' => 'ready',
            'subtotal' => 310,
            'delivery_fee' => 49,
            'service_fee' => 25,
            'total' => 384,
            'payment_method' => 'Cash on delivery',
            'delivery_address' => 'House 18, Mirpur 10, Dhaka',
            'delivery_latitude' => 23.8041,
            'delivery_longitude' => 90.3667,
            'driver_notes' => null,
            'placed_at' => now()->subMinutes(12),
        ]);
        $pickupReadyOrder->orderItems()->create([
            'menu_item_id' => null,
            'name' => 'Courier Pickup Meal',
            'quantity' => 1,
            'unit_price' => 310,
            'line_total' => 310,
        ]);

        $otherCourierOrder = Order::create([
            'user_id' => $customer->id,
            'courier_id' => $otherCourier->id,
            'restaurant_id' => $restaurant->id,
            'status' => 'on_the_way',
            'subtotal' => 330,
            'delivery_fee' => 49,
            'service_fee' => 25,
            'total' => 404,
            'payment_method' => 'Cash on delivery',
            'delivery_address' => 'Hidden from this courier',
            'placed_at' => now()->subMinutes(3),
        ]);
        $otherCourierOrder->orderItems()->create([
            'menu_item_id' => null,
            'name' => 'Other Courier Delivery',
            'quantity' => 1,
            'unit_price' => 330,
            'line_total' => 330,
        ]);

        $completedTodayOrder = Order::create([
            'user_id' => $customer->id,
            'courier_id' => $courier->id,
            'restaurant_id' => $restaurant->id,
            'status' => 'delivered',
            'subtotal' => 150,
            'delivery_fee' => 49,
            'service_fee' => 25,
            'total' => 224,
            'payment_method' => 'Cash on delivery',
            'delivery_address' => 'Completed stop',
            'driver_notes' => null,
            'placed_at' => now()->subHour(),
        ]);
        $completedTodayOrder->orderItems()->create([
            'menu_item_id' => null,
            'name' => 'Delivery complete',
            'quantity' => 1,
            'unit_price' => 150,
            'line_total' => 150,
        ]);

        $response = $this->actingAs($courier)->get(route('courier.dashboard'));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Courier/Dashboard')
            ->where('overview.activeRunsCount', 1)
            ->where('overview.availableClaimsCount', 2)
            ->where('overview.pickupReadyCount', 1)
            ->where('overview.mappedStopsCount', 2)
            ->where('overview.completedTodayCount', 1)
            ->has('assignedDeliveries', 1)
            ->where('assignedDeliveries.0.orderNumber', '#BL-'.str_pad((string) $assignedOrder->id, 4, '0', STR_PAD_LEFT))
            ->where('assignedDeliveries.0.assignment.canComplete', true)
            ->has('availableClaims', 2)
            ->where('availableClaims.0.orderNumber', '#BL-'.str_pad((string) $claimableOrder->id, 4, '0', STR_PAD_LEFT))
            ->where('availableClaims.0.assignment.canClaim', true)
            ->has('pickupQueue', 1)
            ->where('pickupQueue.0.orderNumber', '#BL-'.str_pad((string) $pickupReadyOrder->id, 4, '0', STR_PAD_LEFT))
            ->where('pickupQueue.0.statusKey', 'ready'));
    }

    public function test_unapproved_merchant_is_redirected_away_from_order_operations(): void
    {
        $merchant = User::factory()->merchant()->create([
            'merchant_verified_at' => null,
        ]);

        $this->actingAs($merchant)
            ->from(route('merchant.dashboard'))
            ->get(route('merchant.orders.index'))
            ->assertRedirect(route('merchant.dashboard'))
            ->assertSessionHas('error');
    }

    public function test_merchant_orders_page_uses_active_orders_for_managed_restaurants(): void
    {
        $merchant = User::factory()->merchant()->create();
        $otherMerchant = User::factory()->merchant()->create();
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

        $otherRestaurant = Restaurant::create([
            'user_id' => $otherMerchant->id,
            'name' => 'Green Bowl',
            'slug' => 'green-bowl',
            'category' => 'Healthy Bowls',
            'cuisine' => 'Salads, Smoothies, Protein Plates',
            'min_delivery_time' => 25,
            'max_delivery_time' => 35,
            'rating' => 4.9,
            'delivery_fee' => 29,
            'featured_text' => 'Healthy picks for late-night checkout.',
        ]);

        $visibleOrder = Order::create([
            'user_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'status' => 'preparing',
            'subtotal' => 450,
            'delivery_fee' => 0,
            'service_fee' => 25,
            'total' => 475,
            'payment_method' => 'Cash on delivery',
            'delivery_address' => 'House 12, Road 7, Dhanmondi, Dhaka',
            'delivery_latitude' => 23.7808874,
            'delivery_longitude' => 90.4073486,
            'driver_notes' => 'Call on arrival.',
            'placed_at' => now()->subMinutes(10),
        ]);
        $visibleOrder->orderItems()->create([
            'menu_item_id' => null,
            'name' => 'Smoky Beef Khichuri',
            'quantity' => 1,
            'unit_price' => 450,
            'line_total' => 450,
        ]);

        $hiddenRestaurantOrder = Order::create([
            'user_id' => $customer->id,
            'restaurant_id' => $otherRestaurant->id,
            'status' => 'preparing',
            'subtotal' => 420,
            'delivery_fee' => 29,
            'service_fee' => 25,
            'total' => 474,
            'payment_method' => 'bKash',
            'delivery_address' => 'Banani 11, Dhaka',
            'driver_notes' => null,
            'placed_at' => now()->subMinutes(8),
        ]);
        $hiddenRestaurantOrder->orderItems()->create([
            'menu_item_id' => null,
            'name' => 'Chicken Protein Bowl',
            'quantity' => 1,
            'unit_price' => 420,
            'line_total' => 420,
        ]);

        $hiddenStatusOrder = Order::create([
            'user_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'status' => 'delivered',
            'subtotal' => 90,
            'delivery_fee' => 0,
            'service_fee' => 25,
            'total' => 115,
            'payment_method' => 'Cash on delivery',
            'delivery_address' => 'Old delivery',
            'driver_notes' => null,
            'placed_at' => now()->subHour(),
        ]);
        $hiddenStatusOrder->orderItems()->create([
            'menu_item_id' => null,
            'name' => 'Lemon Mint Cooler',
            'quantity' => 1,
            'unit_price' => 90,
            'line_total' => 90,
        ]);

        $response = $this->actingAs($merchant)->get(route('merchant.orders.index'));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Merchant/Orders/Index')
            ->has('queue', 1)
            ->where('queue.0.orderNumber', '#BL-'.str_pad((string) $visibleOrder->id, 4, '0', STR_PAD_LEFT))
            ->where('queue.0.restaurantName', 'Dhaka Grill')
            ->where('queue.0.customerName', $customer->name)
            ->where('queue.0.summary', '1x Smoky Beef Khichuri')
            ->where('queue.0.destination.address', 'House 12, Road 7, Dhanmondi, Dhaka')
            ->where('queue.0.destination.hasCoordinates', true)
            ->where('queue.0.destination.latitude', 23.7808874)
            ->where('queue.0.destination.longitude', 90.4073486));
    }

    public function test_courier_deliveries_page_uses_active_orders_with_destination_payloads(): void
    {
        $courier = User::factory()->courier()->create();
        $otherCourier = User::factory()->courier()->create();
        $customer = User::factory()->customer()->create();

        $restaurant = Restaurant::create([
            'user_id' => User::factory()->merchant()->create()->id,
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

        $fallbackMapOrder = Order::create([
            'user_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'status' => 'on_the_way',
            'subtotal' => 320,
            'delivery_fee' => 49,
            'service_fee' => 25,
            'total' => 394,
            'payment_method' => 'Cash on delivery',
            'delivery_address' => 'Office Tower, Banani 11, Dhaka',
            'delivery_latitude' => null,
            'delivery_longitude' => null,
            'driver_notes' => 'Reception handoff.',
            'placed_at' => now()->subMinutes(5),
        ]);
        $fallbackMapOrder->orderItems()->create([
            'menu_item_id' => null,
            'name' => 'Street Wrap Combo',
            'quantity' => 1,
            'unit_price' => 320,
            'line_total' => 320,
        ]);

        $pinnedOrder = Order::create([
            'user_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'status' => 'ready',
            'subtotal' => 290,
            'delivery_fee' => 49,
            'service_fee' => 25,
            'total' => 364,
            'payment_method' => 'bKash',
            'delivery_address' => 'House 9, Dhanmondi 27, Dhaka',
            'delivery_latitude' => 23.7529151,
            'delivery_longitude' => 90.3765482,
            'driver_notes' => null,
            'placed_at' => now()->subMinutes(12),
        ]);
        $pinnedOrder->orderItems()->create([
            'menu_item_id' => null,
            'name' => 'Loaded Rice Bowl',
            'quantity' => 1,
            'unit_price' => 290,
            'line_total' => 290,
        ]);

        $otherCourierOrder = Order::create([
            'user_id' => $customer->id,
            'courier_id' => $otherCourier->id,
            'restaurant_id' => $restaurant->id,
            'status' => 'on_the_way',
            'subtotal' => 310,
            'delivery_fee' => 49,
            'service_fee' => 25,
            'total' => 384,
            'payment_method' => 'Cash on delivery',
            'delivery_address' => 'Hidden from this courier',
            'placed_at' => now()->subMinutes(3),
        ]);
        $otherCourierOrder->orderItems()->create([
            'menu_item_id' => null,
            'name' => 'Other Courier Delivery',
            'quantity' => 1,
            'unit_price' => 310,
            'line_total' => 310,
        ]);

        $ignoredOrder = Order::create([
            'user_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'status' => 'delivered',
            'subtotal' => 150,
            'delivery_fee' => 49,
            'service_fee' => 25,
            'total' => 224,
            'payment_method' => 'Cash on delivery',
            'delivery_address' => 'Completed stop',
            'driver_notes' => null,
            'placed_at' => now()->subDay(),
        ]);
        $ignoredOrder->orderItems()->create([
            'menu_item_id' => null,
            'name' => 'Delivery complete',
            'quantity' => 1,
            'unit_price' => 150,
            'line_total' => 150,
        ]);

        $response = $this->actingAs($courier)->get(route('courier.deliveries.index'));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Courier/Deliveries/Index')
            ->has('deliveries', 2)
            ->where('deliveries.0.statusKey', 'on_the_way')
            ->where('deliveries.0.destination.address', 'Office Tower, Banani 11, Dhaka')
            ->where('deliveries.0.destination.hasCoordinates', false)
            ->where('deliveries.0.assignment.canClaim', true)
            ->where('deliveries.0.assignment.courierId', null)
            ->where('deliveries.1.statusKey', 'ready')
            ->where('deliveries.1.destination.hasCoordinates', true)
            ->where('deliveries.1.assignment.canClaim', true)
            ->where('deliveries.1.destination.latitude', 23.7529151)
            ->where('deliveries.1.destination.longitude', 90.3765482));
    }

    public function test_merchant_can_dispatch_their_preparing_order_idempotently(): void
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

        $order = Order::create([
            'user_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'status' => 'preparing',
            'subtotal' => 450,
            'delivery_fee' => 0,
            'service_fee' => 25,
            'total' => 475,
            'payment_method' => 'Cash on delivery',
            'delivery_address' => 'House 12, Road 7, Dhanmondi, Dhaka',
            'placed_at' => now()->subMinutes(10),
        ]);

        $this->actingAs($merchant)
            ->post(route('merchant.orders.dispatch', $order))
            ->assertRedirect(route('merchant.orders.index'));

        $this->assertSame('ready', $order->fresh()->status);

        $this->actingAs($merchant)
            ->post(route('merchant.orders.dispatch', $order))
            ->assertRedirect(route('merchant.orders.index'));

        $this->assertSame('ready', $order->fresh()->status);
    }

    public function test_merchant_cannot_dispatch_another_merchants_order(): void
    {
        $merchant = User::factory()->merchant()->create();
        $otherMerchant = User::factory()->merchant()->create();
        $customer = User::factory()->customer()->create();

        $restaurant = Restaurant::create([
            'user_id' => $otherMerchant->id,
            'name' => 'Green Bowl',
            'slug' => 'green-bowl',
            'category' => 'Healthy Bowls',
            'cuisine' => 'Salads, Smoothies, Protein Plates',
            'min_delivery_time' => 25,
            'max_delivery_time' => 35,
            'rating' => 4.9,
            'delivery_fee' => 29,
            'featured_text' => 'Healthy picks for late-night checkout.',
        ]);

        $order = Order::create([
            'user_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'status' => 'preparing',
            'subtotal' => 420,
            'delivery_fee' => 29,
            'service_fee' => 25,
            'total' => 474,
            'payment_method' => 'Cash on delivery',
            'delivery_address' => 'Banani 11, Dhaka',
            'placed_at' => now()->subMinutes(8),
        ]);

        $this->actingAs($merchant)
            ->post(route('merchant.orders.dispatch', $order))
            ->assertNotFound();

        $this->assertSame('preparing', $order->fresh()->status);
    }

    public function test_courier_can_complete_an_on_the_way_order_idempotently(): void
    {
        $courier = User::factory()->courier()->create();
        $customer = User::factory()->customer()->create();

        $restaurant = Restaurant::create([
            'user_id' => User::factory()->merchant()->create()->id,
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
            'courier_id' => $courier->id,
            'restaurant_id' => $restaurant->id,
            'status' => 'on_the_way',
            'subtotal' => 320,
            'delivery_fee' => 49,
            'service_fee' => 25,
            'total' => 394,
            'payment_method' => 'Cash on delivery',
            'delivery_address' => 'Office Tower, Banani 11, Dhaka',
            'placed_at' => now()->subMinutes(5),
        ]);

        $this->actingAs($courier)
            ->post(route('courier.deliveries.complete', $order))
            ->assertRedirect(route('courier.deliveries.index'));

        $this->assertSame('delivered', $order->fresh()->status);

        $this->actingAs($courier)
            ->post(route('courier.deliveries.complete', $order))
            ->assertRedirect(route('courier.deliveries.index'));

        $this->assertSame('delivered', $order->fresh()->status);
    }

    public function test_courier_can_claim_an_available_delivery_idempotently(): void
    {
        $courier = User::factory()->courier()->create();
        $customer = User::factory()->customer()->create();

        $restaurant = Restaurant::create([
            'user_id' => User::factory()->merchant()->create()->id,
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
            'status' => 'on_the_way',
            'subtotal' => 320,
            'delivery_fee' => 49,
            'service_fee' => 25,
            'total' => 394,
            'payment_method' => 'Cash on delivery',
            'delivery_address' => 'Office Tower, Banani 11, Dhaka',
            'placed_at' => now()->subMinutes(5),
        ]);

        $this->actingAs($courier)
            ->post(route('courier.deliveries.claim', $order))
            ->assertRedirect(route('courier.deliveries.index'));

        $this->assertSame($courier->id, $order->fresh()->courier_id);

        $this->actingAs($courier)
            ->post(route('courier.deliveries.claim', $order))
            ->assertRedirect(route('courier.deliveries.index'));

        $this->assertSame($courier->id, $order->fresh()->courier_id);
    }

    public function test_courier_cannot_claim_another_couriers_delivery(): void
    {
        $courier = User::factory()->courier()->create();
        $otherCourier = User::factory()->courier()->create();
        $customer = User::factory()->customer()->create();

        $restaurant = Restaurant::create([
            'user_id' => User::factory()->merchant()->create()->id,
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
            'courier_id' => $otherCourier->id,
            'restaurant_id' => $restaurant->id,
            'status' => 'on_the_way',
            'subtotal' => 320,
            'delivery_fee' => 49,
            'service_fee' => 25,
            'total' => 394,
            'payment_method' => 'Cash on delivery',
            'delivery_address' => 'Office Tower, Banani 11, Dhaka',
            'placed_at' => now()->subMinutes(5),
        ]);

        $this->actingAs($courier)
            ->post(route('courier.deliveries.claim', $order))
            ->assertNotFound();

        $this->assertSame($otherCourier->id, $order->fresh()->courier_id);
    }

    public function test_admin_dashboard_uses_live_dispatch_metrics_and_board_payloads(): void
    {
        $admin = User::factory()->admin()->create();
        $merchant = User::factory()->merchant()->create();
        $courier = User::factory()->courier()->create();
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

        $preparingOrder = Order::create([
            'user_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'status' => 'preparing',
            'subtotal' => 450,
            'delivery_fee' => 0,
            'service_fee' => 25,
            'total' => 475,
            'payment_method' => 'Cash on delivery',
            'delivery_address' => 'House 12, Road 7, Dhanmondi, Dhaka',
            'delivery_latitude' => 23.7808874,
            'delivery_longitude' => 90.4073486,
            'placed_at' => now()->subMinutes(7),
        ]);
        $preparingOrder->orderItems()->create([
            'menu_item_id' => null,
            'name' => 'Smoky Beef Khichuri',
            'quantity' => 1,
            'unit_price' => 450,
            'line_total' => 450,
        ]);

        $claimedOrder = Order::create([
            'user_id' => $customer->id,
            'courier_id' => $courier->id,
            'restaurant_id' => $restaurant->id,
            'status' => 'on_the_way',
            'subtotal' => 320,
            'delivery_fee' => 0,
            'service_fee' => 25,
            'total' => 345,
            'payment_method' => 'Cash on delivery',
            'delivery_address' => 'Banani 11, Dhaka',
            'delivery_latitude' => 23.7924961,
            'delivery_longitude' => 90.4078068,
            'placed_at' => now()->subMinutes(5),
        ]);
        $claimedOrder->orderItems()->create([
            'menu_item_id' => null,
            'name' => 'Street Wrap Combo',
            'quantity' => 1,
            'unit_price' => 320,
            'line_total' => 320,
        ]);

        $awaitingCourierOrder = Order::create([
            'user_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'status' => 'on_the_way',
            'subtotal' => 290,
            'delivery_fee' => 0,
            'service_fee' => 25,
            'total' => 315,
            'payment_method' => 'bKash',
            'delivery_address' => 'Office Tower, Gulshan 1, Dhaka',
            'placed_at' => now()->subMinutes(3),
        ]);
        $awaitingCourierOrder->orderItems()->create([
            'menu_item_id' => null,
            'name' => 'Loaded Rice Bowl',
            'quantity' => 1,
            'unit_price' => 290,
            'line_total' => 290,
        ]);

        Order::create([
            'user_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'status' => 'delivered',
            'subtotal' => 90,
            'delivery_fee' => 0,
            'service_fee' => 25,
            'total' => 115,
            'payment_method' => 'Cash on delivery',
            'delivery_address' => 'Completed stop',
            'placed_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Dashboard')
            ->where('overview.activeOrders', 3)
            ->where('overview.preparingOrders', 1)
            ->where('overview.awaitingCourier', 1)
            ->where('overview.claimedDeliveries', 1)
            ->where('overview.pinnedDestinations', 2)
            ->has('dispatchBoard', 3)
            ->where('dispatchBoard.0.orderNumber', '#BL-'.str_pad((string) $awaitingCourierOrder->id, 4, '0', STR_PAD_LEFT))
            ->where('dispatchBoard.0.assignment.label', 'Available to claim')
            ->where('dispatchBoard.1.assignment.courierName', $courier->name)
            ->where('dispatchBoard.1.assignment.claimed', true)
            ->where('dispatchBoard.2.statusKey', 'preparing')
            ->where('dispatchBoard.2.destination.hasCoordinates', true));
    }
}