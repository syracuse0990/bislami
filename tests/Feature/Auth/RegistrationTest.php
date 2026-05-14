<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_customer_accounts_can_register(): void
    {
        $response = $this->post('/register', [
            'account_type' => 'customer',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();

        $user = User::query()->firstWhere('email', 'test@example.com');

        $this->assertModelExists($user);
        $this->assertSame('customer', $user->role);
        $this->assertNull($user->store_name);

        $response->assertRedirect(route('customer.dashboard', absolute: false));
    }

    public function test_merchant_accounts_can_register_and_land_on_pending_approval(): void
    {
        $response = $this->post('/register', [
            'account_type' => 'merchant',
            'name' => 'Merchant Owner',
            'store_name' => 'BizLami Test Kitchen',
            'email' => 'merchant@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();

        $merchant = User::query()->firstWhere('email', 'merchant@example.com');

        $this->assertModelExists($merchant);
        $this->assertSame('merchant', $merchant->role);
        $this->assertSame('BizLami Test Kitchen', $merchant->store_name);
        $this->assertNull($merchant->merchant_verified_at);

        $response->assertRedirect(route('merchant.dashboard', absolute: false));

        $this->actingAs($merchant)
            ->get(route('merchant.dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Merchant/PendingApproval')
                ->where('merchant.email', 'merchant@example.com'));
    }

    public function test_merchant_registration_requires_a_store_name(): void
    {
        $response = $this->from('/register')->post('/register', [
            'account_type' => 'merchant',
            'name' => 'Merchant Owner',
            'store_name' => '',
            'email' => 'merchant@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertRedirect('/register')
            ->assertSessionHasErrors('store_name');

        $this->assertGuest();
    }
}
