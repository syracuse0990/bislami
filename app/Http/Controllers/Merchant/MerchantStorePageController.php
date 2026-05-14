<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MerchantStorePageController extends Controller
{
    public function show(Request $request): Response
    {
        $restaurant = $request->user()
            ->restaurantProfile()
            ->withCount(['menuItems', 'orders'])
            ->first();

        return Inertia::render('Merchant/Profile/Index', [
            'hasProfile' => $restaurant !== null,
            'profile' => $this->transformProfile($request, $restaurant),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function transformProfile(Request $request, ?Restaurant $restaurant): array
    {
        return [
            'id' => $restaurant?->id,
            'slug' => $restaurant?->slug,
            'name' => $restaurant?->name ?? ($request->user()->store_name ?? ''),
            'featuredText' => $restaurant?->featured_text ?? '',
            'contactPhone' => $restaurant?->contact_phone ?? '',
            'accountEmail' => $request->user()->email,
            'logoUrl' => $restaurant?->logoUrl(),
            'locationAddress' => $restaurant?->location_address ?? '',
            'locationLatitude' => $restaurant?->location_latitude,
            'locationLongitude' => $restaurant?->location_longitude,
            'hasPin' => $restaurant?->location_latitude !== null && $restaurant?->location_longitude !== null,
            'mapsUrl' => $restaurant?->mapsUrl(),
            'deliveryRadiusKm' => $restaurant?->delivery_radius_km !== null ? (string) $restaurant->delivery_radius_km : '5',
            'deliveryAreaCoordinates' => collect($restaurant?->delivery_area_coordinates ?? [])->values()->all(),
            'minimumOrderValue' => (string) ($restaurant?->minimum_order_value ?? 0),
            'preparationTimeMin' => (string) ($restaurant?->preparation_time_min ?? 15),
            'preparationTimeMax' => (string) ($restaurant?->preparation_time_max ?? 30),
            'operatingHours' => $restaurant?->normalizedOperatingHours() ?? Restaurant::defaultOperatingHours(),
            'closureDates' => collect($restaurant?->closure_dates ?? [])->values()->all(),
            'menuItemsCount' => (int) ($restaurant?->menu_items_count ?? 0),
            'ordersCount' => (int) ($restaurant?->orders_count ?? 0),
        ];
    }
}