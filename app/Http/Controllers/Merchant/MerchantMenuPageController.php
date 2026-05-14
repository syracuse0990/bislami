<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MerchantMenuPageController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $categoryOptions = collect([
            'Rice Bowls',
            'Grill Platters',
            'Wraps',
            'Drinks',
            'Protein Plates',
            'Desserts',
        ])->map(fn (string $category) => [
            'label' => $category,
            'value' => $category,
        ])->values();

        $restaurants = $request->user()
            ->managedRestaurants()
            ->with(['menuItems' => fn ($query) => $query
                ->orderBy('category')
                ->orderBy('name')])
            ->orderBy('name')
            ->get()
            ->map(fn ($restaurant) => [
                'id' => $restaurant->id,
                'name' => $restaurant->name,
                'cuisine' => $restaurant->cuisine,
                'totalItems' => $restaurant->menuItems->count(),
                'menuItems' => $restaurant->menuItems->map(fn ($menuItem) => [
                    'id' => $menuItem->id,
                    'restaurantId' => $menuItem->restaurant_id,
                    'name' => $menuItem->name,
                    'category' => $menuItem->category,
                    'description' => $menuItem->description,
                    'imageUrl' => $this->menuItemImageUrl($menuItem),
                    'price' => \App\Support\MoneyFormatter::format((int) $menuItem->price),
                    'priceValue' => $menuItem->price,
                    'isAvailable' => $menuItem->is_available,
                    'availability' => $menuItem->is_available ? 'Live' : 'Paused',
                ])->values(),
            ])
            ->values();

        $restaurantOptions = $restaurants
            ->map(fn (array $restaurant) => [
                'label' => $restaurant['name'],
                'value' => $restaurant['id'],
            ])
            ->values();

        return Inertia::render('Merchant/Menu/Index', [
            'restaurants' => $restaurants,
            'restaurantOptions' => $restaurantOptions,
            'categoryOptions' => $categoryOptions,
        ]);
    }

    private function menuItemImageUrl(MenuItem $menuItem): ?string
    {
        return $menuItem->imageUrl();
    }
}