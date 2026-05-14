<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use App\Support\CustomerCatalogData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class RestaurantPageController extends Controller
{
    public function index(Request $request, CustomerCatalogData $catalogData): Response|RedirectResponse
    {
        if ($request->user()?->role === 'customer') {
            return redirect()->route('customer.restaurants.index');
        }

        return Inertia::render('Restaurants/Index', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'filters' => [
                'search' => $request->string('search')->toString(),
                'cuisine' => $request->string('cuisine')->toString(),
                'category' => '',
            ],
            ...$catalogData->publicRestaurantIndex([
                'search' => $request->string('search')->toString(),
                'cuisine' => $request->string('cuisine')->toString(),
            ]),
        ]);
    }

    public function show(Request $request, Restaurant $restaurant, CustomerCatalogData $catalogData): Response|RedirectResponse
    {
        abort_unless($restaurant->is_visible, 404);

        if ($request->user()?->role === 'customer') {
            return redirect()->route('customer.restaurants.show', $restaurant);
        }

        return Inertia::render('Restaurants/Show', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'restaurant' => $catalogData->restaurantDetail($restaurant),
        ]);
    }
}