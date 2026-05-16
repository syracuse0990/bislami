<?php

namespace Tests\Feature;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MerchantMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_merchant_can_view_maintenance_page_for_owned_restaurant(): void
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
            'minimum_order_value' => 250,
            'preparation_time_min' => 15,
            'preparation_time_max' => 25,
            'operating_hours' => Restaurant::defaultOperatingHours(),
            'closure_dates' => [],
        ]);

        $this->actingAs($merchant)
            ->get(route('merchant.maintenance.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Merchant/Maintenance/Index')
                ->where('hasRestaurant', true)
                ->where('restaurant.name', 'Dhaka Grill')
                ->where('isOwner', true));
    }

    public function test_maintenance_page_renders_setup_state_when_profile_is_missing(): void
    {
        $merchant = User::factory()->merchant()->create([
            'store_name' => 'Server Merchant',
        ]);

        $this->actingAs($merchant)
            ->get(route('merchant.maintenance.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Merchant/Maintenance/Index')
                ->where('hasRestaurant', false)
                ->where('restaurant.id', null)
                ->where('restaurant.name', 'Server Merchant')
                ->where('isOwner', false)
                ->where('discountSettings.scDiscountRate', 20)
                ->where('discountSettings.pwdDiscountRate', 20));
    }
}