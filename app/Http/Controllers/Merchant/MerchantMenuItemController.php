<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantMenuItemRequest;
use App\Models\ActivityLog;
use App\Models\MenuItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MerchantMenuItemController extends Controller
{
    public function store(MerchantMenuItemRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $restaurant = $request->user()
            ->managedRestaurants()
            ->findOrFail($validated['restaurant_id']);

        $item = $restaurant->menuItems()->create([
            ...$this->menuItemAttributes($validated, $request->file('image')),
            'slug' => $this->uniqueSlug($restaurant->slug, $validated['name']),
        ]);

        ActivityLog::record(
            $request->user(),
            $restaurant->id,
            'menu.item.created',
            "Menu item \"{$item->name}\" created.",
            $item,
        );

        return redirect()->route('merchant.menu.index');
    }

    public function update(MerchantMenuItemRequest $request, MenuItem $menuItem): RedirectResponse
    {
        abort_unless(
            $request->user()->managedRestaurants()->whereKey($menuItem->restaurant_id)->exists(),
            404,
        );

        $validated = $request->validated();
        $restaurant = $request->user()
            ->managedRestaurants()
            ->findOrFail($validated['restaurant_id']);

        $oldImagePath = $menuItem->image_path;
        $attributes = $this->menuItemAttributes($validated, $request->file('image'), $menuItem->image_path);

        $menuItem->update([
            ...$attributes,
            'slug' => $this->uniqueSlug($restaurant->slug, $validated['name'], $menuItem->id),
        ]);

        if ($request->hasFile('image') && $oldImagePath && $oldImagePath !== $menuItem->image_path) {
            Storage::disk('wasabi')->delete($oldImagePath);
        }

        ActivityLog::record(
            $request->user(),
            $menuItem->restaurant_id,
            'menu.item.updated',
            "Menu item \"{$menuItem->name}\" updated.",
            $menuItem,
        );

        return redirect()->route('merchant.menu.index');
    }

    public function toggleAvailability(\Illuminate\Http\Request $request, MenuItem $menuItem): RedirectResponse
    {
        abort_unless(
            $request->user()->managedRestaurants()->whereKey($menuItem->restaurant_id)->exists(),
            403,
        );

        $menuItem->update(['is_available' => ! $menuItem->is_available]);

        return back();
    }

    public function destroy(MenuItem $menuItem): RedirectResponse
    {
        abort_unless(
            request()->user()->managedRestaurants()->whereKey($menuItem->restaurant_id)->exists(),
            404,
        );

        $name = $menuItem->name;
        $restaurantId = $menuItem->restaurant_id;

        if ($menuItem->image_path) {
            Storage::disk('wasabi')->delete($menuItem->image_path);
        }

        $menuItem->delete();

        ActivityLog::record(
            request()->user(),
            $restaurantId,
            'menu.item.deleted',
            "Menu item \"{$name}\" deleted.",
        );

        return redirect()->route('merchant.menu.index')->with('success', 'Menu item deleted.');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function menuItemAttributes(array $validated, ?UploadedFile $image, ?string $currentImagePath = null): array
    {
        unset($validated['image']);

        $normalizedCurrentImagePath = $this->normalizeImagePath($currentImagePath);

        return [
            ...$validated,
            'image_path' => $image
                ? $image->storePublicly('menu-items', 'wasabi')
                : $normalizedCurrentImagePath,
        ];
    }

    private function normalizeImagePath(?string $imagePath): ?string
    {
        if ($imagePath === null) {
            return null;
        }

        $imagePath = trim($imagePath);

        return match ($imagePath) {
            '', '0', 'false', 'null' => null,
            default => $imagePath,
        };
    }

    private function uniqueSlug(string $restaurantSlug, string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($restaurantSlug.' '.$name);
        $slug = $baseSlug;
        $counter = 2;

        while (MenuItem::query()
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}