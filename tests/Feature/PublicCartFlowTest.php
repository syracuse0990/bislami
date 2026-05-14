<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicCartFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_add_a_public_dish_to_cart_and_review_it(): void
    {
        [$restaurant, $menuItem] = $this->createPublicDish();

        $this->post(route('cart.store', $menuItem), [
            'redirect_to' => route('restaurants.show', $restaurant, absolute: false),
        ])->assertRedirect(route('restaurants.show', $restaurant));

        $this->get(route('cart.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cart/Index')
                ->where('cart.restaurant', 'Green Bowl')
                ->where('cart.items.0.name', 'Chicken Protein Bowl')
                ->where('cart.items.0.quantityValue', 1)
                ->where('cart.total', '₱474'));
    }

    public function test_guest_must_confirm_replacing_a_public_cart_from_another_restaurant(): void
    {
        [$greenBowl, $greenBowlItem] = $this->createPublicDish();
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

        $this->post(route('cart.store', $greenBowlItem));

        $this->post(route('cart.store', $dhakaGrillItem), [
            'redirect_to' => route('foods.show', $dhakaGrillItem->slug, absolute: false),
        ])
            ->assertRedirect(route('foods.show', $dhakaGrillItem->slug))
            ->assertSessionHas('error', 'Your cart already contains items from Green Bowl. Confirm replacing it before adding Smoky Beef Khichuri.');

        $this->get(route('cart.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cart/Index')
                ->where('cart.restaurant', 'Green Bowl')
                ->where('cart.items.0.name', 'Chicken Protein Bowl')
                ->has('cart.items', 1));
    }

    public function test_guest_checkout_redirects_to_login_and_then_merges_cart_into_customer_checkout(): void
    {
        [$restaurant, $menuItem] = $this->createPublicDish();
        $user = User::factory()->customer()->create([
            'password' => Hash::make('secret-pass-123'),
        ]);

        $this->post(route('cart.store', $menuItem))->assertRedirect(route('cart.index'));

        $this->get(route('checkout.index'))
            ->assertRedirect(route('login'));

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'secret-pass-123',
        ])->assertRedirect(route('checkout.index'));

        $this->get(route('checkout.index'))
            ->assertRedirect(route('customer.checkout.index'));

        $cartOrder = $user->fresh()->orders()->where('status', 'cart')->firstOrFail();

        $this->assertSame($restaurant->id, $cartOrder->restaurant_id);
        $this->assertSame(420, $cartOrder->subtotal);
        $this->assertSame(474, $cartOrder->total);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $cartOrder->id,
            'menu_item_id' => $menuItem->id,
            'name' => 'Chicken Protein Bowl',
        ]);

        $this->get(route('customer.checkout.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Customer/Checkout/Index')
                ->where('checkout.restaurant', 'Green Bowl')
                ->where('checkout.total', '₱474'));
    }

    public function test_guest_can_register_from_checkout_and_keep_their_cart(): void
    {
        [, $menuItem] = $this->createPublicDish();

        $this->post(route('cart.store', $menuItem))->assertRedirect(route('cart.index'));
        $this->get(route('checkout.index'))->assertRedirect(route('login'));

        $this->post(route('register'), [
            'name' => 'Guest Checkout Customer',
            'email' => 'guest-checkout@example.com',
            'password' => 'secret-pass-123',
            'password_confirmation' => 'secret-pass-123',
        ])->assertRedirect(route('checkout.index'));

        $user = User::query()->where('email', 'guest-checkout@example.com')->firstOrFail();

        $this->get(route('checkout.index'))
            ->assertRedirect(route('customer.checkout.index'));

        $cartOrder = $user->fresh()->orders()->where('status', 'cart')->firstOrFail();

        $this->assertSame('cart', $cartOrder->status);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $cartOrder->id,
            'menu_item_id' => $menuItem->id,
            'name' => 'Chicken Protein Bowl',
        ]);
    }

    /**
     * @return array{Restaurant, MenuItem}
     */
    private function createPublicDish(): array
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

        return [$restaurant, $menuItem];
    }
}