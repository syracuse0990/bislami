<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class SocialAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.google.client_id', 'google-client-id');
        config()->set('services.google.client_secret', 'google-client-secret');
        config()->set('services.google.redirect', 'http://localhost/auth/google/callback');
    }

    public function test_google_redirect_route_redirects_to_provider(): void
    {
        Socialite::shouldReceive('driver->redirect')
            ->once()
            ->andReturn(redirect()->away('https://accounts.google.com/o/oauth2/auth'));

        $response = $this->get(route('auth.social.redirect', ['provider' => 'google']));

        $response->assertRedirect('https://accounts.google.com/o/oauth2/auth');
    }

    public function test_google_callback_can_create_and_authenticate_a_user(): void
    {
        $socialiteUser = (new SocialiteUser())->map([
            'id' => 'google-user-123',
            'name' => 'Google Customer',
            'email' => 'google-customer@example.com',
            'avatar' => null,
        ]);

        Socialite::shouldReceive('driver->user')
            ->once()
            ->andReturn($socialiteUser);

        $response = $this->get(route('auth.social.callback', ['provider' => 'google']));

        $this->assertAuthenticated();

        $user = User::query()->firstWhere('email', 'google-customer@example.com');

        $this->assertModelExists($user);
        $this->assertSame('google', $user->oauth_provider);
        $this->assertSame('google-user-123', $user->oauth_provider_id);
        $this->assertNotNull($user->email_verified_at);

        $response->assertRedirect(route('customer.dashboard', absolute: false));
    }

    public function test_google_callback_links_and_authenticates_existing_user_by_email(): void
    {
        $user = User::factory()->create([
            'email' => 'existing-social@example.com',
            'oauth_provider' => null,
            'oauth_provider_id' => null,
            'email_verified_at' => null,
        ]);

        $socialiteUser = (new SocialiteUser())->map([
            'id' => 'google-existing-456',
            'name' => 'Existing User',
            'email' => 'existing-social@example.com',
            'avatar' => null,
        ]);

        Socialite::shouldReceive('driver->user')
            ->once()
            ->andReturn($socialiteUser);

        $response = $this->get(route('auth.social.callback', ['provider' => 'google']));

        $this->assertAuthenticatedAs($user->fresh());
        $this->assertSame('google', $user->fresh()->oauth_provider);
        $this->assertSame('google-existing-456', $user->fresh()->oauth_provider_id);
        $this->assertNotNull($user->fresh()->email_verified_at);

        $response->assertRedirect(route('customer.dashboard', absolute: false));
    }
}