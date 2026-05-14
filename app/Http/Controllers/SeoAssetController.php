<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SeoAssetController extends Controller
{
    public function sitemap(): Response
    {
        return response()
            ->view('seo.sitemap', [
                'urls' => $this->sitemapUrls(),
            ])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(): Response
    {
        return response()
            ->view('seo.robots', [
                'sitemapUrl' => route('sitemap'),
            ])
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    /**
     * @return Collection<int, array{loc: string, lastmod: string|null}>
     */
    private function sitemapUrls(): Collection
    {
        $restaurants = Restaurant::query()
            ->visible()
            ->whereHas('menuItems', fn (Builder $query) => $query->where('is_available', true))
            ->withMax([
                'menuItems as latest_menu_item_update' => fn ($query) => $query->where('is_available', true),
            ], 'updated_at')
            ->orderBy('slug')
            ->get(['id', 'slug', 'updated_at']);

        $foods = MenuItem::query()
            ->whereHas('restaurant', fn (Builder $query) => $query->visible())
            ->where('is_available', true)
            ->orderBy('slug')
            ->get(['slug', 'updated_at']);

        $latestCatalogUpdate = $this->latestTimestamp([
            $restaurants->max('updated_at'),
            $restaurants->max('latest_menu_item_update'),
            $foods->max('updated_at'),
        ]);

        return collect([
            [
                'loc' => route('home'),
                'lastmod' => $this->formatTimestamp($latestCatalogUpdate),
            ],
            [
                'loc' => route('restaurants.index'),
                'lastmod' => $this->formatTimestamp($latestCatalogUpdate),
            ],
        ])
            ->merge($restaurants->map(fn (Restaurant $restaurant) => [
                'loc' => route('restaurants.show', $restaurant),
                'lastmod' => $this->formatTimestamp($this->latestTimestamp([
                    $restaurant->updated_at,
                    $restaurant->latest_menu_item_update,
                ])),
            ]))
            ->merge($foods->map(fn (MenuItem $menuItem) => [
                'loc' => route('foods.show', $menuItem->slug),
                'lastmod' => $this->formatTimestamp($menuItem->updated_at),
            ]))
            ->values();
    }

    /**
     * @param  array<int, Carbon|string|null>  $values
     */
    private function latestTimestamp(array $values): ?Carbon
    {
        return collect($values)
            ->filter()
            ->map(fn ($value) => $value instanceof Carbon ? $value : Carbon::parse($value))
            ->sortByDesc(fn (Carbon $value) => $value->getTimestamp())
            ->first();
    }

    private function formatTimestamp(Carbon|string|null $value): ?string
    {
        if (! $value) {
            return null;
        }

        $timestamp = $value instanceof Carbon ? $value : Carbon::parse($value);

        return $timestamp->toAtomString();
    }
}