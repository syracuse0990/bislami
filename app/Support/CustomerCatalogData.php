<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Models\MenuItem;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Builder;

class CustomerCatalogData
{
    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $restaurants = Restaurant::query()
            ->visible()
            ->with(['menuItems' => fn ($query) => $query
                ->where('is_available', true)
                ->orderBy('price')
                ->orderBy('name')])
            ->whereHas('menuItems', fn (Builder $query) => $query->where('is_available', true))
            ->orderByDesc('rating')
            ->get();

        $restaurantCards = $restaurants
            ->map(fn (Restaurant $restaurant) => $this->transformRestaurant($restaurant))
            ->values();

        $foods = $restaurants
            ->flatMap(fn (Restaurant $restaurant) => $restaurant->menuItems
                ->map(fn (MenuItem $menuItem) => $this->transformFood($restaurant, $menuItem)))
            ->values();

        $averageDeliveryMinutes = $restaurants->isNotEmpty()
            ? (int) round($restaurants->avg(fn (Restaurant $restaurant) => ($restaurant->min_delivery_time + $restaurant->max_delivery_time) / 2))
            : 0;

        return [
            'cuisines' => $restaurants->pluck('category')->unique()->values(),
            'foodCategories' => $foods->pluck('category')->unique()->values(),
            'restaurants' => $restaurantCards,
            'foods' => $foods,
            'stats' => [
                'restaurantsCount' => $restaurantCards->count(),
                'foodsCount' => $foods->count(),
                'averageDeliveryMinutes' => $averageDeliveryMinutes,
                'highestRatedRestaurant' => $restaurantCards->first()['name'] ?? null,
            ],
        ];
    }

    /**
     * @param  array<string, string>  $filters
     * @return array<string, mixed>
     */
    public function publicHome(array $filters = []): array
    {
        $normalizedFilters = $this->normalizeFilters($filters);

        $foods = $this->publicFoodQuery($normalizedFilters)
            ->paginate(9)
            ->withQueryString();

        $restaurants = $this->publicRestaurantQuery($normalizedFilters)
            ->limit(6)
            ->get();

        return [
            'cuisines' => $this->availableCuisines(),
            'foodCategories' => $this->availableFoodCategories(),
            'restaurants' => $restaurants
                ->map(fn (Restaurant $restaurant) => $this->transformRestaurant($restaurant))
                ->values(),
            'foods' => $foods
                ->getCollection()
                ->map(fn (MenuItem $menuItem) => $this->transformFood($menuItem->restaurant, $menuItem))
                ->values(),
            'foodsPagination' => $this->transformPagination($foods),
            'stats' => $this->catalogStats(),
        ];
    }

    /**
     * @param  array<string, string>  $filters
     * @return array<string, mixed>
     */
    public function publicRestaurantIndex(array $filters = []): array
    {
        $normalizedFilters = $this->normalizeFilters($filters);

        $restaurants = $this->publicRestaurantQuery($normalizedFilters)
            ->paginate(9)
            ->withQueryString();

        return [
            'cuisines' => $this->availableCuisines(),
            'restaurants' => $restaurants
                ->getCollection()
                ->map(fn (Restaurant $restaurant) => $this->transformRestaurant($restaurant))
                ->values(),
            'restaurantsPagination' => $this->transformPagination($restaurants),
            'stats' => $this->catalogStats(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function restaurantDetail(Restaurant $restaurant): array
    {
        abort_unless($restaurant->is_visible, 404);

        $restaurant->load([
            'menuItems' => fn ($query) => $query
                ->where('is_available', true)
                ->orderBy('category')
                ->orderBy('price')
                ->orderBy('name'),
        ]);

        return [
            'id' => $restaurant->id,
            'name' => $restaurant->name,
            'slug' => $restaurant->slug,
            'cuisine' => $restaurant->cuisine,
            'category' => $restaurant->category,
            'featured' => $restaurant->featured_text,
            'rating' => number_format($restaurant->rating, 1).' rating',
            'eta' => "{$restaurant->min_delivery_time}-{$restaurant->max_delivery_time} min",
            'deliveryFee' => $this->formatMoney($restaurant->delivery_fee, 'Free delivery'),
            'categories' => $restaurant->menuItems
                ->pluck('category')
                ->unique()
                ->values(),
            'menuItems' => $restaurant->menuItems
                ->map(fn (MenuItem $menuItem) => [
                    'id' => $menuItem->id,
                    'restaurantId' => $restaurant->id,
                    'slug' => $menuItem->slug,
                    'name' => $menuItem->name,
                    'category' => $menuItem->category,
                    'description' => $menuItem->description,
                    'imageUrl' => $menuItem->imageUrl(),
                    'price' => $this->formatMoney($menuItem->price),
                ])
                ->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function foodDetail(MenuItem $menuItem): array
    {
        $menuItem->load([
            'restaurant',
            'restaurant.menuItems' => fn ($query) => $query
                ->where('is_available', true)
                ->orderBy('price')
                ->orderBy('name'),
        ]);

        $restaurant = $menuItem->restaurant;

    abort_unless($menuItem->is_available && $restaurant?->is_visible, 404);

        return [
            'id' => $menuItem->id,
            'slug' => $menuItem->slug,
            'name' => $menuItem->name,
            'category' => $menuItem->category,
            'description' => $menuItem->description,
            'imageUrl' => $menuItem->imageUrl(),
            'price' => $this->formatMoney($menuItem->price),
            'priceValue' => (int) $menuItem->price,
            'restaurant' => [
                'id' => $restaurant->id,
                'name' => $restaurant->name,
                'slug' => $restaurant->slug,
                'category' => $restaurant->category,
                'cuisine' => $restaurant->cuisine,
                'featured' => $restaurant->featured_text,
                'rating' => number_format($restaurant->rating, 1).' rating',
                'eta' => "{$restaurant->min_delivery_time}-{$restaurant->max_delivery_time} min",
                'deliveryFee' => $this->formatMoney($restaurant->delivery_fee, 'Free delivery'),
            ],
            'relatedItems' => $restaurant->menuItems
                ->where('id', '!=', $menuItem->id)
                ->take(3)
                ->map(fn (MenuItem $relatedItem) => $this->transformFood($restaurant, $relatedItem))
                ->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function transformRestaurant(Restaurant $restaurant): array
    {
        $featuredMenuItem = $restaurant->menuItems->first();

        return [
            'id' => $restaurant->id,
            'slug' => $restaurant->slug,
            'category' => $restaurant->category,
            'menuItemId' => $featuredMenuItem?->id,
            'menuItemsCount' => $restaurant->menuItems->count(),
            'name' => $restaurant->name,
            'cuisine' => $restaurant->cuisine,
            'eta' => "{$restaurant->min_delivery_time}-{$restaurant->max_delivery_time} min",
            'rating' => number_format($restaurant->rating, 1).' rating',
            'deliveryFee' => $this->formatMoney($restaurant->delivery_fee, 'Free delivery'),
            'featured' => $restaurant->featured_text,
            'featuredItem' => $featuredMenuItem?->name ?? 'Menu coming soon',
            'featuredImageUrl' => $featuredMenuItem?->imageUrl(),
            'featuredPrice' => $featuredMenuItem
                ? $this->formatMoney($featuredMenuItem->price)
                : '',
            'menuPreview' => $restaurant->menuItems
                ->take(3)
                ->map(fn (MenuItem $menuItem) => [
                    'id' => $menuItem->id,
                    'slug' => $menuItem->slug,
                    'name' => $menuItem->name,
                    'category' => $menuItem->category,
                    'imageUrl' => $menuItem->imageUrl(),
                    'price' => $this->formatMoney($menuItem->price),
                ])
                ->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function transformFood(Restaurant $restaurant, MenuItem $menuItem): array
    {
        return [
            'id' => $menuItem->id,
            'slug' => $menuItem->slug,
            'restaurantId' => $restaurant->id,
            'restaurantSlug' => $restaurant->slug,
            'restaurantName' => $restaurant->name,
            'restaurantCategory' => $restaurant->category,
            'restaurantCuisine' => $restaurant->cuisine,
            'restaurantFeaturedText' => $restaurant->featured_text,
            'name' => $menuItem->name,
            'category' => $menuItem->category,
            'description' => $menuItem->description,
            'imageUrl' => $menuItem->imageUrl(),
            'eta' => "{$restaurant->min_delivery_time}-{$restaurant->max_delivery_time} min",
            'etaMinutes' => $restaurant->max_delivery_time,
            'rating' => number_format($restaurant->rating, 1).' rating',
            'ratingValue' => (float) $restaurant->rating,
            'deliveryFee' => $this->formatMoney($restaurant->delivery_fee, 'Free delivery'),
            'deliveryFeeValue' => (int) $restaurant->delivery_fee,
            'price' => $this->formatMoney($menuItem->price),
            'priceValue' => (int) $menuItem->price,
        ];
    }

    /**
     * @param  array<string, string>  $filters
     */
    private function publicFoodQuery(array $filters): Builder
    {
        $search = $this->likeSearch($filters['search']);

        return MenuItem::query()
            ->select('menu_items.*')
            ->join('restaurants', 'restaurants.id', '=', 'menu_items.restaurant_id')
            ->with('restaurant')
            ->where('menu_items.is_available', true)
            ->where('restaurants.is_visible', true)
            ->when($search, function (Builder $query, string $searchTerm) {
                $query->where(function (Builder $innerQuery) use ($searchTerm) {
                    $innerQuery
                        ->where('menu_items.name', 'like', $searchTerm)
                        ->orWhere('menu_items.description', 'like', $searchTerm)
                        ->orWhere('menu_items.category', 'like', $searchTerm)
                        ->orWhere('restaurants.name', 'like', $searchTerm)
                        ->orWhere('restaurants.category', 'like', $searchTerm)
                        ->orWhere('restaurants.cuisine', 'like', $searchTerm)
                        ->orWhere('restaurants.featured_text', 'like', $searchTerm);
                });
            })
            ->when($filters['cuisine'] !== '', fn (Builder $query) => $query->where('restaurants.category', $filters['cuisine']))
            ->when($filters['category'] !== '', fn (Builder $query) => $query->where('menu_items.category', $filters['category']))
            ->orderByDesc('restaurants.rating')
            ->orderBy('menu_items.price')
            ->orderBy('menu_items.name');
    }

    /**
     * @param  array<string, string>  $filters
     */
    private function publicRestaurantQuery(array $filters): Builder
    {
        $search = $this->likeSearch($filters['search']);

        return Restaurant::query()
            ->visible()
            ->with(['menuItems' => fn ($query) => $query
                ->where('is_available', true)
                ->orderBy('price')
                ->orderBy('name')])
            ->whereHas('menuItems', fn (Builder $query) => $query->where('is_available', true))
            ->when($search, function (Builder $query, string $searchTerm) {
                $query->where(function (Builder $innerQuery) use ($searchTerm) {
                    $innerQuery
                        ->where('name', 'like', $searchTerm)
                        ->orWhere('category', 'like', $searchTerm)
                        ->orWhere('cuisine', 'like', $searchTerm)
                        ->orWhere('featured_text', 'like', $searchTerm)
                        ->orWhereHas('menuItems', fn (Builder $menuQuery) => $menuQuery
                            ->where('is_available', true)
                            ->where(function (Builder $menuSearchQuery) use ($searchTerm) {
                                $menuSearchQuery
                                    ->where('name', 'like', $searchTerm)
                                    ->orWhere('category', 'like', $searchTerm)
                                    ->orWhere('description', 'like', $searchTerm);
                            }));
                });
            })
            ->when($filters['cuisine'] !== '', fn (Builder $query) => $query->where('category', $filters['cuisine']))
            ->orderByDesc('rating');
    }

    /**
     * @return array<int, string>
     */
    private function availableCuisines(): array
    {
        return Restaurant::query()
            ->visible()
            ->whereHas('menuItems', fn (Builder $query) => $query->where('is_available', true))
            ->orderBy('category')
            ->pluck('category')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function availableFoodCategories(): array
    {
        return MenuItem::query()
            ->where('is_available', true)
            ->whereHas('restaurant', fn (Builder $query) => $query->visible())
            ->orderBy('category')
            ->pluck('category')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function catalogStats(): array
    {
        $restaurants = Restaurant::query()
            ->visible()
            ->whereHas('menuItems', fn (Builder $query) => $query->where('is_available', true))
            ->get(['id', 'name', 'rating', 'min_delivery_time', 'max_delivery_time']);

        $averageDeliveryMinutes = $restaurants->isNotEmpty()
            ? (int) round($restaurants->avg(fn (Restaurant $restaurant) => ($restaurant->min_delivery_time + $restaurant->max_delivery_time) / 2))
            : 0;

        return [
            'restaurantsCount' => $restaurants->count(),
            'foodsCount' => MenuItem::query()
                ->where('is_available', true)
                ->whereHas('restaurant', fn (Builder $query) => $query->visible())
                ->count(),
            'averageDeliveryMinutes' => $averageDeliveryMinutes,
            'highestRatedRestaurant' => $restaurants
                ->sortByDesc('rating')
                ->first()?->name,
        ];
    }

    /**
     * @param  array<string, string>  $filters
     * @return array{search: string, cuisine: string, category: string}
     */
    private function normalizeFilters(array $filters): array
    {
        return [
            'search' => trim((string) ($filters['search'] ?? '')),
            'cuisine' => trim((string) ($filters['cuisine'] ?? '')),
            'category' => trim((string) ($filters['category'] ?? '')),
        ];
    }

    private function likeSearch(string $value): ?string
    {
        return $value === '' ? null : '%'.$value.'%';
    }

    /**
     * @return array<string, int|null>
     */
    private function transformPagination(LengthAwarePaginator $paginator): array
    {
        return [
            'currentPage' => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
            'perPage' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }

    private function formatMoney(int $amount, ?string $zeroLabel = null): string
    {
        return \App\Support\MoneyFormatter::format($amount, $zeroLabel);
    }
}