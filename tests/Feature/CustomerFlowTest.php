<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CustomerFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_dashboard_uses_live_workspace_data(): void
    {
        $user = User::factory()->customer()->create();

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

        $dhakaGrillMenuItem = MenuItem::create([
            'restaurant_id' => $dhakaGrill->id,
            'name' => 'Smoky Beef Khichuri',
            'slug' => 'dhaka-grill-smoky-beef-khichuri',
            'category' => 'Rice Bowls',
            'description' => 'Slow-cooked beef with fragrant khichuri and achar.',
            'price' => 450,
            'is_available' => true,
        ]);

        $cartOrder = Order::create([
            'user_id' => $user->id,
            'restaurant_id' => $greenBowl->id,
            'status' => 'cart',
            'subtotal' => 420,
            'delivery_fee' => 29,
            'service_fee' => 25,
            'total' => 474,
            'payment_method' => 'Cash on delivery',
            'idempotency_key' => 'customer-dashboard-cart-key',
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

        $activeOrder = Order::create([
            'user_id' => $user->id,
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
            'placed_at' => now()->subMinutes(15),
        ]);
        $activeOrder->orderItems()->create([
            'menu_item_id' => $cartMenuItem->id,
            'name' => 'Chicken Protein Bowl',
            'quantity' => 1,
            'unit_price' => 420,
            'line_total' => 420,
        ]);

        $reorderableOrder = Order::create([
            'user_id' => $user->id,
            'restaurant_id' => $dhakaGrill->id,
            'status' => 'delivered',
            'subtotal' => 450,
            'delivery_fee' => 0,
            'service_fee' => 25,
            'total' => 475,
            'payment_method' => 'bKash',
            'delivery_address' => 'Banani 11, Dhaka',
            'delivery_latitude' => null,
            'delivery_longitude' => null,
            'driver_notes' => null,
            'placed_at' => now()->subHours(2),
        ]);
        $reorderableOrder->orderItems()->create([
            'menu_item_id' => $dhakaGrillMenuItem->id,
            'name' => 'Smoky Beef Khichuri',
            'quantity' => 1,
            'unit_price' => 450,
            'line_total' => 450,
        ]);

        $changedOrder = Order::create([
            'user_id' => $user->id,
            'restaurant_id' => $dhakaGrill->id,
            'status' => 'delivered',
            'subtotal' => 190,
            'delivery_fee' => 0,
            'service_fee' => 25,
            'total' => 215,
            'payment_method' => 'bKash',
            'delivery_address' => 'Gulshan 2, Dhaka',
            'delivery_latitude' => null,
            'delivery_longitude' => null,
            'driver_notes' => null,
            'placed_at' => now()->subHours(4),
        ]);
        $changedOrder->orderItems()->create([
            'menu_item_id' => null,
            'name' => 'Greek Yogurt Parfait',
            'quantity' => 1,
            'unit_price' => 190,
            'line_total' => 190,
        ]);

        $response = $this->actingAs($user)->get(route('customer.dashboard'));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Customer/Dashboard')
            ->where('overview.restaurantsCount', 2)
            ->where('overview.foodsCount', 2)
            ->where('overview.activeOrdersCount', 1)
            ->where('overview.cartItemsCount', 1)
            ->where('overview.cartTotalValue', 474)
            ->where('cart.restaurant', 'Green Bowl')
            ->where('cart.totalFormatted', '₱474')
            ->has('spotlightRestaurants', 2)
            ->where('spotlightRestaurants.0.name', 'Green Bowl')
            ->has('spotlightFoods', 2)
            ->where('spotlightFoods.0.name', 'Chicken Protein Bowl')
                ->has('recentOrders', 3)
            ->where('recentOrders.0.orderNumber', '#BL-'.str_pad((string) $activeOrder->id, 4, '0', STR_PAD_LEFT))
                ->where('recentOrders.0.canTrack', true)
                ->where('recentOrders.1.orderNumber', '#BL-'.str_pad((string) $reorderableOrder->id, 4, '0', STR_PAD_LEFT))
                ->where('recentOrders.1.canReorder', true)
                ->where('recentOrders.1.reorder.label', 'Ready to reorder')
                ->where('recentOrders.2.orderNumber', '#BL-'.str_pad((string) $changedOrder->id, 4, '0', STR_PAD_LEFT))
                ->where('recentOrders.2.canReorder', false)
                ->where('recentOrders.2.reorder.label', 'Changed since last order')
                ->where('recentOrders.2.reorder.items.0.name', 'Greek Yogurt Parfait')
                ->where('recentOrders.2.reorder.items.0.reason', 'This dish is no longer listed on the kitchen menu.'));
    }

    public function test_customer_restaurants_page_uses_restaurant_records(): void
    {
        $user = User::factory()->customer()->create();

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

        MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Smoky Beef Khichuri',
            'slug' => 'dhaka-grill-smoky-beef-khichuri',
            'category' => 'Rice Bowls',
            'description' => 'Slow-cooked beef with fragrant khichuri and achar.',
            'price' => 450,
            'is_available' => true,
        ]);

        $response = $this->actingAs($user)->get(route('customer.restaurants.index'));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Customer/Restaurants/Index')
            ->has('restaurants', 1)
            ->where('restaurants.0.name', 'Dhaka Grill')
            ->has('foods', 1)
            ->where('foods.0.name', 'Smoky Beef Khichuri')
            ->has('cuisines', 1)
            ->where('cuisines.0', 'Bengali'));
    }

    public function test_customer_can_open_a_restaurant_menu_page_with_available_menu_items(): void
    {
        $user = User::factory()->customer()->create();
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

        MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Smoky Beef Khichuri',
            'slug' => 'dhaka-grill-smoky-beef-khichuri',
            'category' => 'Rice Bowls',
            'description' => 'Slow-cooked beef with fragrant khichuri and achar.',
            'price' => 450,
            'is_available' => true,
        ]);

        MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Private Chef Special',
            'slug' => 'dhaka-grill-private-chef-special',
            'category' => 'Rice Bowls',
            'description' => 'Not currently available for ordering.',
            'price' => 650,
            'is_available' => false,
        ]);

        $response = $this->actingAs($user)->get(route('customer.restaurants.show', $restaurant));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Customer/Restaurants/Show')
            ->where('restaurant.name', 'Dhaka Grill')
            ->has('restaurant.categories', 1)
            ->where('restaurant.categories.0', 'Rice Bowls')
            ->has('restaurant.menuItems', 1)
            ->where('restaurant.menuItems.0.name', 'Smoky Beef Khichuri'));
    }

    public function test_hidden_restaurants_are_not_available_to_customers(): void
    {
        $user = User::factory()->customer()->create();
        $restaurant = Restaurant::create([
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

        $menuItem = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Hidden Chicken Bowl',
            'slug' => 'hidden-bowl-hidden-chicken-bowl',
            'category' => 'Protein Plates',
            'description' => 'A hidden dish.',
            'price' => 420,
            'is_available' => true,
        ]);

        $this->actingAs($user)
            ->get(route('customer.restaurants.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Customer/Restaurants/Index')
                ->has('restaurants', 0)
                ->has('foods', 0));

        $this->actingAs($user)
            ->get(route('customer.restaurants.show', $restaurant))
            ->assertNotFound();

        $this->actingAs($user)
            ->post(route('customer.cart.store', $menuItem))
            ->assertNotFound();
    }

    public function test_customer_cart_and_orders_pages_use_persisted_order_data(): void
    {
        $user = User::factory()->customer()->create();
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

        Order::create([
            'user_id' => $user->id,
            'restaurant_id' => $restaurant->id,
            'status' => 'cart',
            'subtotal' => 420,
            'delivery_fee' => 29,
            'service_fee' => 20,
            'total' => 469,
            'payment_method' => 'bKash ending in 1024',
            'delivery_address' => 'Home, Banani DOHS, Dhaka',
            'driver_notes' => 'Call on arrival.',
        ])->orderItems()->create([
            'menu_item_id' => null,
            'name' => 'Chicken Protein Bowl',
            'quantity' => 1,
            'unit_price' => 420,
            'line_total' => 420,
        ]);

        Order::create([
            'user_id' => $user->id,
            'restaurant_id' => $restaurant->id,
            'status' => 'preparing',
            'subtotal' => 420,
            'delivery_fee' => 29,
            'service_fee' => 20,
            'total' => 469,
            'payment_method' => 'bKash ending in 1024',
            'delivery_address' => 'Home, Banani DOHS, Dhaka',
            'driver_notes' => 'Call on arrival.',
            'placed_at' => now()->subMinutes(12),
        ])->orderItems()->create([
            'menu_item_id' => null,
            'name' => 'Chicken Protein Bowl',
            'quantity' => 1,
            'unit_price' => 420,
            'line_total' => 420,
        ]);

        Order::create([
            'user_id' => $user->id,
            'restaurant_id' => $restaurant->id,
            'status' => 'delivered',
            'subtotal' => 190,
            'delivery_fee' => 29,
            'service_fee' => 20,
            'total' => 239,
            'payment_method' => 'Cash on delivery',
            'delivery_address' => 'Office, Gulshan 1, Dhaka',
            'driver_notes' => 'Reception drop-off.',
            'placed_at' => now()->subHour(),
        ])->orderItems()->create([
            'menu_item_id' => null,
            'name' => 'Greek Yogurt Parfait',
            'quantity' => 1,
            'unit_price' => 190,
            'line_total' => 190,
        ]);

        $this->actingAs($user)
            ->get(route('customer.cart.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Customer/Cart/Index')
                ->where('cart.restaurant', 'Green Bowl')
                ->has('cart.items', 1));

        $this->actingAs($user)
            ->get(route('customer.orders.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Customer/Orders/Index')
                ->has('orders', 2)
                ->where('orders.0.restaurant', 'Green Bowl')
                ->where('orders.1.canReorder', false)
                ->where('filters.status', 'all')
                ->where('overview.activeOrdersCount', 1)
                ->where('overview.completedOrdersCount', 1)
                ->where('overview.totalOrdersCount', 2));

        $this->actingAs($user)
            ->get(route('customer.orders.index', ['status' => 'active']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Customer/Orders/Index')
                ->has('orders', 1)
                ->where('orders.0.statusKey', 'preparing')
                ->where('filters.status', 'active')
                ->where('overview.activeOrdersCount', 1)
                ->where('overview.completedOrdersCount', 1));

        $this->actingAs($user)
            ->get(route('customer.orders.index', ['status' => 'history']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Customer/Orders/Index')
                ->has('orders', 1)
                ->where('orders.0.statusKey', 'delivered')
                ->where('filters.status', 'history'));
    }

    public function test_customer_can_reorder_a_delivered_order_into_their_cart(): void
    {
        $user = User::factory()->customer()->create();
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
            'user_id' => $user->id,
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

        $this->actingAs($user)
            ->get(route('customer.orders.show', $order))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Customer/Orders/Show')
                ->where('order.canReorder', true));

        $response = $this->actingAs($user)
            ->post(route('customer.orders.reorder', $order));

        $response->assertRedirect(route('customer.cart.index'));

        $cartOrder = $user->fresh()->orders()->where('status', 'cart')->first();

        $this->assertNotNull($cartOrder);
        $this->assertSame($restaurant->id, $cartOrder->restaurant_id);
        $this->assertSame(1090, $cartOrder->subtotal);
        $this->assertSame(1115, $cartOrder->total);
        $this->assertCount(2, $cartOrder->orderItems);
        $this->assertSame(2, $cartOrder->orderItems()->where('menu_item_id', $khichuri->id)->firstOrFail()->quantity);
        $this->assertSame(1, $cartOrder->orderItems()->where('menu_item_id', $parfait->id)->firstOrFail()->quantity);
        $this->assertNotNull($cartOrder->idempotency_key);
    }

    public function test_customer_must_confirm_replacing_a_cart_from_another_restaurant_before_reordering(): void
    {
        $user = User::factory()->customer()->create();
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
            'user_id' => $user->id,
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

        $this->actingAs($user)->post(route('customer.cart.store', $greenBowlItem));

        $response = $this->actingAs($user)
            ->post(route('customer.orders.reorder', $order), [
                'redirect_to' => route('customer.orders.show', $order, absolute: false),
            ]);

        $response
            ->assertRedirect(route('customer.orders.show', $order))
            ->assertSessionHas('error', 'Your cart already contains items from Green Bowl. Confirm replacing it before reordering #BL-'.str_pad((string) $order->id, 4, '0', STR_PAD_LEFT).'.');

        $cartOrder = $user->fresh()->orders()->where('status', 'cart')->firstOrFail();

        $this->assertSame($greenBowl->id, $cartOrder->restaurant_id);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $cartOrder->id,
            'menu_item_id' => $greenBowlItem->id,
            'name' => 'Chicken Protein Bowl',
        ]);
        $this->assertDatabaseMissing('order_items', [
            'order_id' => $cartOrder->id,
            'menu_item_id' => $khichuri->id,
        ]);
    }

    public function test_customer_can_confirm_replacing_a_cart_from_another_restaurant_when_reordering(): void
    {
        $user = User::factory()->customer()->create();
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
            'user_id' => $user->id,
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

        $this->actingAs($user)->post(route('customer.cart.store', $greenBowlItem));

        $response = $this->actingAs($user)
            ->post(route('customer.orders.reorder', $order), [
                'replace_cart' => true,
                'redirect_to' => route('customer.orders.show', $order, absolute: false),
            ]);

        $response
            ->assertRedirect(route('customer.orders.show', $order))
            ->assertSessionHas('success', 'Order #BL-'.str_pad((string) $order->id, 4, '0', STR_PAD_LEFT).' was rebuilt in cart. Your previous cart from Green Bowl was replaced.');

        $cartOrder = $user->fresh()->orders()->where('status', 'cart')->firstOrFail();

        $this->assertSame($dhakaGrill->id, $cartOrder->restaurant_id);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $cartOrder->id,
            'menu_item_id' => $khichuri->id,
            'name' => 'Smoky Beef Khichuri',
        ]);
        $this->assertDatabaseMissing('order_items', [
            'order_id' => $cartOrder->id,
            'menu_item_id' => $greenBowlItem->id,
        ]);
    }

    public function test_customer_can_open_an_order_detail_page_with_delivery_context(): void
    {
        $user = User::factory()->customer()->create();
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
            'user_id' => $user->id,
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

        $this->actingAs($user)
            ->get(route('customer.orders.show', $order))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Customer/Orders/Show')
                ->where('order.orderNumber', '#BL-'.str_pad((string) $order->id, 4, '0', STR_PAD_LEFT))
                ->where('order.restaurant.name', 'Green Bowl')
                ->where('order.status.key', 'on_the_way')
                ->where('order.canReorder', false)
                ->where('order.guidance.title', 'Your rider is currently on the way.')
                ->where('order.timeline.0.state', 'complete')
                ->where('order.timeline.1.state', 'complete')
                ->where('order.timeline.2.state', 'current')
                ->where('order.timeline.3.state', 'upcoming')
                ->where('order.destination.address', 'House 12, Road 7, Dhanmondi, Dhaka')
                ->where('order.destination.hasCoordinates', true)
                ->where('order.reorder.visible', false)
                ->where('order.items.0.name', 'Chicken Protein Bowl')
                ->where('order.totals.total.formatted', '₱474'));
    }

    public function test_customer_order_detail_shows_changed_reorder_items_for_history_orders(): void
    {
        $user = User::factory()->customer()->create();
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
            'user_id' => $user->id,
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

        $this->actingAs($user)
            ->get(route('customer.orders.show', $order))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Customer/Orders/Show')
                ->where('order.canReorder', false)
                ->where('order.reorder.visible', true)
                ->where('order.reorder.label', 'Changed since last order')
                ->where('order.reorder.items.0.name', 'Greek Yogurt Parfait')
                ->where('order.reorder.items.0.reason', 'This dish is no longer listed on the kitchen menu.')
                ->where('order.reorder.items.0.suggestions.0.name', 'Berry Yogurt Bowl')
                ->where('order.reorder.items.0.suggestions.0.price.formatted', '₱210'));
    }

    public function test_customer_cannot_open_another_customers_order_detail_page(): void
    {
        $user = User::factory()->customer()->create();
        $otherUser = User::factory()->customer()->create();
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
            'user_id' => $otherUser->id,
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

        $this->actingAs($user)
            ->get(route('customer.orders.show', $order))
            ->assertNotFound();
    }

    public function test_customer_can_add_a_menu_item_to_their_cart(): void
    {
        $user = User::factory()->customer()->create();
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
        $menuItem = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Smoky Beef Khichuri',
            'slug' => 'dhaka-grill-smoky-beef-khichuri',
            'category' => 'Rice Bowls',
            'description' => 'Slow-cooked beef with fragrant khichuri and achar.',
            'price' => 450,
            'is_available' => true,
        ]);

        $response = $this->actingAs($user)
            ->post(route('customer.cart.store', $menuItem));

        $response->assertRedirect(route('customer.cart.index'));

        $cartOrder = $user->orders()->where('status', 'cart')->first();

        $this->assertNotNull($cartOrder);
        $this->assertSame($restaurant->id, $cartOrder->restaurant_id);
        $cartItem = $cartOrder->orderItems()->first();

        $this->assertNotNull($cartItem);
        $this->assertSame($menuItem->id, $cartItem->menu_item_id);
        $this->assertSame('Smoky Beef Khichuri', $cartItem->name);
        $this->assertSame(1, $cartItem->quantity);
        $this->assertSame(450, $cartOrder->subtotal);
        $this->assertSame(475, $cartOrder->total);
    }

    public function test_customer_must_confirm_replacing_a_cart_from_another_restaurant_before_adding_a_new_item(): void
    {
        $user = User::factory()->customer()->create();
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

        $this->actingAs($user)->post(route('customer.cart.store', $greenBowlItem));

        $response = $this->actingAs($user)
            ->post(route('customer.cart.store', $dhakaGrillItem), [
                'redirect_to' => route('customer.restaurants.show', $dhakaGrill, absolute: false),
            ]);

        $response
            ->assertRedirect(route('customer.restaurants.show', $dhakaGrill))
            ->assertSessionHas('error', 'Your cart already contains items from Green Bowl. Confirm replacing it before adding Smoky Beef Khichuri.');

        $cartOrder = $user->fresh()->orders()->where('status', 'cart')->firstOrFail();

        $this->assertSame($greenBowl->id, $cartOrder->restaurant_id);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $cartOrder->id,
            'menu_item_id' => $greenBowlItem->id,
            'name' => 'Chicken Protein Bowl',
        ]);
        $this->assertDatabaseMissing('order_items', [
            'order_id' => $cartOrder->id,
            'menu_item_id' => $dhakaGrillItem->id,
        ]);
    }

    public function test_customer_can_confirm_replacing_a_cart_from_another_restaurant_when_adding_a_new_item(): void
    {
        $user = User::factory()->customer()->create();
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

        $this->actingAs($user)->post(route('customer.cart.store', $greenBowlItem));

        $response = $this->actingAs($user)
            ->post(route('customer.cart.store', $dhakaGrillItem), [
                'replace_cart' => true,
                'redirect_to' => route('customer.restaurants.show', $dhakaGrill, absolute: false),
            ]);

        $response
            ->assertRedirect(route('customer.restaurants.show', $dhakaGrill))
            ->assertSessionHas('success', 'Smoky Beef Khichuri added to cart. Your previous cart from Green Bowl was replaced.');

        $cartOrder = $user->fresh()->orders()->where('status', 'cart')->firstOrFail();

        $this->assertSame($dhakaGrill->id, $cartOrder->restaurant_id);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $cartOrder->id,
            'menu_item_id' => $dhakaGrillItem->id,
            'name' => 'Smoky Beef Khichuri',
        ]);
        $this->assertDatabaseMissing('order_items', [
            'order_id' => $cartOrder->id,
            'menu_item_id' => $greenBowlItem->id,
        ]);
    }

    public function test_customer_can_increase_and_decrease_cart_item_quantities(): void
    {
        $user = User::factory()->customer()->create();
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
        $menuItem = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Smoky Beef Khichuri',
            'slug' => 'dhaka-grill-smoky-beef-khichuri',
            'category' => 'Rice Bowls',
            'description' => 'Slow-cooked beef with fragrant khichuri and achar.',
            'price' => 450,
            'is_available' => true,
        ]);

        $this->actingAs($user)->post(route('customer.cart.store', $menuItem));

        $this->actingAs($user)
            ->post(route('customer.cart.items.increment', $menuItem))
            ->assertRedirect(route('customer.cart.index'));

        $cartOrder = $user->fresh()->orders()->where('status', 'cart')->firstOrFail();
        $cartItem = $cartOrder->orderItems()->first();

        $this->assertNotNull($cartItem);
        $this->assertSame(2, $cartItem->quantity);
        $this->assertSame(900, $cartOrder->subtotal);
        $this->assertSame(925, $cartOrder->total);

        $this->actingAs($user)
            ->post(route('customer.cart.items.decrement', $menuItem))
            ->assertRedirect(route('customer.cart.index'));

        $cartOrder = $user->fresh()->orders()->where('status', 'cart')->firstOrFail();
        $cartItem = $cartOrder->orderItems()->first();

        $this->assertNotNull($cartItem);
        $this->assertSame(1, $cartItem->quantity);
        $this->assertSame(450, $cartOrder->subtotal);
        $this->assertSame(475, $cartOrder->total);
    }

    public function test_customer_can_remove_the_last_cart_item_by_decrementing_quantity(): void
    {
        $user = User::factory()->customer()->create();
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

        $this->actingAs($user)->post(route('customer.cart.store', $menuItem));

        $this->actingAs($user)
            ->post(route('customer.cart.items.decrement', $menuItem))
            ->assertRedirect(route('customer.cart.index'));

        $this->assertNull($user->fresh()->orders()->where('status', 'cart')->first());
    }

    public function test_customer_can_place_their_cart_order_from_checkout(): void
    {
        $user = User::factory()->customer()->create();
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

        $this->actingAs($user)
            ->post(route('customer.cart.store', $menuItem));

        $cartOrder = $user->fresh()->orders()->where('status', 'cart')->firstOrFail();
        $this->assertNotNull($cartOrder->idempotency_key);

        $response = $this->actingAs($user)->post(route('customer.checkout.place'), [
            'delivery_address' => 'House 12, Road 7, Dhanmondi, Dhaka',
            'delivery_latitude' => 23.7808874,
            'delivery_longitude' => 90.4073486,
            'idempotency_key' => $cartOrder->idempotency_key,
            'payment_method' => 'bKash ending in 1024',
            'driver_notes' => 'Call on arrival.',
        ]);

        $order = $user->orders()->firstOrFail();

        $response->assertRedirect(route('customer.orders.show', $order));

        $this->assertSame('pending', $order->status);
        $this->assertSame('House 12, Road 7, Dhanmondi, Dhaka', $order->delivery_address);
        $this->assertSame(23.7808874, (float) $order->getRawOriginal('delivery_latitude'));
        $this->assertSame(90.4073486, (float) $order->getRawOriginal('delivery_longitude'));
        $this->assertSame('bKash ending in 1024', $order->payment_method);
        $this->assertSame('Call on arrival.', $order->driver_notes);
        $this->assertNotNull($order->placed_at);
    }

    public function test_customer_checkout_ignores_a_stale_retry_with_the_same_idempotency_key(): void
    {
        $user = User::factory()->customer()->create();
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

        $this->actingAs($user)->post(route('customer.cart.store', $menuItem));

        $firstCartOrder = $user->fresh()->orders()->where('status', 'cart')->firstOrFail();
        $firstIdempotencyKey = $firstCartOrder->idempotency_key;

        $payload = [
            'delivery_address' => 'House 12, Road 7, Dhanmondi, Dhaka',
            'delivery_latitude' => 23.7808874,
            'delivery_longitude' => 90.4073486,
            'idempotency_key' => $firstIdempotencyKey,
            'payment_method' => 'bKash ending in 1024',
            'driver_notes' => 'Call on arrival.',
        ];

        $firstResponse = $this->actingAs($user)
            ->post(route('customer.checkout.place'), $payload);

        $firstPlacedOrder = $user->fresh()
            ->orders()
            ->where('status', '!=', 'cart')
            ->firstOrFail();

        $firstResponse->assertRedirect(route('customer.orders.show', $firstPlacedOrder));

        $this->actingAs($user)->post(route('customer.cart.store', $menuItem));

        $newCartOrder = $user->fresh()->orders()->where('status', 'cart')->firstOrFail();

        $this->assertNotSame($firstIdempotencyKey, $newCartOrder->idempotency_key);

        $this->actingAs($user)
            ->post(route('customer.checkout.place'), $payload)
            ->assertRedirect(route('customer.orders.show', $firstPlacedOrder));

        $user = $user->fresh();

        $this->assertSame(1, $user->orders()->where('status', '!=', 'cart')->count());
        $this->assertSame($firstPlacedOrder->id, $user->orders()->where('status', '!=', 'cart')->firstOrFail()->id);
        $this->assertSame($newCartOrder->id, $user->orders()->where('status', 'cart')->firstOrFail()->id);
    }

    public function test_role_routes_are_forbidden_outside_the_matching_role(): void
    {
        $customer = User::factory()->customer()->create();
        $merchant = User::factory()->merchant()->create();

        $this->actingAs($customer)
            ->get(route('merchant.dashboard'))
            ->assertForbidden();

        $this->actingAs($merchant)
            ->get(route('customer.dashboard'))
            ->assertForbidden();

        $this->actingAs($merchant)
            ->get(route('merchant.dashboard'))
            ->assertOk();
    }
}