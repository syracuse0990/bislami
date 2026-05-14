<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class SocialiteController extends Controller
{
    private const SUPPORTED_PROVIDERS = ['google'];

    public function redirect(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, self::SUPPORTED_PROVIDERS, true), 404);

        if (! $this->providerIsConfigured($provider)) {
            return redirect()->route('login')->with('warning', 'Google sign in is not configured yet.');
        }

        return Socialite::driver($provider)->redirect();
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, self::SUPPORTED_PROVIDERS, true), 404);

        if (! $this->providerIsConfigured($provider)) {
            return redirect()->route('login')->with('warning', 'Google sign in is not configured yet.');
        }

        try {
            $oauthUser = Socialite::driver($provider)->user();
        } catch (Throwable $throwable) {
            report($throwable);

            return redirect()->route('login')->with('error', 'We could not complete your Google sign in. Please try again.');
        }

        $email = Str::lower(trim((string) $oauthUser->getEmail()));

        if ($email === '') {
            return redirect()->route('login')->with('error', 'Your Google account did not return an email address for BizLami sign in.');
        }

        $user = User::query()
            ->where('oauth_provider', $provider)
            ->where('oauth_provider_id', (string) $oauthUser->getId())
            ->first();

        if (! $user) {
            $user = User::query()->firstWhere('email', $email);
        }

        $wasRecentlyCreated = false;

        if (! $user) {
            $user = User::create([
                'name' => Str::squish($oauthUser->getName() ?: Str::before($email, '@')),
                'email' => $email,
                'email_verified_at' => now(),
                'role' => 'customer',
                'password' => Hash::make(Str::random(40)),
                'oauth_provider' => $provider,
                'oauth_provider_id' => (string) $oauthUser->getId(),
            ]);

            $wasRecentlyCreated = true;
        } else {
            $user->forceFill([
                'name' => filled($user->name) ? $user->name : Str::squish($oauthUser->getName() ?: Str::before($email, '@')),
                'email_verified_at' => $user->email_verified_at ?? now(),
                'oauth_provider' => $provider,
                'oauth_provider_id' => (string) $oauthUser->getId(),
            ])->save();
        }

        if ($user->isSuspended()) {
            return redirect()->route('login')->with('error', 'Your account has been suspended. Contact BizLami support to restore access.');
        }

        if ($wasRecentlyCreated) {
            event(new Registered($user));
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        $intended = $request->session()->pull('url.intended');
        $hasGuestCart = collect(data_get($request->session()->get('guest_cart', []), 'items', []))->isNotEmpty();

        if (filled($intended)) {
            return redirect()->to($intended);
        }

        if ($hasGuestCart && $user->role === 'customer') {
            return redirect()->route('checkout.index');
        }

        return redirect()->intended(route($user->homeRouteName(), absolute: false));
    }

    private function providerIsConfigured(string $provider): bool
    {
        return filled(config("services.{$provider}.client_id"))
            && filled(config("services.{$provider}.client_secret"));
    }
}