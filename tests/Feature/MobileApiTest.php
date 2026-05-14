<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_a_mobile_token(): void
    {
        $user = User::factory()->customer()->create();

        $response = $this->postJson('/api/v1/tokens', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'iPhone 16 Pro',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.role', 'customer');

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_suspended_user_can_not_create_or_use_mobile_api_access(): void
    {
        $user = User::factory()->customer()->create([
            'is_suspended' => true,
            'suspended_at' => now(),
        ]);

        $this->postJson('/api/v1/tokens', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'iPhone 16 Pro',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/me')
            ->assertForbidden()
            ->assertJsonPath('message', 'Your account has been suspended. Contact BizLami support to restore access.');
    }

    public function test_unapproved_merchant_cannot_use_merchant_mobile_endpoints(): void
    {
        $merchant = User::factory()->merchant()->create([
            'merchant_verified_at' => null,
        ]);

        Sanctum::actingAs($merchant);

        $this->getJson('/api/v1/merchant/orders')
            ->assertForbidden()
            ->assertJsonPath('message', 'Your merchant account is awaiting approval. BizLami will unlock menu and order tools after review.');
    }

    public function test_merchant_mobile_overview_endpoint_returns_live_workspace_sections(): void
    {
        $merchant = User::factory()->merchant()->create();
        $customer = User::factory()->customer()->create();

        $visibleRestaurant = Restaurant::create([
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
            'is_visible' => true,
        ]);

        $hiddenRestaurant = Restaurant::create([
            'user_id' => $merchant->id,
            'name' => 'Green Bowl',
            'slug' => 'green-bowl',
            'category' => 'Healthy Bowls',
            'cuisine' => 'Salads, Smoothies, Protein Plates',
            'min_delivery_time' => 25,
            'max_delivery_time' => 35,
            'rating' => 4.9,
            'delivery_fee' => 29,
            'featured_text' => 'Healthy picks for late-night checkout.',
            'is_visible' => false,
        ]);

        MenuItem::create([
            'restaurant_id' => $visibleRestaurant->id,
            'name' => 'Smoky Beef Khichuri',
            'slug' => 'dhaka-grill-smoky-beef-khichuri',
            'category' => 'Rice Bowls',
            'description' => 'Slow-cooked beef with fragrant khichuri and achar.',
            'price' => 450,
            'is_available' => true,
        ]);

        MenuItem::create([
            'restaurant_id' => $visibleRestaurant->id,
            'name' => 'Lemon Mint Cooler',
            'slug' => 'dhaka-grill-lemon-mint-cooler',
            'category' => 'Drinks',
            'description' => 'Mint, lemon, and sparkling finish.',
            'price' => 90,
            'is_available' => false,
        ]);

        MenuItem::create([
            'restaurant_id' => $hiddenRestaurant->id,
            'name' => 'Chicken Protein Bowl',
            'slug' => 'green-bowl-chicken-protein-bowl',
            'category' => 'Protein Plates',
            'description' => 'Roasted chicken with grains and greens.',
            'price' => 420,
            'is_available' => true,
        ]);

        $preparingOrder = Order::create([
            'user_id' => $customer->id,
            'restaurant_id' => $visibleRestaurant->id,
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
            'placed_at' => now()->subMinutes(15),
        ]);
        $preparingOrder->orderItems()->create([
            'menu_item_id' => null,
            'name' => 'Smoky Beef Khichuri',
            'quantity' => 1,
            'unit_price' => 450,
            'line_total' => 450,
        ]);

        $onTheWayOrder = Order::create([
            'user_id' => $customer->id,
            'restaurant_id' => $hiddenRestaurant->id,
            'status' => 'on_the_way',
            'subtotal' => 420,
            'delivery_fee' => 29,
            'service_fee' => 25,
            'total' => 474,
            'payment_method' => 'bKash',
            'delivery_address' => 'Banani 11, Dhaka',
            'delivery_latitude' => null,
            'delivery_longitude' => null,
            'driver_notes' => null,
            'placed_at' => now()->subMinutes(5),
        ]);
        $onTheWayOrder->orderItems()->create([
            'menu_item_id' => null,
            'name' => 'Chicken Protein Bowl',
            'quantity' => 1,
            'unit_price' => 420,
            'line_total' => 420,
        ]);

        Sanctum::actingAs($merchant);

        $this->getJson('/api/v1/merchant/overview')
            ->assertOk()
            ->assertJsonPath('data.overview.activeOrdersCount', 2)
            ->assertJsonPath('data.overview.preparingOrdersCount', 1)
            ->assertJsonPath('data.overview.ordersTodayCount', 2)
            ->assertJsonPath('data.overview.liveMenuItemsCount', 2)
            ->assertJsonPath('data.overview.pausedMenuItemsCount', 1)
            ->assertJsonPath('data.overview.restaurantsCount', 2)
            ->assertJsonPath('data.overview.visibleRestaurantsCount', 1)
            ->assertJsonPath('data.overview.hiddenRestaurantsCount', 1)
            ->assertJsonPath('data.overview.pinnedDestinationsCount', 1)
            ->assertJsonCount(2, 'data.recentOrders')
            ->assertJsonPath('data.recentOrders.0.orderNumber', '#BL-'.str_pad((string) $onTheWayOrder->id, 4, '0', STR_PAD_LEFT))
            ->assertJsonPath('data.recentOrders.0.restaurant.name', 'Green Bowl')
                ->assertJsonCount(2, 'data.restaurants')
            ->assertJsonCount(3, 'data.recentMenuItems');
    }

    public function test_courier_mobile_overview_endpoint_returns_live_workload_sections(): void
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
            'status' => 'preparing',
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

        Sanctum::actingAs($courier);

        $this->getJson('/api/v1/courier/overview')
            ->assertOk()
            ->assertJsonPath('data.overview.activeRunsCount', 1)
            ->assertJsonPath('data.overview.availableClaimsCount', 1)
            ->assertJsonPath('data.overview.pickupReadyCount', 1)
            ->assertJsonPath('data.overview.mappedStopsCount', 2)
            ->assertJsonPath('data.overview.completedTodayCount', 1)
            ->assertJsonCount(1, 'data.assignedDeliveries')
            ->assertJsonPath('data.assignedDeliveries.0.orderNumber', '#BL-'.str_pad((string) $assignedOrder->id, 4, '0', STR_PAD_LEFT))
            ->assertJsonPath('data.assignedDeliveries.0.assignment.canComplete', true)
            ->assertJsonCount(1, 'data.availableClaims')
            ->assertJsonPath('data.availableClaims.0.orderNumber', '#BL-'.str_pad((string) $claimableOrder->id, 4, '0', STR_PAD_LEFT))
            ->assertJsonPath('data.availableClaims.0.assignment.canClaim', true)
            ->assertJsonCount(1, 'data.pickupQueue')
            ->assertJsonPath('data.pickupQueue.0.orderNumber', '#BL-'.str_pad((string) $pickupReadyOrder->id, 4, '0', STR_PAD_LEFT))
            ->assertJsonPath('data.pickupQueue.0.status.key', 'preparing');
    }

    public function test_customer_mobile_overview_endpoint_returns_home_sections_for_app_bootstrap(): void
    {
        $customer = User::factory()->customer()->create();

        $greenBowl = Restaurant::create([
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

        $dhakaGrill = Restaurant::create([
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

        $cartMenuItem = MenuItem::create([
            'restaurant_id' => $greenBowl->id,
            'name' => 'Chicken Protein Bowl',
            'slug' => 'green-bowl-chicken-protein-bowl',
            'category' => 'Protein Plates',
            'description' => 'Grilled chicken, brown rice, greens, and yogurt sauce.',
            'image_path' => 'seed://demo-foods/chicken-protein-bowl.svg',
            'price' => 420,
            'is_available' => true,
        ]);

        MenuItem::create([
            'restaurant_id' => $greenBowl->id,
            'name' => 'Yogurt Parfait',
            'slug' => 'green-bowl-yogurt-parfait',
            'category' => 'Desserts',
            'description' => 'Greek yogurt, fruit compote, and toasted granola.',
            'price' => 450,
            'is_available' => true,
        ]);

        MenuItem::create([
            'restaurant_id' => $dhakaGrill->id,
            'name' => 'Smoky Beef Khichuri',
            'slug' => 'dhaka-grill-smoky-beef-khichuri',
            'category' => 'Rice Bowls',
            'description' => 'Slow-cooked beef with fragrant khichuri and achar.',
            'price' => 430,
            'is_available' => true,
        ]);

        $cartOrder = Order::create([
            'user_id' => $customer->id,
            'restaurant_id' => $greenBowl->id,
            'status' => 'cart',
            'subtotal' => 420,
            'delivery_fee' => 29,
            'service_fee' => 25,
            'total' => 474,
            'payment_method' => 'Cash on delivery',
            'idempotency_key' => 'mobile-overview-cart-key',
            'delivery_address' => '',
            'delivery_latitude' => null,
            'delivery_longitude' => null,
            'driver_notes' => null,
            'placed_at' => null,
        ]);
        $cartOrder->orderItems()->create([
            'menu_item_id' => $cartMenuItem->id,
            'name' => 'Chicken Protein Bowl',
            'quantity' => 1,
            'unit_price' => 420,
            'line_total' => 420,
        ]);

        $preparingOrder = Order::create([
            'user_id' => $customer->id,
            'restaurant_id' => $greenBowl->id,
            'status' => 'preparing',
            'subtotal' => 420,
            'delivery_fee' => 29,
            'service_fee' => 25,
            'total' => 474,
            'payment_method' => 'Cash on delivery',
            'delivery_address' => 'House 12, Road 7, Dhanmondi, Dhaka',
            'delivery_latitude' => 23.7808874,
            'delivery_longitude' => 90.4073486,
            'driver_notes' => 'Call on arrival.',
            'placed_at' => now()->subMinutes(12),
        ]);
        $preparingOrder->orderItems()->create([
            'menu_item_id' => $cartMenuItem->id,
            'name' => 'Chicken Protein Bowl',
            'quantity' => 1,
            'unit_price' => 420,
            'line_total' => 420,
        ]);

        $deliveredOrder = Order::create([
            'user_id' => $customer->id,
            'restaurant_id' => $dhakaGrill->id,
            'status' => 'delivered',
            'subtotal' => 430,
            'delivery_fee' => 0,
            'service_fee' => 25,
            'total' => 455,
            'payment_method' => 'bKash',
            'delivery_address' => 'Banani 11, Dhaka',
            'delivery_latitude' => null,
            'delivery_longitude' => null,
            'driver_notes' => null,
            'placed_at' => now()->subHours(2),
        ]);
        $deliveredOrder->orderItems()->create([
            'menu_item_id' => null,
            'name' => 'Smoky Beef Khichuri',
            'quantity' => 1,
            'unit_price' => 430,
            'line_total' => 430,
        ]);

        Sanctum::actingAs($customer);

        $this->getJson('/api/v1/customer/overview')
            ->assertOk()
            ->assertJsonPath('data.overview.restaurantsCount', 2)
            ->assertJsonPath('data.overview.foodsCount', 3)
            ->assertJsonPath('data.overview.activeOrdersCount', 1)
            ->assertJsonPath('data.overview.recentOrdersCount', 2)
            ->assertJsonPath('data.overview.cartItemsCount', 1)
            ->assertJsonPath('data.overview.cartTotalValue', 474)
            ->assertJsonPath('data.overview.averageDeliveryMinutes', 28)
            ->assertJsonPath('data.overview.highestRatedRestaurant', 'Green Bowl')
            ->assertJsonCount(2, 'data.spotlightRestaurants')
            ->assertJsonPath('data.spotlightRestaurants.0.name', 'Green Bowl')
            ->assertJsonCount(3, 'data.spotlightFoods')
            ->assertJsonPath('data.spotlightFoods.0.name', 'Chicken Protein Bowl')
            ->assertJsonPath('data.spotlightFoods.0.imageUrl', '/images/demo-foods/chicken-protein-bowl.svg')
            ->assertJsonPath('data.cart.restaurant', 'Green Bowl')
            ->assertJsonPath('data.cart.total.value', 474)
            ->assertJsonCount(2, 'data.recentOrders')
            ->assertJsonPath('data.recentOrders.0.orderNumber', '#BL-'.str_pad((string) $preparingOrder->id, 4, '0', STR_PAD_LEFT))
            ->assertJsonPath('data.recentOrders.0.status.key', 'preparing')
            ->assertJsonPath('data.recentOrders.0.canTrack', true)
            ->assertJsonPath('data.recentOrders.1.orderNumber', '#BL-'.str_pad((string) $deliveredOrder->id, 4, '0', STR_PAD_LEFT))
            ->assertJsonPath('data.recentOrders.1.canTrack', false)
            ->assertJsonPath('data.recentOrders.1.canReorder', false)
            ->assertJsonPath('data.recentOrders.1.reorder.visible', true)
            ->assertJsonPath('data.recentOrders.1.reorder.label', 'Changed since last order')
            ->assertJsonPath('data.recentOrders.1.reorder.items.0.name', 'Smoky Beef Khichuri')
            ->assertJsonPath('data.recentOrders.1.reorder.items.0.reason', 'This dish is no longer listed on the kitchen menu.');
    }

    public function test_customer_mobile_can_open_order_detail_endpoint(): void
    {
        $customer = User::factory()->customer()->create();

        $restaurant = Restaurant::create([
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
            'status' => 'on_the_way',
            'subtotal' => 420,
            'delivery_fee' => 29,
            'service_fee' => 25,
            'total' => 474,
            'payment_method' => 'bKash',
            'delivery_address' => 'House 12, Road 7, Dhanmondi, Dhaka',
            'delivery_latitude' => 23.7808874,
            'delivery_longitude' => 90.4073486,
            'driver_notes' => 'Call on arrival.',
            'placed_at' => now()->subMinutes(15),
        ]);
        $order->orderItems()->create([
            'menu_item_id' => null,
            'name' => 'Chicken Protein Bowl',
            'quantity' => 1,
            'unit_price' => 420,
            'line_total' => 420,
        ]);

        Sanctum::actingAs($customer);

        $this->getJson("/api/v1/customer/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.orderNumber', '#BL-'.str_pad((string) $order->id, 4, '0', STR_PAD_LEFT))
            ->assertJsonPath('data.restaurant.name', 'Green Bowl')
            ->assertJsonPath('data.status.key', 'on_the_way')
            ->assertJsonPath('data.guidance.title', 'Your rider is currently on the way.')
            ->assertJsonPath('data.timeline.0.state', 'complete')
            ->assertJsonPath('data.timeline.1.state', 'complete')
            ->assertJsonPath('data.timeline.2.state', 'current')
            ->assertJsonPath('data.timeline.3.state', 'upcoming')
            ->assertJsonPath('data.destination.address', 'House 12, Road 7, Dhanmondi, Dhaka')
            ->assertJsonPath('data.destination.hasCoordinates', true)
            ->assertJsonPath('data.paymentMethod', 'bKash')
            ->assertJsonPath('data.driverNotes', 'Call on arrival.')
            ->assertJsonPath('data.items.0.name', 'Chicken Protein Bowl')
            ->assertJsonPath('data.items.0.unitPrice.formatted', '₱420')
            ->assertJsonPath('data.totals.total.formatted', '₱474')
            ->assertJsonPath('data.canTrack', true)
            ->assertJsonPath('data.canReorder', false)
            ->assertJsonPath('data.reorder.visible', false)
            ->assertJsonPath('data.reorder.items', []);
    }

    public function test_customer_mobile_order_detail_exposes_changed_reorder_items(): void
    {
        $customer = User::factory()->customer()->create();
        $restaurant = Restaurant::create([
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
            'status' => 'delivered',
            'subtotal' => 190,
            'delivery_fee' => 0,
            'service_fee' => 25,
            'total' => 215,
            'payment_method' => 'bKash',
            'delivery_address' => 'Banani 11, Dhaka',
            'placed_at' => now()->subDay(),
        ]);
        $order->orderItems()->create([
            'menu_item_id' => null,
            'name' => 'Greek Yogurt Parfait',
            'quantity' => 1,
            'unit_price' => 190,
            'line_total' => 190,
        ]);
        MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Berry Yogurt Bowl',
            'slug' => 'berry-yogurt-bowl',
            'category' => 'Breakfast',
            'description' => 'Greek yogurt, berries, and house granola.',
            'image_path' => null,
            'price' => 210,
            'is_available' => true,
        ]);
        MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Tropical Fruit Granola',
            'slug' => 'tropical-fruit-granola',
            'category' => 'Breakfast',
            'description' => 'Fruit, granola, and chilled yogurt.',
            'image_path' => null,
            'price' => 230,
            'is_available' => true,
        ]);

        Sanctum::actingAs($customer);

        $this->getJson("/api/v1/customer/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.canReorder', false)
            ->assertJsonPath('data.reorder.visible', true)
            ->assertJsonPath('data.reorder.label', 'Changed since last order')
            ->assertJsonPath('data.reorder.items.0.name', 'Greek Yogurt Parfait')
            ->assertJsonPath('data.reorder.items.0.reason', 'This dish is no longer listed on the kitchen menu.')
            ->assertJsonPath('data.reorder.items.0.suggestions.0.name', 'Berry Yogurt Bowl')
            ->assertJsonPath('data.reorder.items.0.suggestions.0.price.formatted', '₱210');
    }

    public function test_customer_mobile_can_reorder_a_delivered_order_into_cart(): void
    {
        $customer = User::factory()->customer()->create();
        $restaurant = Restaurant::create([
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

        $khichuri = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Smoky Beef Khichuri',
            'slug' => 'dhaka-grill-smoky-beef-khichuri',
            'category' => 'Rice Bowls',
            'description' => 'Slow-cooked beef with fragrant khichuri and achar.',
            'price' => 450,
            'is_available' => true,
        ]);

        $parfait = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Greek Yogurt Parfait',
            'slug' => 'dhaka-grill-greek-yogurt-parfait',
            'category' => 'Desserts',
            'description' => 'Layered yogurt, berries, and toasted granola.',
            'price' => 190,
            'is_available' => true,
        ]);

        $order = Order::create([
            'user_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'status' => 'delivered',
            'subtotal' => 1090,
            'delivery_fee' => 0,
            'service_fee' => 25,
            'total' => 1115,
            'payment_method' => 'Cash on delivery',
            'delivery_address' => 'House 12, Road 7, Dhanmondi, Dhaka',
            'driver_notes' => 'Call on arrival.',
            'placed_at' => now()->subDay(),
        ]);
        $order->orderItems()->create([
            'menu_item_id' => $khichuri->id,
            'name' => 'Smoky Beef Khichuri',
            'quantity' => 2,
            'unit_price' => 450,
            'line_total' => 900,
        ]);
        $order->orderItems()->create([
            'menu_item_id' => $parfait->id,
            'name' => 'Greek Yogurt Parfait',
            'quantity' => 1,
            'unit_price' => 190,
            'line_total' => 190,
        ]);

        Sanctum::actingAs($customer);

        $this->getJson("/api/v1/customer/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.canReorder', true)
            ->assertJsonPath('data.reorder.label', 'Ready to reorder');

        $this->postJson("/api/v1/customer/orders/{$order->id}/reorder")
            ->assertOk()
            ->assertJsonPath('data.restaurant', 'Dhaka Grill')
            ->assertJsonCount(2, 'data.items')
            ->assertJsonPath('data.items.0.quantityValue', 2)
            ->assertJsonPath('data.total.value', 1115)
            ->assertJsonPath('data.idempotencyKey', fn ($value) => filled($value));

        $cartOrder = $customer->fresh()->orders()->where('status', 'cart')->first();

        $this->assertNotNull($cartOrder);
        $this->assertSame($restaurant->id, $cartOrder->restaurant_id);
        $this->assertSame(1090, $cartOrder->subtotal);
        $this->assertSame(1115, $cartOrder->total);
    }

    public function test_customer_mobile_reorder_requires_confirmation_before_replacing_a_cart_from_another_restaurant(): void
    {
        $customer = User::factory()->customer()->create();
        $greenBowl = Restaurant::create([
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
        $greenBowlItem = MenuItem::create([
            'restaurant_id' => $greenBowl->id,
            'name' => 'Chicken Protein Bowl',
            'slug' => 'green-bowl-chicken-protein-bowl',
            'category' => 'Protein Plates',
            'description' => 'Grilled chicken, brown rice, greens, and yogurt sauce.',
            'price' => 420,
            'is_available' => true,
        ]);
        $dhakaGrill = Restaurant::create([
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
        $khichuri = MenuItem::create([
            'restaurant_id' => $dhakaGrill->id,
            'name' => 'Smoky Beef Khichuri',
            'slug' => 'dhaka-grill-smoky-beef-khichuri',
            'category' => 'Rice Bowls',
            'description' => 'Slow-cooked beef with fragrant khichuri and achar.',
            'price' => 450,
            'is_available' => true,
        ]);
        $order = Order::create([
            'user_id' => $customer->id,
            'restaurant_id' => $dhakaGrill->id,
            'status' => 'delivered',
            'subtotal' => 450,
            'delivery_fee' => 0,
            'service_fee' => 25,
            'total' => 475,
            'payment_method' => 'Cash on delivery',
            'delivery_address' => 'House 12, Road 7, Dhanmondi, Dhaka',
            'placed_at' => now()->subDay(),
        ]);
        $order->orderItems()->create([
            'menu_item_id' => $khichuri->id,
            'name' => 'Smoky Beef Khichuri',
            'quantity' => 1,
            'unit_price' => 450,
            'line_total' => 450,
        ]);

        Sanctum::actingAs($customer);

        $this->postJson("/api/v1/customer/menu-items/{$greenBowlItem->id}/cart")
            ->assertOk();

        $this->postJson("/api/v1/customer/orders/{$order->id}/reorder")
            ->assertStatus(409)
            ->assertJsonPath('message', 'Your cart already contains items from Green Bowl. Confirm replacing it before reordering #BL-'.str_pad((string) $order->id, 4, '0', STR_PAD_LEFT).'.')
            ->assertJsonPath('conflict.type', 'replace_cart')
            ->assertJsonPath('conflict.currentCart.restaurantName', 'Green Bowl')
            ->assertJsonPath('conflict.currentCart.itemsCount', 1)
            ->assertJsonPath('conflict.incoming.restaurantName', 'Dhaka Grill')
            ->assertJsonPath('conflict.incoming.orderId', $order->id);

        $cartOrder = $customer->fresh()->orders()->where('status', 'cart')->firstOrFail();

        $this->assertSame($greenBowl->id, $cartOrder->restaurant_id);
    }

    public function test_customer_mobile_can_confirm_replacing_a_cart_when_reordering(): void
    {
        $customer = User::factory()->customer()->create();
        $greenBowl = Restaurant::create([
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
        $greenBowlItem = MenuItem::create([
            'restaurant_id' => $greenBowl->id,
            'name' => 'Chicken Protein Bowl',
            'slug' => 'green-bowl-chicken-protein-bowl',
            'category' => 'Protein Plates',
            'description' => 'Grilled chicken, brown rice, greens, and yogurt sauce.',
            'price' => 420,
            'is_available' => true,
        ]);
        $dhakaGrill = Restaurant::create([
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
        $khichuri = MenuItem::create([
            'restaurant_id' => $dhakaGrill->id,
            'name' => 'Smoky Beef Khichuri',
            'slug' => 'dhaka-grill-smoky-beef-khichuri',
            'category' => 'Rice Bowls',
            'description' => 'Slow-cooked beef with fragrant khichuri and achar.',
            'price' => 450,
            'is_available' => true,
        ]);
        $order = Order::create([
            'user_id' => $customer->id,
            'restaurant_id' => $dhakaGrill->id,
            'status' => 'delivered',
            'subtotal' => 450,
            'delivery_fee' => 0,
            'service_fee' => 25,
            'total' => 475,
            'payment_method' => 'Cash on delivery',
            'delivery_address' => 'House 12, Road 7, Dhanmondi, Dhaka',
            'placed_at' => now()->subDay(),
        ]);
        $order->orderItems()->create([
            'menu_item_id' => $khichuri->id,
            'name' => 'Smoky Beef Khichuri',
            'quantity' => 1,
            'unit_price' => 450,
            'line_total' => 450,
        ]);

        Sanctum::actingAs($customer);

        $this->postJson("/api/v1/customer/menu-items/{$greenBowlItem->id}/cart")
            ->assertOk();

        $this->postJson("/api/v1/customer/orders/{$order->id}/reorder", [
            'replace_cart' => true,
        ])
            ->assertOk()
            ->assertJsonPath('data.restaurant', 'Dhaka Grill')
            ->assertJsonPath('data.items.0.name', 'Smoky Beef Khichuri')
            ->assertJsonPath('data.total.value', 475);

        $cartOrder = $customer->fresh()->orders()->where('status', 'cart')->firstOrFail();

        $this->assertSame($dhakaGrill->id, $cartOrder->restaurant_id);
        $this->assertDatabaseMissing('order_items', [
            'order_id' => $cartOrder->id,
            'menu_item_id' => $greenBowlItem->id,
        ]);
    }

    public function test_customer_mobile_order_detail_endpoint_is_limited_to_owned_non_cart_orders(): void
    {
        $customer = User::factory()->customer()->create();
        $otherCustomer = User::factory()->customer()->create();

        $restaurant = Restaurant::create([
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

        $otherOrder = Order::create([
            'user_id' => $otherCustomer->id,
            'restaurant_id' => $restaurant->id,
            'status' => 'delivered',
            'subtotal' => 420,
            'delivery_fee' => 29,
            'service_fee' => 25,
            'total' => 474,
            'payment_method' => 'Cash on delivery',
            'delivery_address' => 'Banani 11, Dhaka',
            'delivery_latitude' => null,
            'delivery_longitude' => null,
            'driver_notes' => null,
            'placed_at' => now()->subHour(),
        ]);

        $cartOrder = Order::create([
            'user_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'status' => 'cart',
            'subtotal' => 420,
            'delivery_fee' => 29,
            'service_fee' => 25,
            'total' => 474,
            'payment_method' => 'Cash on delivery',
            'idempotency_key' => 'customer-mobile-cart-order',
            'delivery_address' => '',
            'delivery_latitude' => null,
            'delivery_longitude' => null,
            'driver_notes' => null,
            'placed_at' => null,
        ]);

        Sanctum::actingAs($customer);

        $this->getJson("/api/v1/customer/orders/{$otherOrder->id}")
            ->assertNotFound();

        $this->getJson("/api/v1/customer/orders/{$cartOrder->id}")
            ->assertNotFound();
    }

    public function test_customer_mobile_orders_endpoint_returns_order_history_cards_and_meta(): void
    {
        $customer = User::factory()->customer()->create();
        $otherCustomer = User::factory()->customer()->create();

        $greenBowl = Restaurant::create([
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

        $dhakaGrill = Restaurant::create([
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

        $latestOrder = Order::create([
            'user_id' => $customer->id,
            'restaurant_id' => $greenBowl->id,
            'status' => 'on_the_way',
            'subtotal' => 420,
            'delivery_fee' => 29,
            'service_fee' => 25,
            'total' => 474,
            'payment_method' => 'bKash',
            'delivery_address' => 'House 12, Road 7, Dhanmondi, Dhaka',
            'placed_at' => now()->subMinutes(10),
        ]);
        $latestOrder->orderItems()->create([
            'menu_item_id' => null,
            'name' => 'Chicken Protein Bowl',
            'quantity' => 1,
            'unit_price' => 420,
            'line_total' => 420,
        ]);

        $middleOrder = Order::create([
            'user_id' => $customer->id,
            'restaurant_id' => $greenBowl->id,
            'status' => 'preparing',
            'subtotal' => 410,
            'delivery_fee' => 29,
            'service_fee' => 25,
            'total' => 464,
            'payment_method' => 'Cash on delivery',
            'delivery_address' => 'Banani 11, Dhaka',
            'placed_at' => now()->subMinutes(30),
        ]);
        $middleOrder->orderItems()->create([
            'menu_item_id' => null,
            'name' => 'Yogurt Parfait',
            'quantity' => 1,
            'unit_price' => 410,
            'line_total' => 410,
        ]);

        $oldestOrder = Order::create([
            'user_id' => $customer->id,
            'restaurant_id' => $dhakaGrill->id,
            'status' => 'delivered',
            'subtotal' => 430,
            'delivery_fee' => 0,
            'service_fee' => 25,
            'total' => 455,
            'payment_method' => 'bKash',
            'delivery_address' => 'Gulshan 2, Dhaka',
            'placed_at' => now()->subHours(4),
        ]);
        $oldestOrder->orderItems()->create([
            'menu_item_id' => null,
            'name' => 'Smoky Beef Khichuri',
            'quantity' => 1,
            'unit_price' => 430,
            'line_total' => 430,
        ]);

        Order::create([
            'user_id' => $customer->id,
            'restaurant_id' => $greenBowl->id,
            'status' => 'cart',
            'subtotal' => 420,
            'delivery_fee' => 29,
            'service_fee' => 25,
            'total' => 474,
            'payment_method' => 'Cash on delivery',
            'idempotency_key' => 'customer-mobile-orders-cart',
            'delivery_address' => '',
            'placed_at' => null,
        ]);

        Order::create([
            'user_id' => $otherCustomer->id,
            'restaurant_id' => $dhakaGrill->id,
            'status' => 'delivered',
            'subtotal' => 430,
            'delivery_fee' => 0,
            'service_fee' => 25,
            'total' => 455,
            'payment_method' => 'Cash on delivery',
            'delivery_address' => 'Hidden from this customer',
            'placed_at' => now()->subHour(),
        ]);

        Sanctum::actingAs($customer);

        $this->getJson('/api/v1/customer/orders')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.orderNumber', '#BL-'.str_pad((string) $latestOrder->id, 4, '0', STR_PAD_LEFT))
            ->assertJsonPath('data.0.restaurant.name', 'Green Bowl')
            ->assertJsonPath('data.0.status.key', 'on_the_way')
            ->assertJsonPath('data.0.canTrack', true)
            ->assertJsonPath('data.2.orderNumber', '#BL-'.str_pad((string) $oldestOrder->id, 4, '0', STR_PAD_LEFT))
            ->assertJsonPath('data.2.canTrack', false)
            ->assertJsonPath('data.2.canReorder', false)
            ->assertJsonPath('data.2.reorder.label', 'Changed since last order')
            ->assertJsonPath('data.2.reorder.items.0.name', 'Smoky Beef Khichuri')
            ->assertJsonPath('meta.filters.status', 'all')
            ->assertJsonPath('meta.overview.activeOrdersCount', 2)
            ->assertJsonPath('meta.overview.completedOrdersCount', 1)
            ->assertJsonPath('meta.pagination.total', 3)
            ->assertJsonPath('meta.pagination.currentPage', 1)
            ->assertJsonPath('meta.pagination.lastPage', 1);
    }

    public function test_customer_mobile_orders_endpoint_can_filter_active_and_history_lists(): void
    {
        $customer = User::factory()->customer()->create();

        $restaurant = Restaurant::create([
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

        $activeOrder = Order::create([
            'user_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'status' => 'preparing',
            'subtotal' => 420,
            'delivery_fee' => 29,
            'service_fee' => 25,
            'total' => 474,
            'payment_method' => 'Cash on delivery',
            'delivery_address' => 'House 12, Road 7, Dhanmondi, Dhaka',
            'placed_at' => now()->subMinutes(20),
        ]);

        $completedOrder = Order::create([
            'user_id' => $customer->id,
            'restaurant_id' => $restaurant->id,
            'status' => 'delivered',
            'subtotal' => 420,
            'delivery_fee' => 29,
            'service_fee' => 25,
            'total' => 474,
            'payment_method' => 'bKash',
            'delivery_address' => 'Banani 11, Dhaka',
            'placed_at' => now()->subHours(2),
        ]);

        Sanctum::actingAs($customer);

        $this->getJson('/api/v1/customer/orders?status=active')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.orderNumber', '#BL-'.str_pad((string) $activeOrder->id, 4, '0', STR_PAD_LEFT))
            ->assertJsonPath('meta.filters.status', 'active')
            ->assertJsonPath('meta.pagination.total', 1);

        $this->getJson('/api/v1/customer/orders?status=history')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.orderNumber', '#BL-'.str_pad((string) $completedOrder->id, 4, '0', STR_PAD_LEFT))
            ->assertJsonPath('data.0.canTrack', false)
            ->assertJsonPath('meta.filters.status', 'history')
            ->assertJsonPath('meta.pagination.total', 1);
    }

    public function test_customer_mobile_catalog_and_cart_endpoints_return_browse_data_and_seeded_image_urls(): void
    {
        $customer = User::factory()->customer()->create();
        $restaurant = Restaurant::create([
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

        $menuItem = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Chicken Protein Bowl',
            'slug' => 'green-bowl-chicken-protein-bowl',
            'category' => 'Protein Plates',
            'description' => 'Grilled chicken, brown rice, greens, and yogurt sauce.',
            'image_path' => 'seed://demo-foods/chicken-protein-bowl.svg',
            'price' => 420,
            'is_available' => true,
        ]);

        Sanctum::actingAs($customer);

        $this->getJson('/api/v1/customer/restaurants')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Green Bowl')
            ->assertJsonPath('data.0.featuredImageUrl', '/images/demo-foods/chicken-protein-bowl.svg');

        $this->getJson("/api/v1/customer/restaurants/{$restaurant->slug}")
            ->assertOk()
            ->assertJsonPath('data.menuItems.0.imageUrl', '/images/demo-foods/chicken-protein-bowl.svg');

        $this->getJson('/api/v1/customer/cart')
            ->assertOk()
            ->assertJsonPath('data.items', []);

        $this->postJson("/api/v1/customer/menu-items/{$menuItem->id}/cart")
            ->assertOk()
            ->assertJsonPath('data.restaurant', 'Green Bowl')
            ->assertJsonPath('data.items.0.quantityValue', 1)
            ->assertJsonPath('data.total.value', 474);

        $this->postJson("/api/v1/customer/cart/items/{$menuItem->id}/increment")
            ->assertOk()
            ->assertJsonPath('data.items.0.quantityValue', 2)
            ->assertJsonPath('data.total.value', 894);

        $this->postJson("/api/v1/customer/cart/items/{$menuItem->id}/decrement")
            ->assertOk()
            ->assertJsonPath('data.items.0.quantityValue', 1)
            ->assertJsonPath('data.idempotencyKey', fn ($value) => filled($value));
    }

    public function test_customer_mobile_cart_store_requires_confirmation_before_replacing_a_cart_from_another_restaurant(): void
    {
        $customer = User::factory()->customer()->create();
        $greenBowl = Restaurant::create([
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
        $greenBowlItem = MenuItem::create([
            'restaurant_id' => $greenBowl->id,
            'name' => 'Chicken Protein Bowl',
            'slug' => 'green-bowl-chicken-protein-bowl',
            'category' => 'Protein Plates',
            'description' => 'Grilled chicken, brown rice, greens, and yogurt sauce.',
            'price' => 420,
            'is_available' => true,
        ]);
        $dhakaGrill = Restaurant::create([
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
        $dhakaGrillItem = MenuItem::create([
            'restaurant_id' => $dhakaGrill->id,
            'name' => 'Smoky Beef Khichuri',
            'slug' => 'dhaka-grill-smoky-beef-khichuri',
            'category' => 'Rice Bowls',
            'description' => 'Slow-cooked beef with fragrant khichuri and achar.',
            'price' => 450,
            'is_available' => true,
        ]);

        Sanctum::actingAs($customer);

        $this->postJson("/api/v1/customer/menu-items/{$greenBowlItem->id}/cart")
            ->assertOk();

        $this->postJson("/api/v1/customer/menu-items/{$dhakaGrillItem->id}/cart")
            ->assertStatus(409)
            ->assertJsonPath('message', 'Your cart already contains items from Green Bowl. Confirm replacing it before adding Smoky Beef Khichuri.')
            ->assertJsonPath('conflict.type', 'replace_cart')
            ->assertJsonPath('conflict.currentCart.restaurantName', 'Green Bowl')
            ->assertJsonPath('conflict.currentCart.itemsCount', 1)
            ->assertJsonPath('conflict.incoming.restaurantName', 'Dhaka Grill')
            ->assertJsonPath('conflict.incoming.menuItemId', $dhakaGrillItem->id);

        $cartOrder = $customer->fresh()->orders()->where('status', 'cart')->firstOrFail();

        $this->assertSame($greenBowl->id, $cartOrder->restaurant_id);
    }

    public function test_customer_mobile_cart_store_can_confirm_replacing_a_cart_from_another_restaurant(): void
    {
        $customer = User::factory()->customer()->create();
        $greenBowl = Restaurant::create([
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
        $greenBowlItem = MenuItem::create([
            'restaurant_id' => $greenBowl->id,
            'name' => 'Chicken Protein Bowl',
            'slug' => 'green-bowl-chicken-protein-bowl',
            'category' => 'Protein Plates',
            'description' => 'Grilled chicken, brown rice, greens, and yogurt sauce.',
            'price' => 420,
            'is_available' => true,
        ]);
        $dhakaGrill = Restaurant::create([
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
        $dhakaGrillItem = MenuItem::create([
            'restaurant_id' => $dhakaGrill->id,
            'name' => 'Smoky Beef Khichuri',
            'slug' => 'dhaka-grill-smoky-beef-khichuri',
            'category' => 'Rice Bowls',
            'description' => 'Slow-cooked beef with fragrant khichuri and achar.',
            'price' => 450,
            'is_available' => true,
        ]);

        Sanctum::actingAs($customer);

        $this->postJson("/api/v1/customer/menu-items/{$greenBowlItem->id}/cart")
            ->assertOk();

        $this->postJson("/api/v1/customer/menu-items/{$dhakaGrillItem->id}/cart", [
            'replace_cart' => true,
        ])
            ->assertOk()
            ->assertJsonPath('data.restaurant', 'Dhaka Grill')
            ->assertJsonPath('data.items.0.name', 'Smoky Beef Khichuri')
            ->assertJsonPath('data.total.value', 475);

        $cartOrder = $customer->fresh()->orders()->where('status', 'cart')->firstOrFail();

        $this->assertSame($dhakaGrill->id, $cartOrder->restaurant_id);
        $this->assertDatabaseMissing('order_items', [
            'order_id' => $cartOrder->id,
            'menu_item_id' => $greenBowlItem->id,
        ]);
    }

    public function test_guest_mobile_discovery_endpoints_return_public_browse_data_and_pagination(): void
    {
        $healthyRestaurant = Restaurant::create([
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

        $biryaniRestaurant = Restaurant::create([
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

        MenuItem::create([
            'restaurant_id' => $healthyRestaurant->id,
            'name' => 'Chicken Protein Bowl',
            'slug' => 'green-bowl-chicken-protein-bowl',
            'category' => 'Protein Plates',
            'description' => 'Grilled chicken, brown rice, greens, and yogurt sauce.',
            'image_path' => 'seed://demo-foods/chicken-protein-bowl.svg',
            'price' => 420,
            'is_available' => true,
        ]);

        MenuItem::create([
            'restaurant_id' => $biryaniRestaurant->id,
            'name' => 'Smoky Beef Khichuri',
            'slug' => 'dhaka-grill-smoky-beef-khichuri',
            'category' => 'Rice Bowls',
            'description' => 'Slow-cooked beef with fragrant khichuri and achar.',
            'price' => 450,
            'is_available' => true,
        ]);

        $this->getJson('/api/v1/discovery?search=bowl&cuisine=Healthy%20Bowls&category=Protein%20Plates')
            ->assertOk()
            ->assertJsonPath('data.foods.0.name', 'Chicken Protein Bowl')
            ->assertJsonPath('data.foods.0.imageUrl', '/images/demo-foods/chicken-protein-bowl.svg')
            ->assertJsonPath('data.restaurants.0.name', 'Green Bowl')
            ->assertJsonPath('meta.filters.search', 'bowl')
            ->assertJsonPath('meta.foodsPagination.total', 1)
            ->assertJsonPath('meta.foodCategories.0', 'Protein Plates');

        $this->getJson('/api/v1/discovery/restaurants?search=green&cuisine=Healthy%20Bowls')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Green Bowl')
            ->assertJsonPath('meta.pagination.total', 1)
            ->assertJsonPath('meta.filters.cuisine', 'Healthy Bowls');
    }

    public function test_guest_mobile_discovery_can_open_public_restaurant_and_food_detail_endpoints(): void
    {
        $restaurant = Restaurant::create([
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

        $menuItem = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Chicken Protein Bowl',
            'slug' => 'green-bowl-chicken-protein-bowl',
            'category' => 'Protein Plates',
            'description' => 'Grilled chicken, brown rice, greens, and yogurt sauce.',
            'price' => 420,
            'is_available' => true,
        ]);

        MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Yogurt Parfait',
            'slug' => 'green-bowl-yogurt-parfait',
            'category' => 'Desserts',
            'description' => 'Greek yogurt, fruit compote, and toasted granola.',
            'price' => 150,
            'is_available' => true,
        ]);

        $this->getJson("/api/v1/discovery/restaurants/{$restaurant->slug}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Green Bowl')
            ->assertJsonFragment(['slug' => 'green-bowl-chicken-protein-bowl']);

        $this->getJson("/api/v1/discovery/foods/{$menuItem->slug}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Chicken Protein Bowl')
            ->assertJsonPath('data.restaurant.name', 'Green Bowl')
            ->assertJsonPath('data.relatedItems.0.name', 'Yogurt Parfait');
    }

    public function test_hidden_restaurants_are_excluded_from_customer_and_guest_mobile_catalogs(): void
    {
        $customer = User::factory()->customer()->create();

        $visibleRestaurant = Restaurant::create([
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

        $hiddenRestaurant = Restaurant::create([
            'name' => 'Hidden Bowl',
            'slug' => 'hidden-bowl',
            'category' => 'Healthy Bowls',
            'cuisine' => 'Salads, Smoothies, Protein Plates',
            'min_delivery_time' => 25,
            'max_delivery_time' => 35,
            'rating' => 4.9,
            'delivery_fee' => 29,
            'featured_text' => 'Not currently discoverable.',
            'is_visible' => false,
        ]);

        MenuItem::create([
            'restaurant_id' => $visibleRestaurant->id,
            'name' => 'Chicken Protein Bowl',
            'slug' => 'green-bowl-chicken-protein-bowl',
            'category' => 'Protein Plates',
            'description' => 'Grilled chicken, brown rice, greens, and yogurt sauce.',
            'price' => 420,
            'is_available' => true,
        ]);

        $hiddenMenuItem = MenuItem::create([
            'restaurant_id' => $hiddenRestaurant->id,
            'name' => 'Hidden Chicken Bowl',
            'slug' => 'hidden-bowl-hidden-chicken-bowl',
            'category' => 'Protein Plates',
            'description' => 'A hidden dish.',
            'price' => 420,
            'is_available' => true,
        ]);

        Sanctum::actingAs($customer);

        $this->getJson('/api/v1/customer/restaurants')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'green-bowl');

        $this->getJson('/api/v1/customer/restaurants/hidden-bowl')->assertNotFound();

        $this->getJson('/api/v1/discovery')
            ->assertOk()
            ->assertJsonCount(1, 'data.restaurants')
            ->assertJsonPath('data.restaurants.0.slug', 'green-bowl');

        $this->getJson('/api/v1/discovery/restaurants/hidden-bowl')->assertNotFound();
        $this->getJson("/api/v1/discovery/foods/{$hiddenMenuItem->slug}")->assertNotFound();
    }

    public function test_customer_checkout_api_places_an_order_idempotently(): void
    {
        $customer = User::factory()->customer()->create();
        $restaurant = Restaurant::create([
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
        $menuItem = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Chicken Protein Bowl',
            'slug' => 'green-bowl-chicken-protein-bowl',
            'category' => 'Protein Plates',
            'description' => 'Grilled chicken, brown rice, greens, and yogurt sauce.',
            'price' => 420,
            'is_available' => true,
        ]);

        $this->actingAs($customer)->post(route('customer.cart.store', $menuItem));

        Sanctum::actingAs($customer);

        $checkout = $this->getJson('/api/v1/customer/checkout');

        $checkout
            ->assertOk()
            ->assertJsonPath('data.restaurant', 'Green Bowl')
            ->assertJsonPath('data.total.value', 474);

        $payload = [
            'delivery_address' => 'House 12, Road 7, Dhanmondi, Dhaka',
            'delivery_latitude' => 23.7808874,
            'delivery_longitude' => 90.4073486,
            'idempotency_key' => $checkout->json('data.idempotencyKey'),
            'payment_method' => 'bKash ending in 1024',
            'driver_notes' => 'Call on arrival.',
        ];

        $firstResponse = $this->postJson('/api/v1/customer/checkout/place', $payload);
        $secondResponse = $this->postJson('/api/v1/customer/checkout/place', $payload);

        $firstResponse
            ->assertOk()
            ->assertJsonPath('data.status.key', 'preparing');

        $secondResponse
            ->assertOk()
            ->assertJsonPath('data.id', $firstResponse->json('data.id'));

        $this->assertSame(1, $customer->fresh()->orders()->where('status', '!=', 'cart')->count());
    }

    public function test_merchant_and_courier_mobile_endpoints_share_the_same_delivery_contract(): void
    {
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
            'delivery_latitude' => 23.7808874,
            'delivery_longitude' => 90.4073486,
            'driver_notes' => 'Call on arrival.',
            'idempotency_key' => '11111111-1111-1111-1111-111111111111',
            'placed_at' => now()->subMinutes(10),
        ]);
        $order->orderItems()->create([
            'menu_item_id' => null,
            'name' => 'Smoky Beef Khichuri',
            'quantity' => 1,
            'unit_price' => 450,
            'line_total' => 450,
        ]);

        Sanctum::actingAs($merchant);

        $this->getJson('/api/v1/merchant/orders')
            ->assertOk()
            ->assertJsonPath('data.0.status.key', 'preparing');

        $this->postJson("/api/v1/merchant/orders/{$order->id}/dispatch")
            ->assertOk()
            ->assertJsonPath('data.status.key', 'on_the_way');

        Sanctum::actingAs($courier);

        $this->getJson('/api/v1/courier/deliveries')
            ->assertOk()
            ->assertJsonPath('data.0.assignment.canClaim', true)
            ->assertJsonPath('data.0.destination.hasCoordinates', true);

        $this->postJson("/api/v1/courier/deliveries/{$order->id}/claim")
            ->assertOk()
            ->assertJsonPath('data.assignment.courierId', $courier->id);

        $this->postJson("/api/v1/courier/deliveries/{$order->id}/complete")
            ->assertOk()
            ->assertJsonPath('data.status.key', 'delivered');
    }

    public function test_admin_mobile_dispatch_endpoint_returns_live_overview_metrics(): void
    {
        $admin = User::factory()->admin()->create();
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
            'idempotency_key' => '22222222-2222-2222-2222-222222222222',
            'placed_at' => now()->subMinutes(7),
        ]);
        $preparingOrder->orderItems()->create([
            'menu_item_id' => null,
            'name' => 'Smoky Beef Khichuri',
            'quantity' => 1,
            'unit_price' => 450,
            'line_total' => 450,
        ]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/admin/dispatch')
            ->assertOk()
            ->assertJsonPath('overview.activeOrders', 1)
            ->assertJsonPath('overview.preparingOrders', 1)
            ->assertJsonPath('overview.claimedDeliveries', 0)
            ->assertJsonPath('data.0.restaurant.name', 'Dhaka Grill');
    }
}