<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class MerchantMenuPageController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return $this->index($request);
    }

    public function index(Request $request): Response
    {
        return Inertia::render('Merchant/Menu/Index', $this->sharedPayload($request));
    }

    public function create(Request $request): Response
    {
        $shared = $this->sharedPayload($request);

        return Inertia::render('Merchant/Menu/Form', [
            'mode' => 'create',
            'menuItem' => null,
            'restaurantOptions' => $shared['restaurantOptions'],
            'categoryOptions' => $shared['categoryOptions'],
        ]);
    }

    public function edit(Request $request, MenuItem $menuItem): Response
    {
        abort_unless(
            $request->user()->managedRestaurants()->whereKey($menuItem->restaurant_id)->exists(),
            404,
        );

        $menuItem->loadMissing('restaurant');
        $shared = $this->sharedPayload($request);

        return Inertia::render('Merchant/Menu/Form', [
            'mode' => 'edit',
            'menuItem' => $this->transformMenuItem(
                $menuItem,
                $menuItem->restaurant?->name,
                $menuItem->restaurant?->slug,
                $menuItem->restaurant?->cuisine,
            ),
            'restaurantOptions' => $shared['restaurantOptions'],
            'categoryOptions' => $shared['categoryOptions'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function sharedPayload(Request $request): array
    {
        $restaurants = $this->managedRestaurants($request);

        $restaurantOptions = $restaurants
            ->map(fn (array $restaurant) => [
                'label' => $restaurant['name'],
                'value' => $restaurant['id'],
            ])
            ->values();

        $categoryOptions = $this->defaultCategories()
            ->merge(
                $restaurants
                    ->flatMap(fn (array $restaurant) => collect($restaurant['menuItems'])->pluck('category'))
                    ->filter()
                    ->sort()
                    ->values(),
            )
            ->unique()
            ->map(fn (string $category) => [
                'label' => $category,
                'value' => $category,
            ])
            ->values();

        return [
            'restaurants' => $restaurants,
            'restaurantOptions' => $restaurantOptions,
            'categoryOptions' => $categoryOptions,
            'menuItems' => $restaurants
                ->flatMap(fn (array $restaurant) => $restaurant['menuItems'])
                ->values(),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function managedRestaurants(Request $request): Collection
    {
        return $request->user()
            ->managedRestaurants()
            ->with(['menuItems' => fn ($query) => $query
                ->orderBy('category')
                ->orderBy('name')])
            ->orderBy('name')
            ->get()
            ->map(fn ($restaurant) => [
                'id' => $restaurant->id,
                'name' => $restaurant->name,
                'slug' => $restaurant->slug,
                'cuisine' => $restaurant->cuisine,
                'totalItems' => $restaurant->menuItems->count(),
                'liveItemsCount' => $restaurant->menuItems->where('is_available', true)->count(),
                'promoItemsCount' => $restaurant->menuItems->filter(fn ($menuItem) => $menuItem->promo_price !== null)->count(),
                'menuItems' => $restaurant->menuItems
                    ->map(fn ($menuItem) => $this->transformMenuItem($menuItem, $restaurant->name, $restaurant->slug, $restaurant->cuisine))
                    ->values(),
            ])
            ->values();
    }

    private function defaultCategories(): Collection
    {
        return collect([
            'Rice Bowls',
            'Grill Platters',
            'Kebabs',
            'Wraps',
            'Burgers',
            'Fried Chicken',
            'Pizza',
            'Pasta',
            'Sandwiches',
            'Drinks',
            'Protein Plates',
            'Breakfast',
            'Desserts',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function transformMenuItem(MenuItem $menuItem, ?string $restaurantName = null, ?string $restaurantSlug = null, ?string $restaurantCuisine = null): array
    {
        return [
            'id' => $menuItem->id,
            'restaurantId' => $menuItem->restaurant_id,
            'restaurantName' => $restaurantName,
            'restaurantSlug' => $restaurantSlug,
            'restaurantCuisine' => $restaurantCuisine,
            'name' => $menuItem->name,
            'category' => $menuItem->category,
            'description' => $menuItem->description,
            'imageUrl' => $this->menuItemImageUrl($menuItem),
            'basePrice' => \App\Support\MoneyFormatter::format((int) $menuItem->price),
            'price' => \App\Support\MoneyFormatter::format((int) $menuItem->price),
            'priceValue' => $menuItem->price,
            'promoPrice' => $menuItem->promo_price !== null
                ? \App\Support\MoneyFormatter::format((int) $menuItem->promo_price)
                : null,
            'promoPriceValue' => $menuItem->promo_price,
            'effectivePrice' => \App\Support\MoneyFormatter::format($menuItem->effectivePriceValue()),
            'isAvailable' => $menuItem->is_available,
            'availability' => $menuItem->is_available ? 'Live' : 'Paused',
            'availabilityStartsAt' => $menuItem->availability_starts_at,
            'availabilityEndsAt' => $menuItem->availability_ends_at,
            'availabilityWindowLabel' => $this->availabilityWindowLabel($menuItem),
            'variants' => $menuItem->variants ?? [],
            'addOns' => $menuItem->add_ons ?? [],
            'modifiers' => $menuItem->modifiers ?? [],
            'bundleItems' => $menuItem->bundle_items ?? [],
            'counts' => [
                'variants' => count($menuItem->variants ?? []),
                'addOns' => count($menuItem->add_ons ?? []),
                'modifiers' => count($menuItem->modifiers ?? []),
                'bundleItems' => count($menuItem->bundle_items ?? []),
            ],
            'updatedAt' => $menuItem->updated_at?->diffForHumans(),
            'updatedAtDate' => $menuItem->updated_at?->toDayDateTimeString(),
        ];
    }

    private function menuItemImageUrl(MenuItem $menuItem): ?string
    {
        return $menuItem->imageUrl();
    }

    private function availabilityWindowLabel(MenuItem $menuItem): ?string
    {
        if (! $menuItem->availability_starts_at && ! $menuItem->availability_ends_at) {
            return null;
        }

        return collect([$menuItem->availability_starts_at, $menuItem->availability_ends_at])
            ->filter()
            ->join(' - ');
    }
}