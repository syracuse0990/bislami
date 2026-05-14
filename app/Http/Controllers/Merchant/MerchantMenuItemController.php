<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantMenuItemRequest;
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

        $restaurant->menuItems()->create([
            ...$this->menuItemAttributes($validated, $request->file('image')),
            'slug' => $this->uniqueSlug($restaurant->slug, $validated['name']),
        ]);

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

        return redirect()->route('merchant.menu.index');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function menuItemAttributes(array $validated, ?UploadedFile $image, ?string $currentImagePath = null): array
    {
        unset($validated['image']);

        return [
            ...$validated,
            'image_path' => $image
                ? $image->storePublicly('menu-items', 'wasabi')
                : $currentImagePath,
        ];
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