<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\MenuItemDailyCapacity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class MerchantDailyMenuController extends Controller
{
    public function index(Request $request): Response
    {
        $today = today();
        $yesterday = today()->subDay();

        $restaurants = $request->user()
            ->managedRestaurants()
            ->with(['menuItems' => fn ($q) => $q
                ->with(['dailyCapacities' => fn ($q) => $q->whereDate('date', '>=', $yesterday)->whereDate('date', '<=', $today)])
                ->orderBy('category')
                ->orderBy('name'),
            ])
            ->orderBy('name')
            ->get();

        $groups = $restaurants->map(fn ($restaurant) => [
            'id' => $restaurant->id,
            'name' => $restaurant->name,
            'items' => $restaurant->menuItems->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'category' => $item->category ?? 'Uncategorized',
                'isAvailable' => $item->is_available,
                'imageUrl' => $item->imageUrl(),
                'capacity' => $item->dailyCapacities->first(fn ($c) => $c->date->isSameDay($today))?->capacity,
                'yesterdayCapacity' => $item->dailyCapacities->first(fn ($c) => $c->date->isSameDay($yesterday))?->capacity,
            ])->values(),
        ])->values();

        return Inertia::render('Merchant/Menu/DailyMenu', [
            'groups' => $groups,
            'today' => $today->toDateString(),
            'multipleRestaurants' => $restaurants->count() > 1,
            'hasYesterdayData' => $restaurants->flatMap->menuItems->flatMap->dailyCapacities
                ->filter(fn ($c) => $c->date->isSameDay($yesterday))->isNotEmpty(),
        ]);
    }

    public function bulkUpdate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'capacities' => ['required', 'array'],
            'capacities.*' => ['nullable', 'integer', 'min:1', 'max:9999'],
        ]);

        $merchantItemIds = $this->merchantMenuItemIds($request);

        foreach ($validated['capacities'] as $menuItemId => $capacity) {
            $menuItemId = (int) $menuItemId;

            abort_unless(in_array($menuItemId, $merchantItemIds), 403);

            if ($capacity === null) {
                MenuItemDailyCapacity::where('menu_item_id', $menuItemId)
                    ->whereDate('date', today())
                    ->delete();
            } else {
                MenuItemDailyCapacity::updateOrCreate(
                    ['menu_item_id' => $menuItemId, 'date' => today()],
                    ['capacity' => $capacity],
                );
            }
        }

        return back()->with('success', 'Daily limits saved.');
    }

    /** @return array<int> */
    private function merchantMenuItemIds(Request $request): array
    {
        return $request->user()
            ->managedRestaurants()
            ->with('menuItems:id,restaurant_id')
            ->get()
            ->flatMap(fn ($r) => $r->menuItems->pluck('id'))
            ->all();
    }
}
