<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CashierPosPageController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        // Merchant owner restaurants first
        $restaurants = $user->managedRestaurants()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn ($r) => ['id' => $r->id, 'name' => $r->name])
            ->values();

        // Fallback: staff assignments
        if ($restaurants->isEmpty()) {
            $restaurants = $user->staffAssignments()
                ->with('restaurant:id,name')
                ->where('status', 'active')
                ->get()
                ->map(fn ($s) => $s->restaurant)
                ->filter()
                ->unique('id')
                ->map(fn ($r) => ['id' => $r->id, 'name' => $r->name])
                ->values();
        }

        abort_if($restaurants->isEmpty(), 403, 'No restaurant assigned.');

        $defaultRestaurantId = (int) ($request->query('restaurant_id', $restaurants->first()['id']));
        $currentRestaurant = $restaurants->firstWhere('id', $defaultRestaurantId)
            ?? $restaurants->first();

        $menuItems = MenuItem::query()
            ->where('restaurant_id', $currentRestaurant['id'])
            ->where('is_available', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->map(fn (MenuItem $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'category' => $item->category ?? 'Other',
                'price' => (int) $item->price,
                'promoPrice' => $item->promo_price ? (int) $item->promo_price : null,
                'imageUrl' => $item->displayImageUrl(),
                'description' => $item->description,
            ])
            ->values();

        $categories = $menuItems
            ->pluck('category')
            ->unique()
            ->sort()
            ->values();

        return Inertia::render('Merchant/Cashier/Pos', [
            'restaurant' => $currentRestaurant,
            'restaurants' => $restaurants,
            'menuItems' => $menuItems,
            'categories' => $categories,
            'lastPlacedOrder' => session()->pull('pos_order_success'),
        ]);
    }
}
