<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LamiKaMenuSeeder extends Seeder
{
    private const RESTAURANT_ID = 5;
    private const RESTAURANT_SLUG = 'lami-ka';

    public function run(): void
    {
        $items = $this->menuItems();

        foreach ($items as $item) {
            $slug = $this->uniqueSlug($item['name']);

            MenuItem::firstOrCreate(
                ['slug' => $slug],
                [
                    'restaurant_id' => self::RESTAURANT_ID,
                    'name' => $item['name'],
                    'slug' => $slug,
                    'category' => $item['category'],
                    'description' => $item['description'],
                    'image_path' => $item['image'],
                    'price' => $item['price'],
                    'promo_price' => $item['promo_price'] ?? null,
                    'is_available' => true,
                    'pax_min' => $item['pax_min'] ?? null,
                    'pax_max' => $item['pax_max'] ?? null,
                ],
            );
        }
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug(self::RESTAURANT_SLUG.' '.$name);
        $slug = $base;
        $counter = 2;

        while (MenuItem::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /** @return array<int, array<string, mixed>> */
    private function menuItems(): array
    {
        return [
            // ── Rice Bowls ────────────────────────────────────────────────────
            [
                'category' => 'Rice Bowls',
                'name' => 'Garlic Butter Shrimp Rice Bowl',
                'description' => 'Juicy garlic-butter shrimp served over steamed jasmine rice, topped with pickled cucumber, toasted sesame, and a drizzle of sriracha mayo.',
                'price' => 145,
                'image' => 'https://images.unsplash.com/photo-1603360946226-e6de22c8b1f1?w=800&h=600&fit=crop&q=80',
            ],
            [
                'category' => 'Rice Bowls',
                'name' => 'Teriyaki Chicken Rice Bowl',
                'description' => 'Tender grilled chicken glazed in house-made teriyaki sauce, served on a bed of warm jasmine rice with steamed broccoli and edamame.',
                'price' => 130,
                'image' => 'https://images.unsplash.com/photo-1569050467447-ce54b3bbc37d?w=800&h=600&fit=crop&q=80',
            ],
            [
                'category' => 'Rice Bowls',
                'name' => 'Korean Beef Bibimbap Bowl',
                'description' => 'Seasoned ground beef, julienned vegetables, fried egg, and gochujang sauce over warm rice. Mix it all together for the perfect bite.',
                'price' => 155,
                'promo_price' => 135,
                'image' => 'https://images.unsplash.com/photo-1546549130-be468161d673?w=800&h=600&fit=crop&q=80',
            ],
            [
                'category' => 'Rice Bowls',
                'name' => 'Adobo Flakes Rice Bowl',
                'description' => 'Crispy pork adobo flakes on a mound of garlic fried rice, topped with a soft-boiled egg, quick-pickled onions, and a side of vinegar dip.',
                'price' => 110,
                'image' => 'https://images.unsplash.com/photo-1512058564366-18510be2db19?w=800&h=600&fit=crop&q=80',
            ],

            // ── Grill Platters ────────────────────────────────────────────────
            [
                'category' => 'Grill Platters',
                'name' => 'Mixed Grill Fiesta Platter',
                'description' => 'A feast for two — pork belly liempo, chicken inasal, pork sausages, and corn on the cob, grilled over charcoal and served with atchara and steamed rice.',
                'price' => 380,
                'pax_min' => 2,
                'pax_max' => 3,
                'image' => 'https://images.unsplash.com/photo-1544025162-d76538b2a756?w=800&h=600&fit=crop&q=80',
            ],
            [
                'category' => 'Grill Platters',
                'name' => 'Charcoal Chicken Inasal Platter',
                'description' => 'Whole chicken marinated overnight in calamansi, lemongrass, and annatto oil, slow-grilled over coconut shell charcoal. Comes with garlic rice, liver sauce, and atchara.',
                'price' => 260,
                'pax_min' => 1,
                'pax_max' => 2,
                'image' => 'https://images.unsplash.com/photo-1529193591184-b1d58069ecdd?w=800&h=600&fit=crop&q=80',
            ],
            [
                'category' => 'Grill Platters',
                'name' => 'Pork BBQ Skewers Platter (6 pcs)',
                'description' => 'Six sticks of tender pork skewers marinated in a sweet-savory BBQ sauce, grilled to perfection. Served with java rice and banana catsup.',
                'price' => 175,
                'image' => 'https://images.unsplash.com/photo-1558030006-44fb395e8e43?w=800&h=600&fit=crop&q=80',
            ],
            [
                'category' => 'Grill Platters',
                'name' => 'Seafood Grill Platter',
                'description' => 'Fresh pusit (squid), tilapia, and tahong (mussels) grilled with garlic butter and calamansi. Served with steamed rice, sawsawan, and cucumber salad.',
                'price' => 295,
                'pax_min' => 2,
                'pax_max' => 3,
                'image' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=800&h=600&fit=crop&q=80',
            ],

            // ── Kebabs ────────────────────────────────────────────────────────
            [
                'category' => 'Kebabs',
                'name' => 'Beef Seekh Kebab (4 pcs)',
                'description' => 'Minced beef seasoned with aromatic spices, green chili, and fresh herbs, hand-rolled on skewers and grilled. Served with mint chutney and warm pita.',
                'price' => 160,
                'image' => 'https://images.unsplash.com/photo-1599487488170-d11ec9c172f0?w=800&h=600&fit=crop&q=80',
            ],
            [
                'category' => 'Kebabs',
                'name' => 'Chicken Shish Kebab',
                'description' => 'Marinated chicken cubes with bell peppers and onions on skewers, char-grilled and served over garlic rice with a side of tzatziki sauce.',
                'price' => 145,
                'image' => 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=800&h=600&fit=crop&q=80',
            ],
            [
                'category' => 'Kebabs',
                'name' => 'Lamb Adana Kebab',
                'description' => 'Spiced ground lamb mixed with red chili flakes and parsley, pressed onto flat skewers and grilled over charcoal. Served with flatbread and yogurt dip.',
                'price' => 210,
                'image' => 'https://images.unsplash.com/photo-1567620905732-2d1ec7ab7445?w=800&h=600&fit=crop&q=80',
            ],

            // ── Wraps ─────────────────────────────────────────────────────────
            [
                'category' => 'Wraps',
                'name' => 'Grilled Chicken Caesar Wrap',
                'description' => 'Sliced grilled chicken breast, crisp romaine lettuce, parmesan shavings, croutons, and creamy Caesar dressing rolled in a soft flour tortilla.',
                'price' => 120,
                'image' => 'https://images.unsplash.com/photo-1540189549416-79b1f283e76e?w=800&h=600&fit=crop&q=80',
            ],
            [
                'category' => 'Wraps',
                'name' => 'Veggie Falafel Wrap',
                'description' => 'Crispy falafel balls, hummus, tabbouleh, roasted peppers, and cucumber-tomato salsa wrapped in warm whole wheat pita.',
                'price' => 105,
                'image' => 'https://images.unsplash.com/photo-1562802378-063ec186a863?w=800&h=600&fit=crop&q=80',
            ],
            [
                'category' => 'Wraps',
                'name' => 'Spicy Tuna Wrap',
                'description' => 'Seared tuna tossed in spicy mayo, avocado slices, shredded cabbage, pickled red onion, and sriracha, wrapped in a toasted spinach tortilla.',
                'price' => 135,
                'image' => 'https://images.unsplash.com/photo-1562802378-063ec186a863?w=800&h=600&fit=crop&q=80',
            ],
            [
                'category' => 'Wraps',
                'name' => 'Beef Shawarma Wrap',
                'description' => 'Thinly sliced seasoned beef, garlic sauce, pickled turnips, fresh tomato, and parsley — the classic shawarma experience, rolled to go.',
                'price' => 115,
                'image' => 'https://images.unsplash.com/photo-1529006557810-274b9b2fc783?w=800&h=600&fit=crop&q=80',
            ],

            // ── Burgers ───────────────────────────────────────────────────────
            [
                'category' => 'Burgers',
                'name' => 'Classic Smash Burger',
                'description' => 'Two smashed beef patties with American cheese, caramelized onions, pickle chips, mustard, and our house burger sauce on a toasted brioche bun.',
                'price' => 155,
                'image' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=800&h=600&fit=crop&q=80',
            ],
            [
                'category' => 'Burgers',
                'name' => 'Crispy Chicken Burger',
                'description' => 'Southern-style fried chicken thigh fillet with coleslaw, dill pickles, and honey-mustard sauce on a toasted sesame seed bun.',
                'price' => 140,
                'image' => 'https://images.unsplash.com/photo-1551782450-17144efb9c50?w=800&h=600&fit=crop&q=80',
            ],
            [
                'category' => 'Burgers',
                'name' => 'Mushroom Swiss Burger',
                'description' => 'Juicy beef patty piled with sautéed garlic mushrooms, melted Swiss cheese, arugula, and truffle aioli on a pretzel bun.',
                'price' => 175,
                'promo_price' => 155,
                'image' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=800&h=600&fit=crop&q=80',
            ],
            [
                'category' => 'Burgers',
                'name' => 'BBQ Bacon Cheeseburger',
                'description' => 'Thick beef patty topped with crispy bacon, cheddar cheese, smoky BBQ sauce, jalapeño rings, and crispy onion strings.',
                'price' => 185,
                'image' => 'https://images.unsplash.com/photo-1586190848641-5f92c4f5ee59?w=800&h=600&fit=crop&q=80',
            ],
            [
                'category' => 'Burgers',
                'name' => 'Plant-Based Burger',
                'description' => 'A house-made black bean and quinoa patty with avocado cream, sun-dried tomato, cucumber, and green leaf lettuce on a whole grain bun.',
                'price' => 150,
                'image' => 'https://images.unsplash.com/photo-1551782450-17144efb9c50?w=800&h=600&fit=crop&q=80',
            ],

            // ── Fried Chicken ─────────────────────────────────────────────────
            [
                'category' => 'Fried Chicken',
                'name' => 'Southern Fried Chicken (3 pcs)',
                'description' => 'Three pieces of bone-in chicken (leg, thigh, breast) coated in seasoned buttermilk batter and deep-fried to golden perfection. Served with creamy coleslaw and biscuit.',
                'price' => 160,
                'image' => 'https://images.unsplash.com/photo-1627308595229-7830a5c91f9f?w=800&h=600&fit=crop&q=80',
            ],
            [
                'category' => 'Fried Chicken',
                'name' => 'Korean Soy Garlic Chicken (6 pcs)',
                'description' => 'Crispy double-fried chicken wings glazed in a sticky sweet-savory soy garlic sauce, garnished with sesame seeds and spring onions.',
                'price' => 150,
                'image' => 'https://images.unsplash.com/photo-1627308595229-7830a5c91f9f?w=800&h=600&fit=crop&q=80',
            ],
            [
                'category' => 'Fried Chicken',
                'name' => 'Spicy Nashville Tenders (4 pcs)',
                'description' => 'Chicken tenders dredged in spiced flour, fried crispy, and brushed with a fiery cayenne honey butter glaze. Served with pickles and white bread.',
                'price' => 145,
                'image' => 'https://images.unsplash.com/photo-1598515214211-89d3c73ae83b?w=800&h=600&fit=crop&q=80',
            ],
            [
                'category' => 'Fried Chicken',
                'name' => 'Fried Chicken Rice Meal',
                'description' => 'One piece crispy fried chicken served with garlic rice, a cup of gravy, and fresh ensalada. Simple, satisfying, classic.',
                'price' => 105,
                'image' => 'https://images.unsplash.com/photo-1562967914-608f82629710?w=800&h=600&fit=crop&q=80',
            ],

            // ── Pizza ─────────────────────────────────────────────────────────
            [
                'category' => 'Pizza',
                'name' => 'Margherita Classic',
                'description' => 'San Marzano tomato sauce, fresh buffalo mozzarella, and hand-torn basil leaves on a thin sourdough crust, finished with extra virgin olive oil.',
                'price' => 220,
                'image' => 'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=800&h=600&fit=crop&q=80',
            ],
            [
                'category' => 'Pizza',
                'name' => 'Pepperoni Feast',
                'description' => 'Layers of mozzarella and generous overlapping pepperoni on classic tomato sauce. Edges brushed with garlic butter for that golden finish.',
                'price' => 250,
                'image' => 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=800&h=600&fit=crop&q=80',
            ],
            [
                'category' => 'Pizza',
                'name' => 'BBQ Pulled Pork Pizza',
                'description' => 'Smoky BBQ sauce base with slow-cooked pulled pork, red onion, mozzarella, and a drizzle of honey, topped with fresh cilantro.',
                'price' => 270,
                'image' => 'https://images.unsplash.com/photo-1574071318508-1cdbab80d002?w=800&h=600&fit=crop&q=80',
            ],
            [
                'category' => 'Pizza',
                'name' => 'Four Cheese Pizza',
                'description' => 'Mozzarella, cheddar, parmesan, and gorgonzola on a white garlic cream sauce base, with a sprinkle of fresh thyme and cracked black pepper.',
                'price' => 260,
                'promo_price' => 235,
                'image' => 'https://images.unsplash.com/photo-1520201163981-8cc95007dd2a?w=800&h=600&fit=crop&q=80',
            ],
            [
                'category' => 'Pizza',
                'name' => 'Hawaiian Delight',
                'description' => 'Classic tomato sauce, mozzarella, smoky ham, and juicy pineapple chunks. Sweet, salty, and utterly irresistible.',
                'price' => 240,
                'image' => 'https://images.unsplash.com/photo-1565299585323-38d6b0865b47?w=800&h=600&fit=crop&q=80',
            ],

            // ── Pasta ─────────────────────────────────────────────────────────
            [
                'category' => 'Pasta',
                'name' => 'Spaghetti Bolognese',
                'description' => 'Slow-simmered beef and pork ragù tossed with al dente spaghetti, finished with parmesan and fresh basil. Comfort in a bowl.',
                'price' => 165,
                'image' => 'https://images.unsplash.com/photo-1555949258-eb67b1ef0ceb?w=800&h=600&fit=crop&q=80',
            ],
            [
                'category' => 'Pasta',
                'name' => 'Creamy Chicken Carbonara',
                'description' => 'Fettuccine tossed in a rich egg-and-cream sauce with crispy pancetta, sautéed chicken strips, parmesan, and cracked black pepper.',
                'price' => 175,
                'image' => 'https://images.unsplash.com/photo-1621996346565-e3dbc646d9a9?w=800&h=600&fit=crop&q=80',
            ],
            [
                'category' => 'Pasta',
                'name' => 'Seafood Aglio e Olio',
                'description' => 'Linguine with shrimp, squid rings, and mussels sautéed in olive oil, toasted garlic, chili flakes, and fresh parsley. Simple yet bold.',
                'price' => 195,
                'image' => 'https://images.unsplash.com/photo-1622973536968-3ead57888a65?w=800&h=600&fit=crop&q=80',
            ],
            [
                'category' => 'Pasta',
                'name' => 'Penne Arrabbiata',
                'description' => 'Penne pasta in a fiery tomato sauce with garlic, fresh basil, and generous red chili flakes. Topped with shaved parmesan and drizzled with olive oil.',
                'price' => 145,
                'image' => 'https://images.unsplash.com/photo-1598866594230-a7c12756260f?w=800&h=600&fit=crop&q=80',
            ],

            // ── Sandwiches ────────────────────────────────────────────────────
            [
                'category' => 'Sandwiches',
                'name' => 'Club Sandwich',
                'description' => 'Triple-decker with turkey, crispy bacon, fried egg, Swiss cheese, lettuce, tomato, and mayo on thick-cut toasted white bread. Served with fries.',
                'price' => 145,
                'image' => 'https://images.unsplash.com/photo-1550547660-c3c1e0d0e75c?w=800&h=600&fit=crop&q=80',
            ],
            [
                'category' => 'Sandwiches',
                'name' => 'Philly Cheesesteak Sub',
                'description' => 'Thinly sliced ribeye steak with caramelized onions, sautéed bell peppers, and melted provolone in a toasted hoagie roll.',
                'price' => 165,
                'image' => 'https://images.unsplash.com/photo-1539252554880-c0c4a1a97a86?w=800&h=600&fit=crop&q=80',
            ],
            [
                'category' => 'Sandwiches',
                'name' => 'BLT with Avocado',
                'description' => 'Crispy maple bacon, ripe tomato, butter lettuce, and smashed avocado on toasted sourdough with a swipe of chipotle aioli.',
                'price' => 130,
                'image' => 'https://images.unsplash.com/photo-1558985212-f21e32ee6721?w=800&h=600&fit=crop&q=80',
            ],

            // ── Drinks ────────────────────────────────────────────────────────
            [
                'category' => 'Drinks',
                'name' => 'Fresh Calamansi Juice',
                'description' => 'Freshly squeezed calamansi blended with a hint of honey and sparkling water. Refreshingly tangy and perfectly Filipino.',
                'price' => 55,
                'image' => 'https://images.unsplash.com/photo-1499028344343-cd173ffc68a9?w=800&h=600&fit=crop&q=80',
            ],
            [
                'category' => 'Drinks',
                'name' => 'Mango Shake',
                'description' => 'Fresh Philippine carabao mangoes blended smooth with cold milk and a scoop of vanilla ice cream. Thick, sweet, and tropical.',
                'price' => 75,
                'image' => 'https://images.unsplash.com/photo-1595981234058-a9302fb97229?w=800&h=600&fit=crop&q=80',
            ],
            [
                'category' => 'Drinks',
                'name' => 'Iced Brown Sugar Milk Tea',
                'description' => 'Brewed black tea with caramelized brown sugar syrup, fresh milk, and tapioca pearls over ice. Creamy, sweet, and satisfying.',
                'price' => 90,
                'image' => 'https://images.unsplash.com/photo-1544145945-f90425340c7e?w=800&h=600&fit=crop&q=80',
            ],
            [
                'category' => 'Drinks',
                'name' => 'Sparkling Lemonade',
                'description' => 'House-made lemon syrup topped with chilled sparkling water, mint leaves, and sliced lemon. Light and effervescent.',
                'price' => 60,
                'image' => 'https://images.unsplash.com/photo-1523677011781-c91d1bbe2f9e?w=800&h=600&fit=crop&q=80',
            ],
            [
                'category' => 'Drinks',
                'name' => 'Coconut Water',
                'description' => 'Pure young coconut water served chilled with coconut meat on the side. Natural electrolytes, zero sugar added.',
                'price' => 65,
                'image' => 'https://images.unsplash.com/photo-1550158769-6cbce68a7cef?w=800&h=600&fit=crop&q=80',
            ],

            // ── Protein Plates ────────────────────────────────────────────────
            [
                'category' => 'Protein Plates',
                'name' => 'Grilled Chicken Protein Bowl',
                'description' => 'Grilled chicken breast, quinoa, roasted sweet potato, steamed broccoli, cherry tomatoes, and a tahini drizzle. 40g protein per serving.',
                'price' => 175,
                'image' => 'https://images.unsplash.com/photo-1546069901-522a0f44b4c5?w=800&h=600&fit=crop&q=80',
            ],
            [
                'category' => 'Protein Plates',
                'name' => 'Beef and Egg Power Plate',
                'description' => 'Lean ground beef with two fried eggs, brown rice, sautéed spinach, and sliced avocado. A balanced macro-friendly plate for your training days.',
                'price' => 160,
                'image' => 'https://images.unsplash.com/photo-1546069901-522a0f44b4c5?w=800&h=600&fit=crop&q=80',
            ],
            [
                'category' => 'Protein Plates',
                'name' => 'Tuna Steak Plate',
                'description' => 'Pan-seared yellowfin tuna steak with lemon herb butter, cauliflower rice, roasted asparagus, and a balsamic glaze.',
                'price' => 195,
                'image' => 'https://images.unsplash.com/photo-1476124369491-e7addf5db371?w=800&h=600&fit=crop&q=80',
            ],

            // ── Breakfast ─────────────────────────────────────────────────────
            [
                'category' => 'Breakfast',
                'name' => 'Full Filipino Breakfast',
                'description' => 'Tapsilog (beef tapa, garlic fried rice, and fried egg), with fresh tomato and cucumber, and a glass of hot chocolate. The classic wake-up plate.',
                'price' => 130,
                'image' => 'https://images.unsplash.com/photo-1567620905732-2d1ec7ab7445?w=800&h=600&fit=crop&q=80',
            ],
            [
                'category' => 'Breakfast',
                'name' => 'Pancake Stack',
                'description' => 'Fluffy buttermilk pancakes (3 pcs) with whipped butter, fresh seasonal berries, and warm maple syrup on the side.',
                'price' => 110,
                'image' => 'https://images.unsplash.com/photo-1506084868230-bb9d95c24759?w=800&h=600&fit=crop&q=80',
            ],
            [
                'category' => 'Breakfast',
                'name' => 'Eggs Benedict',
                'description' => 'Two poached eggs on toasted English muffins with Canadian bacon and house-made hollandaise sauce. Served with roasted potatoes.',
                'price' => 145,
                'image' => 'https://images.unsplash.com/photo-1525351484163-7529414f2bd8?w=800&h=600&fit=crop&q=80',
            ],
            [
                'category' => 'Breakfast',
                'name' => 'Avocado Toast',
                'description' => 'Thick sourdough toasted and spread with smashed avocado, topped with a jammy soft-boiled egg, chili flakes, microgreens, and maldon salt.',
                'price' => 120,
                'image' => 'https://images.unsplash.com/photo-1596797038530-2c107229654b?w=800&h=600&fit=crop&q=80',
            ],

            // ── Desserts ──────────────────────────────────────────────────────
            [
                'category' => 'Desserts',
                'name' => 'Leche Flan',
                'description' => 'Classic Filipino caramel custard made with egg yolks, condensed milk, and evaporated milk, steamed to silky smooth perfection with a deep amber caramel top.',
                'price' => 75,
                'image' => 'https://images.unsplash.com/photo-1563805042-7684c019e1cb?w=800&h=600&fit=crop&q=80',
            ],
            [
                'category' => 'Desserts',
                'name' => 'Chocolate Lava Cake',
                'description' => 'Warm dark chocolate cake with a molten center, served with a scoop of vanilla ice cream and a dusting of powdered sugar.',
                'price' => 120,
                'image' => 'https://images.unsplash.com/photo-1551024709-8f23befc548e?w=800&h=600&fit=crop&q=80',
            ],
            [
                'category' => 'Desserts',
                'name' => 'Halo-Halo Supreme',
                'description' => 'Crushed ice with sweet beans, nata de coco, macapuno, ube halaya, leche flan, pinipig, and evaporated milk, topped with a generous scoop of ube ice cream.',
                'price' => 95,
                'promo_price' => 80,
                'image' => 'https://images.unsplash.com/photo-1488477181947-9e6a3fb45396?w=800&h=600&fit=crop&q=80',
            ],
            [
                'category' => 'Desserts',
                'name' => 'Mango Panna Cotta',
                'description' => 'Silky vanilla panna cotta layered with fresh mango coulis and topped with diced Philippine mangoes and toasted coconut flakes.',
                'price' => 90,
                'image' => 'https://images.unsplash.com/photo-1618897996318-5a901fa696f9?w=800&h=600&fit=crop&q=80',
            ],
        ];
    }
}
