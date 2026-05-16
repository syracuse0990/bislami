<?php

namespace Tests\Feature\Auth;

use App\Models\Restaurant;
use App\Models\RestaurantStaff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('customer.dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_suspended_users_can_not_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create([
            'is_suspended' => true,
            'suspended_at' => now(),
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');
    }

    public function test_suspended_authenticated_users_are_redirected_out_of_protected_web_routes(): void
    {
        $user = User::factory()->customer()->create([
            'is_suspended' => true,
            'suspended_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('customer.dashboard'));

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_merchant_users_are_redirected_to_their_role_home_after_login(): void
    {
        $user = User::factory()->merchant()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('merchant.dashboard', absolute: false));
    }

    public function test_cashier_staff_are_promoted_and_redirected_to_cashier_dashboard_after_login(): void
    {
        $merchantOwner = User::factory()->merchant()->create();

        $restaurant = Restaurant::create([
            'user_id' => $merchantOwner->id,
            'name' => 'Lami Ka',
            'slug' => 'lami-ka',
            'category' => 'Restaurant',
            'cuisine' => 'Rice Bowls, Grill',
            'min_delivery_time' => 20,
            'max_delivery_time' => 30,
            'rating' => 4.8,
            'delivery_fee' => 0,
            'featured_text' => 'Cashier login redirect test restaurant.',
            'minimum_order_value' => 0,
            'preparation_time_min' => 15,
            'preparation_time_max' => 30,
            'operating_hours' => Restaurant::defaultOperatingHours(),
            'closure_dates' => [],
        ]);

        $cashier = User::factory()->customer()->create([
            'email' => 'cashier@example.com',
        ]);

        RestaurantStaff::create([
            'restaurant_id' => $restaurant->id,
            'user_id' => $cashier->id,
            'invited_email' => $cashier->email,
            'invited_name' => 'Counter Cashier',
            'role' => 'cashier',
            'permissions' => RestaurantStaff::DEFAULT_PERMISSIONS['cashier'],
            'status' => 'active',
            'invited_by' => $merchantOwner->id,
            'invited_at' => now(),
            'accepted_at' => now(),
        ]);

        $response = $this->post('/login', [
            'email' => $cashier->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $this->assertSame('merchant', $cashier->fresh()->role);
        $response->assertRedirect(route('merchant.cashier.dashboard', absolute: false));
    }
}
