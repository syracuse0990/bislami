<?php

namespace App\Http\Controllers\Api\Guest;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Guest\DiscoveryFoodDetailResource;
use App\Http\Resources\Api\Guest\DiscoveryIndexResource;
use App\Http\Resources\Api\Guest\DiscoveryRestaurantDetailResource;
use App\Http\Resources\Api\Guest\DiscoveryRestaurantSummaryResource;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Support\CustomerCatalogData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DiscoveryController extends Controller
{
    public function index(Request $request, CustomerCatalogData $catalogData): DiscoveryIndexResource
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'cuisine' => $request->string('cuisine')->toString(),
            'category' => $request->string('category')->toString(),
        ];

        $payload = $catalogData->publicHome($filters);

        return DiscoveryIndexResource::make($payload)->additional([
            'meta' => [
                'filters' => $filters,
                'cuisines' => $payload['cuisines'],
                'foodCategories' => $payload['foodCategories'],
                'foodsPagination' => $payload['foodsPagination'],
            ],
        ]);
    }

    public function restaurants(Request $request, CustomerCatalogData $catalogData): AnonymousResourceCollection
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'cuisine' => $request->string('cuisine')->toString(),
        ];

        $payload = $catalogData->publicRestaurantIndex($filters);

        return DiscoveryRestaurantSummaryResource::collection(collect($payload['restaurants']))->additional([
            'meta' => [
                'filters' => $filters,
                'cuisines' => $payload['cuisines'],
                'stats' => $payload['stats'],
                'pagination' => $payload['restaurantsPagination'],
            ],
        ]);
    }

    public function showRestaurant(Restaurant $restaurant, CustomerCatalogData $catalogData): DiscoveryRestaurantDetailResource
    {
        return DiscoveryRestaurantDetailResource::make($catalogData->restaurantDetail($restaurant));
    }

    public function showFood(MenuItem $menuItem, CustomerCatalogData $catalogData): DiscoveryFoodDetailResource
    {
        $menuItem->loadMissing('restaurant');

        abort_unless($menuItem->is_available && $menuItem->restaurant?->is_visible, 404);

        return DiscoveryFoodDetailResource::make($catalogData->foodDetail($menuItem));
    }
}