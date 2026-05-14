<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Customer\RestaurantDetailResource;
use App\Http\Resources\Api\Customer\RestaurantSummaryResource;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CatalogController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $restaurants = Restaurant::query()
            ->visible()
            ->with(['menuItems' => fn ($query) => $query
                ->where('is_available', true)
                ->orderBy('price')])
            ->whereHas('menuItems', fn (Builder $query) => $query->where('is_available', true))
            ->orderByDesc('rating')
            ->get();

        $cuisines = Restaurant::query()
            ->visible()
            ->whereHas('menuItems', fn (Builder $query) => $query->where('is_available', true))
            ->orderBy('category')
            ->pluck('category')
            ->unique()
            ->values();

        return RestaurantSummaryResource::collection($restaurants)
            ->additional([
                'meta' => [
                    'cuisines' => $cuisines,
                ],
            ]);
    }

    public function show(Restaurant $restaurant): RestaurantDetailResource
    {
        abort_unless($restaurant->is_visible, 404);

        $restaurant->load([
            'menuItems' => fn ($query) => $query
                ->where('is_available', true)
                ->orderBy('category')
                ->orderBy('price')
                ->orderBy('name'),
        ]);

        return RestaurantDetailResource::make($restaurant);
    }
}