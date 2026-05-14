<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_homepage_uses_live_food_catalog_data(): void
    {
        $restaurant = Restaurant::create([
            'name' => 'Dhaka Grill',
            'slug' => 'dhaka-grill',
            'category' => 'Bengali',
            'cuisine' => 'Biryani, Kebabs, Grill',
            'min_delivery_time' => 20,
            'max_delivery_time' => 30,
            'rating' => 4.8,
            'delivery_fee' => 0,
            'featured_text' => 'Dinner bundles and charcoal-grilled platters.',
        ]);

        $menuItem = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Smoky Beef Khichuri',
            'slug' => 'dhaka-grill-smoky-beef-khichuri',
            'category' => 'Rice Bowls',
            'description' => 'Slow-cooked beef with fragrant khichuri and achar.',
            'price' => 450,
            'is_available' => true,
        ]);

        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Welcome')
                ->has('foods', 1)
                ->where('foods.0.name', 'Smoky Beef Khichuri')
                ->has('restaurants', 1)
                ->where('restaurants.0.name', 'Dhaka Grill')
                ->has('restaurants.0.menuPreview', 1)
                ->where('restaurants.0.menuPreview.0.id', $menuItem->id)
                ->where('restaurants.0.menuPreview.0.name', 'Smoky Beef Khichuri')
                ->has('foodCategories', 1)
                ->where('foodCategories.0', 'Rice Bowls')
                ->where('stats.restaurantsCount', 1)
                ->where('stats.foodsCount', 1));
    }

    public function test_sitemap_xml_lists_public_discovery_routes_and_available_pages_only(): void
    {
        $restaurant = Restaurant::create([
            'name' => 'Dhaka Grill',
            'slug' => 'dhaka-grill',
            'category' => 'Bengali',
            'cuisine' => 'Biryani, Kebabs, Grill',
            'min_delivery_time' => 20,
            'max_delivery_time' => 30,
            'rating' => 4.8,
            'delivery_fee' => 0,
            'featured_text' => 'Dinner bundles and charcoal-grilled platters.',
        ]);

        $availableMenuItem = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Smoky Beef Khichuri',
            'slug' => 'dhaka-grill-smoky-beef-khichuri',
            'category' => 'Rice Bowls',
            'description' => 'Slow-cooked beef with fragrant khichuri and achar.',
            'price' => 450,
            'is_available' => true,
        ]);

        $unavailableMenuItem = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Hidden Test Dish',
            'slug' => 'dhaka-grill-hidden-test-dish',
            'category' => 'Secret Menu',
            'description' => 'Not meant for public discovery.',
            'price' => 990,
            'is_available' => false,
        ]);

        $this->get(route('sitemap'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('home'), false)
            ->assertSee(route('restaurants.index'), false)
            ->assertSee(route('restaurants.show', $restaurant), false)
            ->assertSee(route('foods.show', $availableMenuItem->slug), false)
            ->assertDontSee(route('foods.show', $unavailableMenuItem->slug), false);
    }

    public function test_robots_txt_references_the_public_sitemap(): void
    {
        $this->get(route('robots'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('User-agent: *', false)
            ->assertSee('Allow: /', false)
            ->assertSee('Sitemap: '.route('sitemap'), false);
    }

    public function test_guest_homepage_exposes_filter_query_props_for_shareable_links(): void
    {
        $this->get('/?search=bowl&cuisine=Healthy%20Bowls&category=Protein%20Plates')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Welcome')
                ->where('filters.search', 'bowl')
                ->where('filters.cuisine', 'Healthy Bowls')
                ->where('filters.category', 'Protein Plates')
                ->where('seo.siteName', config('app.name'))
                ->where('seo.appUrl', rtrim((string) config('app.url'), '/')));
    }

    public function test_guest_homepage_filters_food_results_on_the_server(): void
    {
        $healthyRestaurant = Restaurant::create([
            'name' => 'Green Bowl',
            'slug' => 'green-bowl',
            'category' => 'Healthy Bowls',
            'cuisine' => 'Salads, Smoothies, Protein Plates',
            'min_delivery_time' => 25,
            'max_delivery_time' => 35,
            'rating' => 4.9,
            'delivery_fee' => 29,
            'featured_text' => 'Healthy picks for late-night checkout.',
        ]);

        $biryaniRestaurant = Restaurant::create([
            'name' => 'Dhaka Grill',
            'slug' => 'dhaka-grill',
            'category' => 'Bengali',
            'cuisine' => 'Biryani, Kebabs, Grill',
            'min_delivery_time' => 20,
            'max_delivery_time' => 30,
            'rating' => 4.7,
            'delivery_fee' => 0,
            'featured_text' => 'Charcoal-grilled platters and rice bowls.',
        ]);

        MenuItem::create([
            'restaurant_id' => $healthyRestaurant->id,
            'name' => 'Chicken Protein Bowl',
            'slug' => 'green-bowl-chicken-protein-bowl',
            'category' => 'Protein Plates',
            'description' => 'Grilled chicken, brown rice, greens, and yogurt sauce.',
            'price' => 420,
            'is_available' => true,
        ]);

        MenuItem::create([
            'restaurant_id' => $biryaniRestaurant->id,
            'name' => 'Smoky Beef Khichuri',
            'slug' => 'dhaka-grill-smoky-beef-khichuri',
            'category' => 'Rice Bowls',
            'description' => 'Slow-cooked beef with fragrant khichuri and achar.',
            'price' => 450,
            'is_available' => true,
        ]);

        $this->get('/?search=bowl&cuisine=Healthy%20Bowls&category=Protein%20Plates')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Welcome')
                ->has('foods', 1)
                ->where('foods.0.name', 'Chicken Protein Bowl')
                ->where('foodsPagination.total', 1)
                ->where('foodsPagination.currentPage', 1)
                ->has('restaurants', 1)
                ->where('restaurants.0.name', 'Green Bowl'));
    }

    public function test_authenticated_users_are_redirected_to_their_workspace_from_the_homepage(): void
    {
        $user = User::factory()->customer()->create();

        $this->actingAs($user)
            ->get(route('home'))
            ->assertRedirect(route('customer.dashboard'));
    }

    public function test_guest_can_open_a_public_restaurant_menu_page(): void
    {
        $restaurant = Restaurant::create([
            'name' => 'Green Bowl',
            'slug' => 'green-bowl',
            'category' => 'Healthy Bowls',
            'cuisine' => 'Salads, Smoothies, Protein Plates',
            'min_delivery_time' => 25,
            'max_delivery_time' => 35,
            'rating' => 4.9,
            'delivery_fee' => 29,
            'featured_text' => 'Healthy picks for late-night checkout.',
        ]);

        MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Chicken Protein Bowl',
            'slug' => 'green-bowl-chicken-protein-bowl',
            'category' => 'Protein Plates',
            'description' => 'Grilled chicken, brown rice, greens, and yogurt sauce.',
            'price' => 420,
            'is_available' => true,
        ]);

        $this->get(route('restaurants.show', $restaurant))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Restaurants/Show')
                ->where('restaurant.name', 'Green Bowl')
                ->has('restaurant.menuItems', 1)
                ->where('restaurant.menuItems.0.name', 'Chicken Protein Bowl'));
    }

    public function test_hidden_restaurants_are_excluded_from_public_discovery_and_detail_routes(): void
    {
        $hiddenRestaurant = Restaurant::create([
            'name' => 'Hidden Bowl',
            'slug' => 'hidden-bowl',
            'category' => 'Healthy Bowls',
            'cuisine' => 'Salads, Smoothies, Protein Plates',
            'min_delivery_time' => 25,
            'max_delivery_time' => 35,
            'rating' => 4.9,
            'delivery_fee' => 29,
            'featured_text' => 'Not currently discoverable.',
            'is_visible' => false,
        ]);

        $hiddenMenuItem = MenuItem::create([
            'restaurant_id' => $hiddenRestaurant->id,
            'name' => 'Hidden Chicken Bowl',
            'slug' => 'hidden-bowl-hidden-chicken-bowl',
            'category' => 'Protein Plates',
            'description' => 'A hidden dish.',
            'price' => 420,
            'is_available' => true,
        ]);

        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Welcome')
                ->where('stats.restaurantsCount', 0)
                ->where('stats.foodsCount', 0)
                ->has('foods', 0)
                ->has('restaurants', 0));

        $this->get(route('restaurants.show', $hiddenRestaurant))->assertNotFound();
        $this->get(route('foods.show', $hiddenMenuItem->slug))->assertNotFound();
        $this->get(route('sitemap'))
            ->assertDontSee(route('restaurants.show', $hiddenRestaurant), false)
            ->assertDontSee(route('foods.show', $hiddenMenuItem->slug), false);
    }

    public function test_guest_can_open_a_public_food_detail_page(): void
    {
        $restaurant = Restaurant::create([
            'name' => 'Green Bowl',
            'slug' => 'green-bowl',
            'category' => 'Healthy Bowls',
            'cuisine' => 'Salads, Smoothies, Protein Plates',
            'min_delivery_time' => 25,
            'max_delivery_time' => 35,
            'rating' => 4.9,
            'delivery_fee' => 29,
            'featured_text' => 'Healthy picks for late-night checkout.',
        ]);

        $menuItem = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Chicken Protein Bowl',
            'slug' => 'green-bowl-chicken-protein-bowl',
            'category' => 'Protein Plates',
            'description' => 'Grilled chicken, brown rice, greens, and yogurt sauce.',
            'price' => 420,
            'is_available' => true,
        ]);

        MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Yogurt Parfait',
            'slug' => 'green-bowl-yogurt-parfait',
            'category' => 'Desserts',
            'description' => 'Greek yogurt, fruit compote, and toasted granola.',
            'price' => 150,
            'is_available' => true,
        ]);

        $this->get(route('foods.show', $menuItem->slug))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Foods/Show')
                ->where('food.name', 'Chicken Protein Bowl')
                ->where('food.restaurant.name', 'Green Bowl')
                ->has('food.relatedItems', 1)
                ->where('food.relatedItems.0.name', 'Yogurt Parfait'));
    }

    public function test_guest_can_open_a_public_restaurants_index_page(): void
    {
        $restaurant = Restaurant::create([
            'name' => 'Green Bowl',
            'slug' => 'green-bowl',
            'category' => 'Healthy Bowls',
            'cuisine' => 'Salads, Smoothies, Protein Plates',
            'min_delivery_time' => 25,
            'max_delivery_time' => 35,
            'rating' => 4.9,
            'delivery_fee' => 29,
            'featured_text' => 'Healthy picks for late-night checkout.',
        ]);

        MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Chicken Protein Bowl',
            'slug' => 'green-bowl-chicken-protein-bowl',
            'category' => 'Protein Plates',
            'description' => 'Grilled chicken, brown rice, greens, and yogurt sauce.',
            'price' => 420,
            'is_available' => true,
        ]);

        $this->get('/restaurants?search=green&cuisine=Healthy%20Bowls')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Restaurants/Index')
                ->has('restaurants', 1)
                ->where('restaurants.0.name', 'Green Bowl')
                ->where('filters.search', 'green')
                ->where('filters.cuisine', 'Healthy Bowls'));
    }

    public function test_guest_restaurants_index_filters_results_on_the_server(): void
    {
        $healthyRestaurant = Restaurant::create([
            'name' => 'Green Bowl',
            'slug' => 'green-bowl',
            'category' => 'Healthy Bowls',
            'cuisine' => 'Salads, Smoothies, Protein Plates',
            'min_delivery_time' => 25,
            'max_delivery_time' => 35,
            'rating' => 4.9,
            'delivery_fee' => 29,
            'featured_text' => 'Healthy picks for late-night checkout.',
        ]);

        $biryaniRestaurant = Restaurant::create([
            'name' => 'Dhaka Grill',
            'slug' => 'dhaka-grill',
            'category' => 'Bengali',
            'cuisine' => 'Biryani, Kebabs, Grill',
            'min_delivery_time' => 20,
            'max_delivery_time' => 30,
            'rating' => 4.7,
            'delivery_fee' => 0,
            'featured_text' => 'Charcoal-grilled platters and rice bowls.',
        ]);

        MenuItem::create([
            'restaurant_id' => $healthyRestaurant->id,
            'name' => 'Chicken Protein Bowl',
            'slug' => 'green-bowl-chicken-protein-bowl',
            'category' => 'Protein Plates',
            'description' => 'Grilled chicken, brown rice, greens, and yogurt sauce.',
            'price' => 420,
            'is_available' => true,
        ]);

        MenuItem::create([
            'restaurant_id' => $biryaniRestaurant->id,
            'name' => 'Smoky Beef Khichuri',
            'slug' => 'dhaka-grill-smoky-beef-khichuri',
            'category' => 'Rice Bowls',
            'description' => 'Slow-cooked beef with fragrant khichuri and achar.',
            'price' => 450,
            'is_available' => true,
        ]);

        $this->get('/restaurants?search=protein&cuisine=Healthy%20Bowls')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Restaurants/Index')
                ->has('restaurants', 1)
                ->where('restaurants.0.name', 'Green Bowl')
                ->where('restaurantsPagination.total', 1)
                ->where('restaurantsPagination.currentPage', 1));
    }

    public function test_authenticated_customer_is_redirected_to_customer_restaurant_page_from_public_route(): void
    {
        $user = User::factory()->customer()->create();
        $restaurant = Restaurant::create([
            'name' => 'Green Bowl',
            'slug' => 'green-bowl',
            'category' => 'Healthy Bowls',
            'cuisine' => 'Salads, Smoothies, Protein Plates',
            'min_delivery_time' => 25,
            'max_delivery_time' => 35,
            'rating' => 4.9,
            'delivery_fee' => 29,
            'featured_text' => 'Healthy picks for late-night checkout.',
        ]);

        $this->actingAs($user)
            ->get(route('restaurants.show', $restaurant))
            ->assertRedirect(route('customer.restaurants.show', $restaurant));
    }

    public function test_authenticated_customer_is_redirected_to_customer_restaurant_page_from_public_food_route(): void
    {
        $user = User::factory()->customer()->create();
        $restaurant = Restaurant::create([
            'name' => 'Green Bowl',
            'slug' => 'green-bowl',
            'category' => 'Healthy Bowls',
            'cuisine' => 'Salads, Smoothies, Protein Plates',
            'min_delivery_time' => 25,
            'max_delivery_time' => 35,
            'rating' => 4.9,
            'delivery_fee' => 29,
            'featured_text' => 'Healthy picks for late-night checkout.',
        ]);

        $menuItem = MenuItem::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Chicken Protein Bowl',
            'slug' => 'green-bowl-chicken-protein-bowl',
            'category' => 'Protein Plates',
            'description' => 'Grilled chicken, brown rice, greens, and yogurt sauce.',
            'price' => 420,
            'is_available' => true,
        ]);

        $this->actingAs($user)
            ->get(route('foods.show', $menuItem->slug))
            ->assertRedirect(route('customer.restaurants.show', $restaurant).'#dish-'.$menuItem->id);
    }

    public function test_authenticated_customer_is_redirected_to_customer_restaurants_index_from_public_index(): void
    {
        $user = User::factory()->customer()->create();

        $this->actingAs($user)
            ->get(route('restaurants.index'))
            ->assertRedirect(route('customer.restaurants.index'));
    }
}