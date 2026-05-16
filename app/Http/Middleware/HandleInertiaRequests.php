<?php

namespace App\Http\Middleware;

use App\Support\CustomerCartService;
use App\Support\GuestCartService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $restaurantBrand = $this->restaurantBrand($request);

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user()
                    ? [
                        ...$request->user()->only(['id', 'name', 'email', 'role']),
                        'merchantVerifiedAt' => $request->user()->merchant_verified_at?->toAtomString(),
                    ]
                    : null,
                'homeRoute' => $request->user()
                    ? route($request->user()->homeRouteName(), absolute: false)
                    : null,
                'restaurantBrand' => $restaurantBrand,
                'restaurantLogoUrl' => $restaurantBrand['logoUrl'] ?? null,
            ],
            'services' => [
                'googleMaps' => [
                    'enabled' => filled(config('services.google_maps.key')),
                ],
                'socialAuth' => [
                    'google' => [
                        'enabled' => filled(config('services.google.client_id'))
                            && filled(config('services.google.client_secret')),
                    ],
                ],
                'realtime' => [
                    'enabled' => config('broadcasting.default') === 'pusher'
                        && filled(config('broadcasting.connections.pusher.key'))
                        && filled(config('broadcasting.connections.pusher.options.host')),
                    'key' => config('broadcasting.connections.pusher.key'),
                    'cluster' => config('broadcasting.connections.pusher.options.cluster'),
                    'wsHost' => config('broadcasting.connections.pusher.options.host'),
                    'wsPort' => (int) config('broadcasting.connections.pusher.options.port', 80),
                    'wssPort' => (int) config('broadcasting.connections.pusher.options.port', 443),
                    'forceTLS' => (bool) config('broadcasting.connections.pusher.options.useTLS', false),
                    'authEndpoint' => url('/broadcasting/auth'),
                ],
            ],
            'seo' => [
                'appUrl' => rtrim((string) config('app.url'), '/'),
                'siteName' => config('app.name', 'BizLami'),
            ],
            'shoppingCart' => fn () => $request->user()?->role === 'customer'
                ? app(CustomerCartService::class)->context($request->user())
                : ($request->user() ? null : app(GuestCartService::class)->context($request)),
            'customerCart' => fn () => $request->user()?->role === 'customer'
                ? app(CustomerCartService::class)->context($request->user())
                : null,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'warning' => fn () => $request->session()->get('warning'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }

    /**
     * @return array{id: int, name: string, logoUrl: string|null}|null
     */
    private function restaurantBrand(Request $request): ?array
    {
        $user = $request->user();

        if (! $user) {
            return null;
        }

        $restaurant = $user->restaurantProfile()
            ->select(['id', 'name', 'logo_path'])
            ->first();

        if (! $restaurant) {
            $assignment = $user->staffAssignments()
                ->with('restaurant:id,name,logo_path')
                ->where('status', 'active')
                ->first();

            $restaurant = $assignment?->restaurant;
        }

        if (! $restaurant) {
            return null;
        }

        return [
            'id' => $restaurant->id,
            'name' => $restaurant->name,
            'logoUrl' => $restaurant->logoUrl(),
        ];
    }
}
