<?php

namespace Tests\Feature;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MerchantStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_merchant_can_view_restaurant_profile_page(): void
    {
        $merchant = User::factory()->merchant()->create();

        Restaurant::create([
            'user_id' => $merchant->id,
            'name' => 'Dhaka Grill',
            'slug' => 'dhaka-grill',
            'category' => 'Bengali',
            'cuisine' => 'Biryani, Kebabs, Grill',
            'min_delivery_time' => 20,
            'max_delivery_time' => 30,
            'rating' => 4.8,
            'delivery_fee' => 0,
            'featured_text' => 'Dinner bundles and charcoal-grilled platters.',
            'contact_phone' => '01700000000',
            'location_address' => 'Dhanmondi 27, Dhaka',
            'location_latitude' => 23.7529151,
            'location_longitude' => 90.3765482,
            'delivery_radius_km' => 6,
            'delivery_area_coordinates' => [
                ['lat' => 23.760001, 'lng' => 90.367101],
                ['lat' => 23.759421, 'lng' => 90.385721],
                ['lat' => 23.745411, 'lng' => 90.386482],
                ['lat' => 23.744938, 'lng' => 90.369214],
            ],
            'minimum_order_value' => 250,
            'preparation_time_min' => 15,
            'preparation_time_max' => 25,
            'operating_hours' => Restaurant::defaultOperatingHours(),
            'closure_dates' => ['2026-12-16'],
        ]);

        $this->actingAs($merchant)
            ->get(route('merchant.profile.show'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Merchant/Profile/Index')
                ->where('hasProfile', true)
                ->where('profile.name', 'Dhaka Grill')
                ->where('profile.contactPhone', '01700000000')
                ->where('profile.locationAddress', 'Dhanmondi 27, Dhaka')
                ->where('profile.hasPin', true)
                ->has('profile.operatingHours', 7)
                ->has('profile.deliveryAreaCoordinates', 4));
    }

    public function test_profile_page_prefills_registered_store_name_when_profile_is_missing(): void
    {
        $merchant = User::factory()->merchant()->create([
            'store_name' => 'BizLami Test Kitchen',
        ]);

        $this->actingAs($merchant)
            ->get(route('merchant.profile.show'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Merchant/Profile/Index')
                ->where('hasProfile', false)
                ->where('profile.name', 'BizLami Test Kitchen')
                ->where('profile.preparationTimeMin', '15')
                ->where('profile.preparationTimeMax', '30')
                ->has('profile.operatingHours', 7));
    }

    public function test_merchant_can_create_a_restaurant_profile(): void
    {
        $merchant = User::factory()->merchant()->create();

        $this->actingAs($merchant)
            ->put(route('merchant.profile.update'), [
                'name' => 'Green Bowl',
                'featured_text' => 'Healthy picks for late-night checkout.',
                'contact_phone' => '01800000000',
                'location_address' => 'Banani 11, Dhaka',
                'location_latitude' => 23.7935240,
                'location_longitude' => 90.4063230,
                'delivery_area_coordinates' => [
                    ['lat' => 23.801601, 'lng' => 90.398531],
                    ['lat' => 23.801421, 'lng' => 90.414926],
                    ['lat' => 23.786939, 'lng' => 90.416111],
                    ['lat' => 23.786781, 'lng' => 90.398924],
                ],
                'minimum_order_value' => 199,
                'preparation_time_min' => 18,
                'preparation_time_max' => 28,
                'operating_hours' => Restaurant::defaultOperatingHours(),
                'closure_dates' => ['2026-12-25'],
            ])
            ->assertRedirect(route('merchant.profile.show'));

        $this->assertDatabaseHas('restaurants', [
            'user_id' => $merchant->id,
            'name' => 'Green Bowl',
            'contact_phone' => '01800000000',
            'location_address' => 'Banani 11, Dhaka',
            'minimum_order_value' => 199,
        ]);

        $this->assertSame(4, count($merchant->fresh()->restaurantProfile?->delivery_area_coordinates ?? []));
        $this->assertSame(['2026-12-25'], $merchant->fresh()->restaurantProfile?->closure_dates);
    }

    public function test_uploaded_restaurant_logo_is_stored_on_wasabi(): void
    {
        Storage::fake('wasabi');

        $merchant = User::factory()->merchant()->create();
        $logo = UploadedFile::fake()->image('restaurant-logo.png', 320, 320);

        $this->actingAs($merchant)
            ->post(route('merchant.profile.update'), [
                '_method' => 'PUT',
                'name' => 'Wasabi Logo Kitchen',
                'featured_text' => 'Logo upload coverage.',
                'contact_phone' => '01755555555',
                'logo' => $logo,
                'location_address' => 'Bislig City, Surigao del Sur',
                'location_latitude' => 8.187464,
                'location_longitude' => 126.352700,
                'delivery_area_coordinates' => [
                    ['lat' => 8.194184, 'lng' => 126.343101],
                    ['lat' => 8.193811, 'lng' => 126.363528],
                    ['lat' => 8.180101, 'lng' => 126.364219],
                    ['lat' => 8.179954, 'lng' => 126.343764],
                ],
                'minimum_order_value' => 249,
                'preparation_time_min' => 15,
                'preparation_time_max' => 25,
                'operating_hours' => Restaurant::defaultOperatingHours(),
            ])
            ->assertRedirect(route('merchant.profile.show'));

        $restaurant = $merchant->fresh()->restaurantProfile;

        $this->assertNotNull($restaurant);
        $this->assertNotNull($restaurant?->logo_path);
        Storage::disk('wasabi')->assertExists($restaurant->logo_path);
    }

    public function test_repeated_profile_saves_reuse_the_same_restaurant_record(): void
    {
        $merchant = User::factory()->merchant()->create();

        $this->actingAs($merchant)
            ->put(route('merchant.profile.update'), [
                'name' => 'First Profile Save',
                'featured_text' => 'First profile state.',
                'contact_phone' => '01300000000',
                'location_address' => 'Mohakhali, Dhaka',
                'delivery_area_coordinates' => [
                    ['lat' => 23.787861, 'lng' => 90.398111],
                    ['lat' => 23.787611, 'lng' => 90.414417],
                    ['lat' => 23.776418, 'lng' => 90.414517],
                    ['lat' => 23.776199, 'lng' => 90.397954],
                ],
                'minimum_order_value' => 120,
                'preparation_time_min' => 12,
                'preparation_time_max' => 18,
                'operating_hours' => Restaurant::defaultOperatingHours(),
            ])
            ->assertRedirect(route('merchant.profile.show'));

        $restaurantId = $merchant->fresh()->restaurantProfile?->id;

        $this->actingAs($merchant)
            ->put(route('merchant.profile.update'), [
                'name' => 'Second Profile Save',
                'featured_text' => 'Second profile state.',
                'contact_phone' => '01399999999',
                'location_address' => 'Banasree, Dhaka',
                'delivery_area_coordinates' => [
                    ['lat' => 23.770661, 'lng' => 90.430211],
                    ['lat' => 23.770492, 'lng' => 90.446317],
                    ['lat' => 23.758219, 'lng' => 90.446092],
                    ['lat' => 23.758366, 'lng' => 90.429956],
                ],
                'minimum_order_value' => 140,
                'preparation_time_min' => 14,
                'preparation_time_max' => 22,
                'operating_hours' => Restaurant::defaultOperatingHours(),
            ])
            ->assertRedirect(route('merchant.profile.show'));

        $this->assertSame(1, $merchant->fresh()->managedRestaurants()->count());
        $this->assertSame($restaurantId, $merchant->fresh()->restaurantProfile?->id);
        $this->assertDatabaseHas('restaurants', [
            'id' => $restaurantId,
            'name' => 'Second Profile Save',
            'contact_phone' => '01399999999',
        ]);
    }

    public function test_merchant_can_update_their_restaurant_profile(): void
    {
        $merchant = User::factory()->merchant()->create();
        $restaurant = Restaurant::create([
            'user_id' => $merchant->id,
            'name' => 'Spice Lane',
            'slug' => 'spice-lane',
            'category' => 'Fast Food',
            'cuisine' => 'Street Food',
            'min_delivery_time' => 18,
            'max_delivery_time' => 25,
            'rating' => 4.7,
            'delivery_fee' => 49,
            'featured_text' => 'Lunch drops and office meal boxes.',
            'contact_phone' => '01600000000',
            'location_address' => 'Old address',
            'delivery_radius_km' => 5,
            'delivery_area_coordinates' => [
                ['lat' => 23.782601, 'lng' => 90.398511],
                ['lat' => 23.782412, 'lng' => 90.415622],
                ['lat' => 23.770924, 'lng' => 90.415097],
                ['lat' => 23.771133, 'lng' => 90.398227],
            ],
            'minimum_order_value' => 150,
            'preparation_time_min' => 12,
            'preparation_time_max' => 20,
            'operating_hours' => Restaurant::defaultOperatingHours(),
        ]);

        $this->actingAs($merchant)
            ->put(route('merchant.profile.update'), [
                'name' => 'Spice Lane Express',
                'featured_text' => 'Fast wraps, rice bowls, and office specials.',
                'contact_phone' => '01900000000',
                'location_address' => 'Gulshan 1, Dhaka',
                'location_latitude' => 23.7805150,
                'location_longitude' => 90.4073910,
                'delivery_area_coordinates' => [
                    ['lat' => 23.788844, 'lng' => 90.399711],
                    ['lat' => 23.788314, 'lng' => 90.417875],
                    ['lat' => 23.773214, 'lng' => 90.418221],
                    ['lat' => 23.772988, 'lng' => 90.399845],
                ],
                'minimum_order_value' => 249,
                'preparation_time_min' => 15,
                'preparation_time_max' => 24,
                'operating_hours' => collect(Restaurant::defaultOperatingHours())
                    ->map(fn (array $hour) => $hour['day'] === 'friday'
                        ? [...$hour, 'enabled' => false, 'open' => null, 'close' => null]
                        : $hour)
                    ->all(),
                'closure_dates' => ['2026-08-01', '2026-08-02'],
            ])
            ->assertRedirect(route('merchant.profile.show'));

        $this->assertDatabaseHas('restaurants', [
            'id' => $restaurant->id,
            'name' => 'Spice Lane Express',
            'contact_phone' => '01900000000',
            'location_address' => 'Gulshan 1, Dhaka',
            'minimum_order_value' => 249,
        ]);

        $this->assertSame(['2026-08-01', '2026-08-02'], $restaurant->fresh()->closure_dates);
    }

    public function test_updating_profile_does_not_touch_another_merchants_restaurant(): void
    {
        $merchant = User::factory()->merchant()->create();
        $otherMerchant = User::factory()->merchant()->create();
        $restaurant = Restaurant::create([
            'user_id' => $otherMerchant->id,
            'name' => 'Vento Pizza Lab',
            'slug' => 'vento-pizza-lab',
            'category' => 'Italian',
            'cuisine' => 'Pizza, Pasta',
            'min_delivery_time' => 28,
            'max_delivery_time' => 38,
            'rating' => 4.6,
            'delivery_fee' => 39,
            'featured_text' => 'Stone-baked pizzas and pasta bowls.',
            'contact_phone' => '01500000000',
            'location_address' => 'Uttara, Dhaka',
            'delivery_radius_km' => 4,
            'delivery_area_coordinates' => [
                ['lat' => 23.881412, 'lng' => 90.370842],
                ['lat' => 23.881115, 'lng' => 90.387922],
                ['lat' => 23.869214, 'lng' => 90.387551],
                ['lat' => 23.869498, 'lng' => 90.370602],
            ],
            'minimum_order_value' => 199,
            'preparation_time_min' => 18,
            'preparation_time_max' => 26,
            'operating_hours' => Restaurant::defaultOperatingHours(),
        ]);

        $this->actingAs($merchant)
            ->put(route('merchant.profile.update'), [
                'name' => 'My Own Restaurant',
                'featured_text' => 'Nope.',
                'contact_phone' => '01400000000',
                'location_address' => 'Mirpur 10, Dhaka',
                'delivery_area_coordinates' => [
                    ['lat' => 23.812219, 'lng' => 90.355911],
                    ['lat' => 23.811978, 'lng' => 90.372205],
                    ['lat' => 23.799927, 'lng' => 90.371844],
                    ['lat' => 23.800204, 'lng' => 90.355402],
                ],
                'minimum_order_value' => 99,
                'preparation_time_min' => 10,
                'preparation_time_max' => 20,
                'operating_hours' => Restaurant::defaultOperatingHours(),
            ])
            ->assertRedirect(route('merchant.profile.show'));

        $this->assertDatabaseHas('restaurants', [
            'id' => $restaurant->id,
            'name' => 'Vento Pizza Lab',
        ]);

        $this->assertDatabaseHas('restaurants', [
            'user_id' => $merchant->id,
            'name' => 'My Own Restaurant',
        ]);
    }
}