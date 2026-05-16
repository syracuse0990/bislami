<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantStoreRequest;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class MerchantStoreController extends Controller
{
    public function upsert(MerchantStoreRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $restaurant = $request->user()->restaurantProfile()->first();
        $oldLogoPath = $restaurant?->logo_path;
        $attributes = [
            ...$this->profileAttributes($validated, $request->file('logo'), $restaurant),
            'slug' => $this->uniqueSlug($validated['name'], $restaurant?->id),
        ];

        if ($restaurant) {
            $restaurant->update($attributes);
        } else {
            $restaurant = $request->user()->managedRestaurants()->create($attributes);
        }

        if ($request->hasFile('logo') && $oldLogoPath && $oldLogoPath !== $restaurant->logo_path) {
            Storage::disk('wasabi')->delete($oldLogoPath);
        }

        return redirect()
            ->route('merchant.profile.show')
            ->with('success', 'Restaurant profile saved successfully.');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function profileAttributes(array $validated, ?UploadedFile $logo, ?Restaurant $currentProfile = null): array
    {
        unset($validated['logo']);
        $preparationTimeMin = (int) ($validated['preparation_time_min'] ?? $currentProfile?->preparation_time_min ?? 15);
        $preparationTimeMax = (int) ($validated['preparation_time_max'] ?? $currentProfile?->preparation_time_max ?? 30);

        return [
            'name' => trim($validated['name']),
            'featured_text' => trim($validated['featured_text']),
            'contact_phone' => trim($validated['contact_phone']),
            'logo_path' => $this->storeLogo($logo, $currentProfile?->logo_path),
            'location_address' => trim($validated['location_address']),
            'location_latitude' => $validated['location_latitude'] ?? null,
            'location_longitude' => $validated['location_longitude'] ?? null,
            'delivery_radius_km' => array_key_exists('delivery_radius_km', $validated) && $validated['delivery_radius_km'] !== null
                ? (float) $validated['delivery_radius_km']
                : $currentProfile?->delivery_radius_km,
            'delivery_area_coordinates' => collect($validated['delivery_area_coordinates'] ?? [])
                ->map(fn (array $point) => [
                    'lat' => round((float) $point['lat'], 6),
                    'lng' => round((float) $point['lng'], 6),
                ])
                ->values()
                ->all(),
            'minimum_order_value' => (int) $validated['minimum_order_value'],
            'preparation_time_min' => $preparationTimeMin,
            'preparation_time_max' => max($preparationTimeMax, $preparationTimeMin),
            'operating_hours' => collect($validated['operating_hours'])
                ->map(fn (array $hour) => [
                    'day' => $hour['day'],
                    'enabled' => (bool) $hour['enabled'],
                    'open' => $hour['enabled'] ? $hour['open'] : null,
                    'close' => $hour['enabled'] ? $hour['close'] : null,
                ])
                ->values()
                ->all(),
            'closure_dates' => collect($validated['closure_dates'] ?? [])
                ->filter()
                ->map(fn (string $date) => trim($date))
                ->unique()
                ->sort()
                ->values()
                ->all(),
            'category' => $currentProfile?->category ?? 'Restaurant',
            'cuisine' => $currentProfile?->cuisine ?? 'General',
            'min_delivery_time' => $currentProfile?->min_delivery_time ?? 30,
            'max_delivery_time' => $currentProfile?->max_delivery_time ?? 45,
            'delivery_fee' => (int) ($currentProfile?->delivery_fee ?? 0),
            'rating' => (float) ($currentProfile?->rating ?? 0),
            'is_visible' => $currentProfile?->is_visible ?? true,
        ];
    }

    private function storeLogo(?UploadedFile $logo, ?string $currentPath = null): ?string
    {
        if (! $logo) {
            return $currentPath;
        }

        $storedPath = $logo->store('stores', 'wasabi');

        if (! is_string($storedPath) || $storedPath === '') {
            report(new RuntimeException('Merchant restaurant logo upload failed on the wasabi disk.'));

            return $currentPath;
        }

        return $storedPath;
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 2;

        while (Restaurant::query()
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}