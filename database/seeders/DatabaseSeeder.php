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
                'description' => 'Slow-braised beef, ghee khichuri rice, caramelized onion, cucumber salad, and house mango achar.',
                'image_path' => $this->unsplashImage('1544025162-d76694265947'),
                'price' => 450,
                'is_available' => true,
            ],
            [
                'restaurant_id' => $restaurants[0]->id,
                'name' => 'Lemon Mint Cooler',
                'slug' => 'dhaka-grill-lemon-mint-cooler',
                'category' => 'Drinks',
                'description' => 'Fresh lemon juice, garden mint, a touch of cane syrup, and sparkling soda over pebble ice.',
                'image_path' => $this->unsplashImage('1497534446932-c925b458314e'),
                'price' => 90,
                'is_available' => true,
            ],
            [
                'restaurant_id' => $restaurants[0]->id,
                'name' => 'Old Dhaka Chicken Biryani',
                'slug' => 'dhaka-grill-old-dhaka-chicken-biryani',
                'category' => 'Signature Rice',
                'description' => 'Aromatic basmati biryani with saffron chicken, potato, boiled egg, and cooling cucumber raita.',
                'image_path' => $this->unsplashImage('1515003197210-e0cd71810b5f'),
                'price' => 520,
                'is_available' => true,
            ],
            [
                'restaurant_id' => $restaurants[0]->id,
                'name' => 'Charcoal Seekh Kebab Platter',
                'slug' => 'dhaka-grill-charcoal-seekh-kebab-platter',
                'category' => 'Kebabs',
                'description' => 'Flame-grilled beef seekh kebabs served with roomali roti, pickled onions, and green chutney.',
                'image_path' => $this->unsplashImage('1540189549336-e6e99c3679fe'),
                'price' => 490,
                'is_available' => true,
            ],
            [
                'restaurant_id' => $restaurants[1]->id,
                'name' => 'Street Wrap Combo',
                'slug' => 'spice-lane-street-wrap-combo',
                'category' => 'Wraps',
                'description' => 'Two flame-grilled chicken shawarma wraps with masala fries, garlic toum, and pickled slaw.',
                'image_path' => $this->unsplashImage('1504674900247-0877df9cc836'),
                'price' => 320,
                'is_available' => true,
            ],
            [
                'restaurant_id' => $restaurants[1]->id,
                'name' => 'Loaded Rice Bowl',
                'slug' => 'spice-lane-loaded-rice-bowl',
                'category' => 'Rice Bowls',
                'description' => 'Turmeric rice topped with peri-peri chicken, soft egg, butter corn, and smoky comeback sauce.',
                'image_path' => $this->unsplashImage('1567620905732-2d1ec7ab7445'),
                'price' => 290,
                'is_available' => true,
            ],
            [
                'restaurant_id' => $restaurants[1]->id,
                'name' => 'Firecracker Chicken Sliders',
                'slug' => 'spice-lane-firecracker-chicken-sliders',
                'category' => 'Burgers',
                'description' => 'Three crispy chicken sliders with chili jam, house slaw, and pepper jack mayo.',
                'image_path' => $this->unsplashImage('1568901346375-23c9450c58cd'),
                'price' => 360,
                'is_available' => true,
            ],
            [
                'restaurant_id' => $restaurants[1]->id,
                'name' => 'Seoul Crispy Chicken Box',
                'slug' => 'spice-lane-seoul-crispy-chicken-box',
                'category' => 'Fried Chicken',
                'description' => 'Korean-style crispy chicken bites with seasoned fries, sesame slaw, and gochujang dip.',
                'image_path' => $this->unsplashImage('1547592180-85f173990554'),
                'price' => 410,
                'is_available' => true,
            ],
            [
                'restaurant_id' => $restaurants[2]->id,
                'name' => 'Chicken Protein Bowl',
                'slug' => 'green-bowl-chicken-protein-bowl',
                'category' => 'Protein Plates',
                'description' => 'Herb-grilled chicken breast with brown rice, roasted pumpkin, edamame, cucumber, and miso yogurt dressing.',
                'image_path' => $this->unsplashImage('1512621776951-a57141f2eefd'),
                'price' => 420,
                'is_available' => true,
            ],
            [
                'restaurant_id' => $restaurants[2]->id,
                'name' => 'Yogurt Parfait',
                'slug' => 'green-bowl-yogurt-parfait',
                'category' => 'Desserts',
                'description' => 'Greek yogurt layered with berry compote, toasted granola, chia seeds, and wildflower honey.',
                'image_path' => $this->unsplashImage('1482049016688-2d3e1b311543'),
                'price' => 150,
                'is_available' => true,
            ],
            [
                'restaurant_id' => $restaurants[2]->id,
                'name' => 'Citrus Salmon Power Bowl',
                'slug' => 'green-bowl-citrus-salmon-power-bowl',
                'category' => 'Protein Plates',
                'description' => 'Seared salmon with quinoa, avocado, mango salsa, kale, and citrus sesame vinaigrette.',
                'image_path' => $this->unsplashImage('1546069901-ba9599a7e63c'),
                'price' => 520,
                'is_available' => true,
            ],
            [
                'restaurant_id' => $restaurants[2]->id,
                'name' => 'Matcha Chia Overnight Oats',
                'slug' => 'green-bowl-matcha-chia-overnight-oats',
                'category' => 'Breakfast',
                'description' => 'Overnight oats with matcha chia pudding, kiwi, banana, coconut flakes, and almond crunch.',
                'image_path' => $this->unsplashImage('1482049016688-2d3e1b311543'),
                'price' => 440,
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
            'subtotal' => 1030,
            'delivery_fee' => 0,
            'service_fee' => 35,
            'total' => 1065,
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
                'menu_item_id' => $menuItems['dhaka-grill-charcoal-seekh-kebab-platter']->id,
                'name' => 'Charcoal Seekh Kebab Platter',
                'quantity' => 1,
                'unit_price' => 490,
                'line_total' => 490,
            ],
            [
                'menu_item_id' => $menuItems['dhaka-grill-lemon-mint-cooler']->id,
                'name' => 'Lemon Mint Cooler',
                'quantity' => 1,
                'unit_price' => 90,
                'line_total' => 90,
            ],
        ]);

        $preparingOrder = Order::query()->create([
            'user_id' => $customer->id,
            'courier_id' => null,
            'restaurant_id' => $restaurants[0]->id,
            'status' => 'preparing',
            'subtotal' => 610,
            'delivery_fee' => 0,
            'service_fee' => 25,
            'total' => 635,
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
                'menu_item_id' => $menuItems['dhaka-grill-old-dhaka-chicken-biryani']->id,
                'name' => 'Old Dhaka Chicken Biryani',
                'quantity' => 1,
                'unit_price' => 520,
                'line_total' => 520,
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
            'subtotal' => 1020,
            'delivery_fee' => 49,
            'service_fee' => 25,
            'total' => 1094,
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
            [
                'menu_item_id' => $menuItems['spice-lane-seoul-crispy-chicken-box']->id,
                'name' => 'Seoul Crispy Chicken Box',
                'quantity' => 1,
                'unit_price' => 410,
                'line_total' => 410,
            ],
        ]);

        $claimedOrder = Order::query()->create([
            'user_id' => $customer->id,
            'courier_id' => $courier->id,
            'restaurant_id' => $restaurants[2]->id,
            'status' => 'on_the_way',
            'subtotal' => 860,
            'delivery_fee' => 29,
            'service_fee' => 25,
            'total' => 914,
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
                'menu_item_id' => $menuItems['green-bowl-matcha-chia-overnight-oats']->id,
                'name' => 'Matcha Chia Overnight Oats',
                'quantity' => 1,
                'unit_price' => 440,
                'line_total' => 440,
            ],
        ]);

        $deliveredOrder = Order::query()->create([
            'user_id' => $returningCustomer->id,
            'courier_id' => $courier->id,
            'restaurant_id' => $restaurants[2]->id,
            'status' => 'delivered',
            'subtotal' => 670,
            'delivery_fee' => 29,
            'service_fee' => 20,
            'total' => 719,
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
                'menu_item_id' => $menuItems['green-bowl-citrus-salmon-power-bowl']->id,
                'name' => 'Citrus Salmon Power Bowl',
                'quantity' => 1,
                'unit_price' => 520,
                'line_total' => 520,
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

    private function unsplashImage(string $photoId): string
    {
        return "https://images.unsplash.com/photo-{$photoId}?auto=format&fit=crop&w=1200&q=80";
    }
}
