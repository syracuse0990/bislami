<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $customer = User::query()->updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Demo Customer',
                'role' => 'customer',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        $returningCustomer = User::query()->updateOrCreate(
            ['email' => 'customer.two@example.com'],
            [
                'name' => 'Returning Customer',
                'role' => 'customer',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        $merchant = User::query()->updateOrCreate(
            ['email' => 'merchant@example.com'],
            [
                'name' => 'Demo Merchant',
                'role' => 'merchant',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        $courier = User::query()->updateOrCreate(
            ['email' => 'courier@example.com'],
            [
                'name' => 'Demo Courier',
                'role' => 'courier',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Demo Admin',
                'role' => 'admin',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        $restaurants = collect([
            [
                'user_id' => $merchant->id,
                'name' => 'Dhaka Grill',
                'slug' => 'dhaka-grill',
                'category' => 'Bengali',
                'cuisine' => 'Biryani, Kebabs, Grill',
                'min_delivery_time' => 20,
                'max_delivery_time' => 30,
                'rating' => 4.8,
                'delivery_fee' => 0,
                'featured_text' => 'Best for dinner bundles and charcoal-grilled platters.',
            ],
            [
                'user_id' => $merchant->id,
                'name' => 'Spice Lane',
                'slug' => 'spice-lane',
                'category' => 'Fast Food',
                'cuisine' => 'Street Food, Wraps, Rice Bowls',
                'min_delivery_time' => 18,
                'max_delivery_time' => 25,
                'rating' => 4.7,
                'delivery_fee' => 49,
                'featured_text' => 'Popular for quick lunch drops and office meal boxes.',
            ],
            [
                'user_id' => $merchant->id,
                'name' => 'Green Bowl',
                'slug' => 'green-bowl',
                'category' => 'Healthy Bowls',
                'cuisine' => 'Salads, Smoothies, Protein Plates',
                'min_delivery_time' => 25,
                'max_delivery_time' => 35,
                'rating' => 4.9,
                'delivery_fee' => 29,
                'featured_text' => 'Healthy picks for late-night checkout and repeat orders.',
            ],
        ])->map(fn (array $restaurant) => Restaurant::query()->updateOrCreate(
            ['slug' => $restaurant['slug']],
            $restaurant,
        ));

        $menuItems = collect([
            [
                'restaurant_id' => $restaurants[0]->id,
                'name' => 'Smoky Beef Khichuri',
                'slug' => 'dhaka-grill-smoky-beef-khichuri',
                'category' => 'Rice Bowls',
                'description' => 'Slow-cooked beef with fragrant khichuri and achar.',
                'image_path' => $this->seedImagePath('smoky-beef-khichuri.svg'),
                'price' => 450,
                'is_available' => true,
            ],
            [
                'restaurant_id' => $restaurants[0]->id,
                'name' => 'Lemon Mint Cooler',
                'slug' => 'dhaka-grill-lemon-mint-cooler',
                'category' => 'Drinks',
                'description' => 'Fresh lemon, mint, and soda for hot dinner runs.',
                'image_path' => $this->seedImagePath('lemon-mint-cooler.svg'),
                'price' => 90,
                'is_available' => true,
            ],
            [
                'restaurant_id' => $restaurants[1]->id,
                'name' => 'Street Wrap Combo',
                'slug' => 'spice-lane-street-wrap-combo',
                'category' => 'Wraps',
                'description' => 'Double chicken wrap with masala fries and dip.',
                'image_path' => $this->seedImagePath('street-wrap-combo.svg'),
                'price' => 320,
                'is_available' => true,
            ],
            [
                'restaurant_id' => $restaurants[1]->id,
                'name' => 'Loaded Rice Bowl',
                'slug' => 'spice-lane-loaded-rice-bowl',
                'category' => 'Rice Bowls',
                'description' => 'Spicy rice bowl stacked with chicken, egg, and sauce.',
                'image_path' => $this->seedImagePath('loaded-rice-bowl.svg'),
                'price' => 290,
                'is_available' => true,
            ],
            [
                'restaurant_id' => $restaurants[2]->id,
                'name' => 'Chicken Protein Bowl',
                'slug' => 'green-bowl-chicken-protein-bowl',
                'category' => 'Protein Plates',
                'description' => 'Grilled chicken, brown rice, greens, and yogurt sauce.',
                'image_path' => $this->seedImagePath('chicken-protein-bowl.svg'),
                'price' => 420,
                'is_available' => true,
            ],
            [
                'restaurant_id' => $restaurants[2]->id,
                'name' => 'Yogurt Parfait',
                'slug' => 'green-bowl-yogurt-parfait',
                'category' => 'Desserts',
                'description' => 'Greek yogurt, fruit compote, and toasted granola.',
                'image_path' => $this->seedImagePath('yogurt-parfait.svg'),
                'price' => 150,
                'is_available' => true,
            ],
        ])->mapWithKeys(fn (array $menuItem) => [
            $menuItem['slug'] => MenuItem::query()->updateOrCreate(
            ['slug' => $menuItem['slug']],
            $menuItem,
            ),
        ]);

        $existingOrderIds = Order::query()
            ->whereIn('user_id', [$customer->id, $returningCustomer->id])
            ->pluck('id');

        if ($existingOrderIds->isNotEmpty()) {
            OrderItem::query()->whereIn('order_id', $existingOrderIds)->delete();
            Order::query()->whereIn('id', $existingOrderIds)->delete();
        }

        $cartOrder = Order::query()->create([
            'user_id' => $customer->id,
            'courier_id' => null,
            'restaurant_id' => $restaurants[0]->id,
            'status' => 'cart',
            'subtotal' => 630,
            'delivery_fee' => 0,
            'service_fee' => 25,
            'total' => 655,
            'payment_method' => 'bKash ending in 1024',
            'idempotency_key' => '11111111-1111-1111-1111-111111111111',
            'delivery_address' => 'Home, Block C, Dhanmondi, Dhaka',
            'delivery_latitude' => 23.7464660,
            'delivery_longitude' => 90.3760150,
            'driver_notes' => 'Call on arrival, leave with guard if unavailable.',
            'placed_at' => null,
        ]);
        $this->syncOrderItems($cartOrder, [
            [
                'menu_item_id' => $menuItems['dhaka-grill-smoky-beef-khichuri']->id,
                'name' => 'Smoky Beef Khichuri',
                'quantity' => 1,
                'unit_price' => 450,
                'line_total' => 450,
            ],
            [
                'menu_item_id' => $menuItems['dhaka-grill-lemon-mint-cooler']->id,
                'name' => 'Lemon Mint Cooler',
                'quantity' => 2,
                'unit_price' => 90,
                'line_total' => 180,
            ],
        ]);

        $preparingOrder = Order::query()->create([
            'user_id' => $customer->id,
            'courier_id' => null,
            'restaurant_id' => $restaurants[0]->id,
            'status' => 'preparing',
            'subtotal' => 540,
            'delivery_fee' => 0,
            'service_fee' => 25,
            'total' => 565,
            'payment_method' => 'bKash ending in 1024',
            'idempotency_key' => '22222222-2222-2222-2222-222222222222',
            'delivery_address' => 'Home, Block C, Dhanmondi, Dhaka',
            'delivery_latitude' => 23.7464660,
            'delivery_longitude' => 90.3760150,
            'driver_notes' => 'Call on arrival, leave with guard if unavailable.',
            'placed_at' => now()->subMinutes(12),
        ]);
        $this->syncOrderItems($preparingOrder, [
            [
                'menu_item_id' => $menuItems['dhaka-grill-smoky-beef-khichuri']->id,
                'name' => 'Smoky Beef Khichuri',
                'quantity' => 1,
                'unit_price' => 450,
                'line_total' => 450,
            ],
            [
                'menu_item_id' => $menuItems['dhaka-grill-lemon-mint-cooler']->id,
                'name' => 'Lemon Mint Cooler',
                'quantity' => 1,
                'unit_price' => 90,
                'line_total' => 90,
            ],
        ]);

        $claimableOrder = Order::query()->create([
            'user_id' => $returningCustomer->id,
            'courier_id' => null,
            'restaurant_id' => $restaurants[1]->id,
            'status' => 'on_the_way',
            'subtotal' => 610,
            'delivery_fee' => 49,
            'service_fee' => 25,
            'total' => 684,
            'payment_method' => 'Cash on delivery',
            'idempotency_key' => '33333333-3333-3333-3333-333333333333',
            'delivery_address' => 'Office Tower, Banani 11, Dhaka',
            'delivery_latitude' => null,
            'delivery_longitude' => null,
            'driver_notes' => 'Reception drop-off for the courier to claim.',
            'placed_at' => now()->subMinutes(18),
        ]);
        $this->syncOrderItems($claimableOrder, [
            [
                'menu_item_id' => $menuItems['spice-lane-street-wrap-combo']->id,
                'name' => 'Street Wrap Combo',
                'quantity' => 1,
                'unit_price' => 320,
                'line_total' => 320,
            ],
            [
                'menu_item_id' => $menuItems['spice-lane-loaded-rice-bowl']->id,
                'name' => 'Loaded Rice Bowl',
                'quantity' => 1,
                'unit_price' => 290,
                'line_total' => 290,
            ],
        ]);

        $claimedOrder = Order::query()->create([
            'user_id' => $customer->id,
            'courier_id' => $courier->id,
            'restaurant_id' => $restaurants[2]->id,
            'status' => 'on_the_way',
            'subtotal' => 570,
            'delivery_fee' => 29,
            'service_fee' => 25,
            'total' => 624,
            'payment_method' => 'Cash on delivery',
            'idempotency_key' => '44444444-4444-4444-4444-444444444444',
            'delivery_address' => 'House 9, Dhanmondi 27, Dhaka',
            'delivery_latitude' => 23.7529151,
            'delivery_longitude' => 90.3765482,
            'driver_notes' => 'Buzz the intercom before heading upstairs.',
            'placed_at' => now()->subMinutes(9),
        ]);
        $this->syncOrderItems($claimedOrder, [
            [
                'menu_item_id' => $menuItems['green-bowl-chicken-protein-bowl']->id,
                'name' => 'Chicken Protein Bowl',
                'quantity' => 1,
                'unit_price' => 420,
                'line_total' => 420,
            ],
            [
                'menu_item_id' => $menuItems['green-bowl-yogurt-parfait']->id,
                'name' => 'Yogurt Parfait',
                'quantity' => 1,
                'unit_price' => 150,
                'line_total' => 150,
            ],
        ]);

        $deliveredOrder = Order::query()->create([
            'user_id' => $returningCustomer->id,
            'courier_id' => $courier->id,
            'restaurant_id' => $restaurants[2]->id,
            'status' => 'delivered',
            'subtotal' => 570,
            'delivery_fee' => 29,
            'service_fee' => 20,
            'total' => 619,
            'payment_method' => 'Cash on delivery',
            'idempotency_key' => '55555555-5555-5555-5555-555555555555',
            'delivery_address' => 'Office, Banani 11, Dhaka',
            'delivery_latitude' => 23.7935240,
            'delivery_longitude' => 90.4063230,
            'driver_notes' => 'Hand over at reception desk.',
            'placed_at' => now()->subDay()->setTime(20, 45),
        ]);
        $this->syncOrderItems($deliveredOrder, [
            [
                'menu_item_id' => $menuItems['green-bowl-chicken-protein-bowl']->id,
                'name' => 'Chicken Protein Bowl',
                'quantity' => 1,
                'unit_price' => 420,
                'line_total' => 420,
            ],
            [
                'menu_item_id' => $menuItems['green-bowl-yogurt-parfait']->id,
                'name' => 'Yogurt Parfait',
                'quantity' => 1,
                'unit_price' => 150,
                'line_total' => 150,
            ],
        ]);
    }

    /**
     * @param  array<int, array<string, int|string>>  $items
     */
    private function syncOrderItems(Order $order, array $items): void
    {
        $order->orderItems()->delete();

        $order->orderItems()->createMany($items);
    }

    private function seedImagePath(string $fileName): string
    {
        return 'seed://demo-foods/'.$fileName;
    }
}
