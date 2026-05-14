<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_users_page_uses_live_user_records_and_filters(): void
    {
        $admin = User::factory()->admin()->create();
        $merchant = User::factory()->merchant()->create([
            'name' => 'Merchant Mona',
            'email' => 'merchant@example.com',
        ]);
        $customer = User::factory()->customer()->create();
        $courier = User::factory()->courier()->create();

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

        Order::create([
            'user_id' => $customer->id,
            'courier_id' => $courier->id,
            'restaurant_id' => $restaurant->id,
            'status' => 'on_the_way',
            'subtotal' => 420,
            'delivery_fee' => 29,
            'service_fee' => 25,
            'total' => 474,
            'payment_method' => 'Cash on delivery',
            'delivery_address' => 'Banani 11, Dhaka',
            'placed_at' => now()->subMinutes(10),
        ]);

        $this->actingAs($admin)
            ->get('/admin/users?search=merchant&role=merchant')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Users/Index')
                ->where('overview.totalUsers', 4)
                ->where('overview.merchantUsers', 1)
                ->where('overview.customerUsers', 1)
                ->where('overview.courierUsers', 1)
                ->where('filters.search', 'merchant')
                ->where('filters.role', 'merchant')
                ->has('users', 1)
                ->where('users.0.name', 'Merchant Mona')
                ->where('users.0.email', 'merchant@example.com')
                ->where('users.0.accessLabel', 'Active access')
                ->where('users.0.merchantApprovalLabel', 'Merchant approved')
                ->where('users.0.managedRestaurantsCount', 1)
                ->where('users.0.restaurantsIndexUrl', route('admin.restaurants.index', ['merchant' => 'merchant@example.com']))
                ->where('usersPagination.total', 1));
    }

    public function test_admin_restaurants_page_uses_live_restaurant_records_and_filters(): void
    {
        $admin = User::factory()->admin()->create();
        $merchant = User::factory()->merchant()->create([
            'name' => 'Merchant Mona',
            'email' => 'merchant@example.com',
        ]);
        $otherMerchant = User::factory()->merchant()->create();
        $customer = User::factory()->customer()->create();

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

        Restaurant::create([
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

        MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Chicken Protein Bowl',
            'slug' => 'green-bowl-chicken-protein-bowl',
            'category' => 'Protein Plates',
            'description' => 'Grilled chicken, brown rice, greens, and yogurt sauce.',
            'price' => 420,
            'is_available' => true,
        ]);

        Order::create([
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

        $this->actingAs($admin)
            ->get('/admin/restaurants?search=green&category=Healthy%20Bowls')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Restaurants/Index')
                ->where('overview.totalRestaurants', 2)
                ->where('overview.activeMerchants', 2)
                ->where('overview.liveMenus', 1)
                ->where('overview.activeOrders', 1)
                ->where('filters.search', 'green')
                ->where('filters.category', 'Healthy Bowls')
                ->has('restaurants', 1)
                ->where('restaurants.0.name', 'Green Bowl')
                ->where('restaurants.0.merchantEmail', 'merchant@example.com')
                ->where('restaurants.0.visibilityLabel', 'Discovery visible')
                ->where('restaurants.0.availableMenuItemsCount', 1)
                ->where('restaurants.0.activeOrdersCount', 1)
                ->where('restaurants.0.publicMenuUrl', route('restaurants.show', $restaurant))
                ->where('restaurantsPagination.total', 1));
    }

    public function test_admin_can_open_a_user_detail_page_with_role_context(): void
    {
        $admin = User::factory()->admin()->create();
        $merchant = User::factory()->merchant()->create([
            'name' => 'Merchant Mona',
            'email' => 'merchant@example.com',
        ]);
        $customer = User::factory()->customer()->create();

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
        $order->orderItems()->create([
            'menu_item_id' => null,
            'name' => 'Chicken Protein Bowl',
            'quantity' => 1,
            'unit_price' => 420,
            'line_total' => 420,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.show', $merchant))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Users/Show')
                ->where('user.name', 'Merchant Mona')
                ->where('user.roleLabel', 'Merchant')
                ->where('user.accessLabel', 'Active access')
                ->where('user.merchantApprovalLabel', 'Merchant approved')
                ->where('user.managedRestaurantsCount', 1)
                ->has('managedRestaurants', 1)
                ->where('managedRestaurants.0.name', 'Green Bowl')
                ->where('managedRestaurants.0.detailUrl', route('admin.restaurants.show', $restaurant))
                ->has('recentOrders', 0)
                ->has('assignedDeliveries', 0));
    }

    public function test_admin_can_open_a_restaurant_detail_page_with_menu_and_order_context(): void
    {
        $admin = User::factory()->admin()->create();
        $merchant = User::factory()->merchant()->create([
            'name' => 'Merchant Mona',
            'email' => 'merchant@example.com',
        ]);
        $customer = User::factory()->customer()->create();
        $courier = User::factory()->courier()->create();

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

        $menuItem = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Chicken Protein Bowl',
            'slug' => 'green-bowl-chicken-protein-bowl',
            'category' => 'Protein Plates',
            'description' => 'Grilled chicken, brown rice, greens, and yogurt sauce.',
            'price' => 420,
            'is_available' => true,
        ]);

        $order = Order::create([
            'user_id' => $customer->id,
            'courier_id' => $courier->id,
            'restaurant_id' => $restaurant->id,
            'status' => 'on_the_way',
            'subtotal' => 420,
            'delivery_fee' => 29,
            'service_fee' => 25,
            'total' => 474,
            'payment_method' => 'Cash on delivery',
            'delivery_address' => 'Banani 11, Dhaka',
            'placed_at' => now()->subMinutes(8),
        ]);
        $order->orderItems()->create([
            'menu_item_id' => $menuItem->id,
            'name' => 'Chicken Protein Bowl',
            'quantity' => 1,
            'unit_price' => 420,
            'line_total' => 420,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.restaurants.show', $restaurant))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Restaurants/Show')
                ->where('restaurant.name', 'Green Bowl')
                ->where('restaurant.visibilityLabel', 'Discovery visible')
                ->where('restaurant.detailUrl', route('admin.restaurants.show', $restaurant))
                ->where('merchant.name', 'Merchant Mona')
                ->where('merchant.approvalLabel', 'Merchant approved')
                ->where('merchant.usersDetailUrl', route('admin.users.show', $merchant))
                ->has('menuItems', 1)
                ->where('menuItems.0.name', 'Chicken Protein Bowl')
                ->where('menuItems.0.publicDetailUrl', route('foods.show', $menuItem->slug))
                ->has('activeOrders', 1)
                ->where('activeOrders.0.orderNumber', '#BL-'.str_pad((string) $order->id, 4, '0', STR_PAD_LEFT))
                ->where('activeOrders.0.courierName', $courier->name));
    }

    public function test_admin_can_suspend_and_restore_a_user_account(): void
    {
        $admin = User::factory()->admin()->create();
        $merchant = User::factory()->merchant()->create([
            'name' => 'Merchant Mona',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.suspend', $merchant))
            ->assertRedirect();

        $this->assertTrue($merchant->fresh()->isSuspended());

        $this->actingAs($admin)
            ->post(route('admin.users.restore', $merchant))
            ->assertRedirect();

        $this->assertFalse($merchant->fresh()->isSuspended());
    }

    public function test_admin_can_approve_and_revoke_merchant_status(): void
    {
        $admin = User::factory()->admin()->create();
        $merchant = User::factory()->merchant()->create([
            'merchant_verified_at' => null,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.users.approve-merchant', $merchant))
            ->assertRedirect();

        $this->assertNotNull($merchant->fresh()->merchant_verified_at);

        $this->actingAs($admin)
            ->post(route('admin.users.revoke-merchant-approval', $merchant))
            ->assertRedirect();

        $this->assertNull($merchant->fresh()->merchant_verified_at);
    }

    public function test_admin_can_hide_and_restore_restaurant_visibility(): void
    {
        $admin = User::factory()->admin()->create();
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

        $this->actingAs($admin)
            ->post(route('admin.restaurants.hide', $restaurant))
            ->assertRedirect();

        $this->assertFalse($restaurant->fresh()->is_visible);

        $this->actingAs($admin)
            ->post(route('admin.restaurants.reveal', $restaurant))
            ->assertRedirect();

        $this->assertTrue($restaurant->fresh()->is_visible);
    }

    public function test_admin_management_routes_are_forbidden_outside_admin_role(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get(route('admin.users.index'))
            ->assertForbidden();

        $this->actingAs($customer)
            ->get(route('admin.restaurants.index'))
            ->assertForbidden();

        $this->actingAs($customer)
            ->get(route('admin.users.show', $admin))
            ->assertForbidden();

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

        $this->actingAs($customer)
            ->post(route('admin.users.suspend', $admin))
            ->assertForbidden();

        $this->actingAs($customer)
            ->post(route('admin.restaurants.hide', $restaurant))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk();
    }
}