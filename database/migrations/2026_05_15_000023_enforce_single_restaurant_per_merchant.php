<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->collapseDuplicateMerchantRestaurants();

        Schema::table('restaurants', function (Blueprint $table): void {
            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table): void {
            $table->dropUnique(['user_id']);
        });
    }

    private function collapseDuplicateMerchantRestaurants(): void
    {
        $duplicateMerchantIds = DB::table('restaurants')
            ->select('user_id')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('user_id');

        foreach ($duplicateMerchantIds as $merchantId) {
            DB::transaction(function () use ($merchantId): void {
                $restaurants = DB::table('restaurants')
                    ->where('user_id', $merchantId)
                    ->orderByRaw('CASE WHEN created_at IS NULL THEN 1 ELSE 0 END')
                    ->orderBy('created_at')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                if ($restaurants->count() < 2) {
                    return;
                }

                $canonicalRestaurant = $restaurants->first();
                $duplicateRestaurants = $restaurants->slice(1)->values();
                $duplicateRestaurantIds = $duplicateRestaurants->pluck('id')->all();

                DB::table('restaurants')
                    ->where('id', $canonicalRestaurant->id)
                    ->update($this->mergedRestaurantAttributes($canonicalRestaurant, $duplicateRestaurants));

                DB::table('menu_items')
                    ->whereIn('restaurant_id', $duplicateRestaurantIds)
                    ->update(['restaurant_id' => $canonicalRestaurant->id]);

                DB::table('orders')
                    ->whereIn('restaurant_id', $duplicateRestaurantIds)
                    ->update(['restaurant_id' => $canonicalRestaurant->id]);

                DB::table('restaurants')
                    ->whereIn('id', $duplicateRestaurantIds)
                    ->delete();
            });
        }
    }

    /**
     * @param  Collection<int, object>  $duplicateRestaurants
     * @return array<string, mixed>
     */
    private function mergedRestaurantAttributes(object $canonicalRestaurant, Collection $duplicateRestaurants): array
    {
        $restaurants = collect([$canonicalRestaurant])->concat($duplicateRestaurants);

        return [
            'featured_text' => $this->firstMeaningfulValue($restaurants, 'featured_text', $canonicalRestaurant->featured_text),
            'contact_phone' => $this->firstMeaningfulValue($restaurants, 'contact_phone', $canonicalRestaurant->contact_phone),
            'logo_path' => $this->firstMeaningfulValue($restaurants, 'logo_path', $canonicalRestaurant->logo_path),
            'location_address' => $this->firstMeaningfulValue($restaurants, 'location_address', $canonicalRestaurant->location_address),
            'location_latitude' => $this->firstMeaningfulValue($restaurants, 'location_latitude', $canonicalRestaurant->location_latitude),
            'location_longitude' => $this->firstMeaningfulValue($restaurants, 'location_longitude', $canonicalRestaurant->location_longitude),
            'delivery_radius_km' => $this->firstMeaningfulValue($restaurants, 'delivery_radius_km', $canonicalRestaurant->delivery_radius_km),
            'minimum_order_value' => $this->firstMeaningfulValue($restaurants, 'minimum_order_value', $canonicalRestaurant->minimum_order_value),
            'preparation_time_min' => $this->firstMeaningfulValue($restaurants, 'preparation_time_min', $canonicalRestaurant->preparation_time_min),
            'preparation_time_max' => $this->firstMeaningfulValue($restaurants, 'preparation_time_max', $canonicalRestaurant->preparation_time_max),
            'order_settings' => $this->firstMeaningfulJson($restaurants, 'order_settings', $canonicalRestaurant->order_settings),
            'operating_hours' => $this->firstMeaningfulJson($restaurants, 'operating_hours', $canonicalRestaurant->operating_hours),
            'closure_dates' => $this->firstMeaningfulJson($restaurants, 'closure_dates', $canonicalRestaurant->closure_dates),
            'updated_at' => now(),
        ];
    }

    /**
     * @param  Collection<int, object>  $restaurants
     */
    private function firstMeaningfulValue(Collection $restaurants, string $column, mixed $fallback): mixed
    {
        foreach ($restaurants as $restaurant) {
            $value = $restaurant->{$column} ?? null;

            if ($value === null) {
                continue;
            }

            if (is_string($value) && trim($value) === '') {
                continue;
            }

            return $value;
        }

        return $fallback;
    }

    /**
     * @param  Collection<int, object>  $restaurants
     */
    private function firstMeaningfulJson(Collection $restaurants, string $column, mixed $fallback): mixed
    {
        foreach ($restaurants as $restaurant) {
            $value = $restaurant->{$column} ?? null;

            if ($value === null) {
                continue;
            }

            $decoded = json_decode($value, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                continue;
            }

            if ($decoded === [] || $decoded === null) {
                continue;
            }

            return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $fallback;
    }
};