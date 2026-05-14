<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MerchantMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_merchant_dashboard_uses_live_workspace_data(): void
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

        $response = $this->actingAs($merchant)->get(route('merchant.dashboard'));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Merchant/Dashboard')
            ->where('overview.activeOrdersCount', 2)
            ->where('overview.preparingOrdersCount', 1)
            ->where('overview.ordersTodayCount', 2)
            ->where('overview.liveMenuItemsCount', 2)
            ->where('overview.pausedMenuItemsCount', 1)
            ->where('overview.restaurantsCount', 2)
            ->where('overview.visibleRestaurantsCount', 1)
            ->where('overview.hiddenRestaurantsCount', 1)
            ->where('overview.pinnedDestinationsCount', 1)
            ->has('recentOrders', 2)
            ->where('recentOrders.0.orderNumber', '#BL-'.str_pad((string) $onTheWayOrder->id, 4, '0', STR_PAD_LEFT))
            ->where('recentOrders.0.restaurantName', 'Green Bowl')
            ->where('recentOrders.0.destinationHasCoordinates', false)
            ->has('recentMenuItems', 3)
            ->where('recentMenuItems.0.name', 'Chicken Protein Bowl')
            ->has('restaurants', 2)
            ->where('restaurants.0.name', 'Dhaka Grill')
            ->where('restaurants.0.liveMenuItemsCount', 1)
            ->where('restaurants.1.name', 'Green Bowl')
            ->where('restaurants.1.visibilityLabel', 'Hidden from discovery'));
    }

    public function test_unapproved_merchant_lands_on_a_pending_approval_workspace(): void
    {
        $merchant = User::factory()->merchant()->create([
            'merchant_verified_at' => null,
        ]);

        $this->actingAs($merchant)
            ->get(route('merchant.dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Merchant/PendingApproval')
                ->where('merchant.email', $merchant->email)
                ->where('merchant.managedRestaurantsCount', 0)
                ->where('merchant.verificationLabel', 'Email verified'));
    }

    public function test_unapproved_merchant_is_redirected_away_from_menu_tools(): void
    {
        $merchant = User::factory()->merchant()->create([
            'merchant_verified_at' => null,
        ]);
        $restaurant = Restaurant::create([
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
        ]);

        $this->actingAs($merchant)
            ->from(route('merchant.dashboard'))
            ->get(route('merchant.menu.index'))
            ->assertRedirect(route('merchant.dashboard'))
            ->assertSessionHas('error');

        $this->actingAs($merchant)
            ->from(route('merchant.dashboard'))
            ->post(route('merchant.menu.store'), [
                'restaurant_id' => $restaurant->id,
                'name' => 'Yogurt Parfait',
                'category' => 'Desserts',
                'description' => 'Greek yogurt, fruit compote, and toasted granola.',
                'price' => 150,
                'is_available' => true,
            ])
            ->assertRedirect(route('merchant.dashboard'))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('menu_items', [
            'restaurant_id' => $restaurant->id,
            'name' => 'Yogurt Parfait',
        ]);
    }

    public function test_merchant_menu_page_uses_restaurant_menu_item_records(): void
    {
        $merchant = User::factory()->merchant()->create();
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

        MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Smoky Beef Khichuri',
            'slug' => 'dhaka-grill-smoky-beef-khichuri',
            'category' => 'Rice Bowls',
            'description' => 'Slow-cooked beef with fragrant khichuri and achar.',
            'price' => 450,
            'is_available' => true,
        ]);

        $response = $this->actingAs($merchant)->get(route('merchant.menu.index'));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Merchant/Menu/Index')
            ->has('restaurants', 1)
            ->where('restaurants.0.name', 'Dhaka Grill')
            ->where('restaurants.0.totalItems', 1)
            ->where('restaurants.0.menuItems.0.name', 'Smoky Beef Khichuri'));
    }

    public function test_merchant_can_create_a_menu_item_for_their_restaurant(): void
    {
        $merchant = User::factory()->merchant()->create();
        $restaurant = Restaurant::create([
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
        ]);

        $response = $this->actingAs($merchant)->post(route('merchant.menu.store'), [
            'restaurant_id' => $restaurant->id,
            'name' => 'Yogurt Parfait',
            'category' => 'Desserts',
            'description' => 'Greek yogurt, fruit compote, and toasted granola.',
            'price' => 150,
            'is_available' => true,
        ]);

        $response->assertRedirect(route('merchant.menu.index'));

        $this->assertDatabaseHas('menu_items', [
            'restaurant_id' => $restaurant->id,
            'name' => 'Yogurt Parfait',
            'category' => 'Desserts',
            'price' => 150,
            'is_available' => true,
        ]);
    }

    public function test_merchant_can_update_their_menu_item(): void
    {
        $merchant = User::factory()->merchant()->create();
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
        $menuItem = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Street Wrap Combo',
            'slug' => 'spice-lane-street-wrap-combo',
            'category' => 'Wraps',
            'description' => 'Double chicken wrap with masala fries and dip.',
            'price' => 320,
            'is_available' => true,
        ]);

        $response = $this->actingAs($merchant)->patch(route('merchant.menu.update', $menuItem), [
            'restaurant_id' => $restaurant->id,
            'name' => 'Street Wrap Combo XL',
            'category' => 'Wraps',
            'description' => 'Double chicken wrap with extra fries and dip.',
            'price' => 360,
            'is_available' => false,
        ]);

        $response->assertRedirect(route('merchant.menu.index'));

        $this->assertDatabaseHas('menu_items', [
            'id' => $menuItem->id,
            'name' => 'Street Wrap Combo XL',
            'description' => 'Double chicken wrap with extra fries and dip.',
            'price' => 360,
            'is_available' => false,
        ]);
    }

    public function test_merchant_cannot_create_menu_items_for_another_merchants_restaurant(): void
    {
        $merchant = User::factory()->merchant()->create();
        $otherMerchant = User::factory()->merchant()->create();
        $restaurant = Restaurant::create([
            'user_id' => $otherMerchant->id,
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

        $this->actingAs($merchant)
            ->post(route('merchant.menu.store'), [
                'restaurant_id' => $restaurant->id,
                'name' => 'Smoky Beef Khichuri',
                'category' => 'Rice Bowls',
                'description' => 'Slow-cooked beef with fragrant khichuri and achar.',
                'price' => 450,
                'is_available' => true,
            ])
            ->assertNotFound();
    }
}