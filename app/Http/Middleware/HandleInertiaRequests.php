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
            ],
            'services' => [
                'googleMaps' => [
                    'enabled' => filled(config('services.google_maps.key')),
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
}
