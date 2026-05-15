<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class LamiKaMenuImageFixSeeder extends Seeder
{
    private const RESTAURANT_ID = 5;
    private const BASE = 'https://images.unsplash.com/photo-';
    private const SUFFIX = '?w=800&h=600&fit=crop&q=80';

    public function run(): void
    {
        $fixes = [
            // ── Breakfast (confirmed broken) ─────────────────────────────────
            'Full Filipino Breakfast' => '1567620905732-2d1ec7ab7445', // rice & meat plate
            'Eggs Benedict'           => '1525351484163-7529414f2bd8', // brunch plate
            'Avocado Toast'           => '1596797038530-2c107229654b', // avocado toast

            // ── Rice Bowls (uncertain IDs replaced) ──────────────────────────
            'Garlic Butter Shrimp Rice Bowl' => '1603360946226-e6de22c8b1f1', // rice bowl
            'Korean Beef Bibimbap Bowl'      => '1546549130-be468161d673',     // bibimbap
            'Adobo Flakes Rice Bowl'         => '1512058564366-18510be2db19',  // rice/grain

            // ── Wraps (replaced uncertain ones) ──────────────────────────────
            'Grilled Chicken Caesar Wrap' => '1540189549416-79b1f283e76e', // wrap plate
            'Veggie Falafel Wrap'         => '1562802378-063ec186a863',    // falafel bowl

            // ── Burgers (replaced uncertain ones) ────────────────────────────
            'Mushroom Swiss Burger' => '1568901346375-23c9450c58cd', // classic burger
            'BBQ Bacon Cheeseburger' => '1586190848641-5f92c4f5ee59', // loaded burger
            'Plant-Based Burger'     => '1551782450-17144efb9c50',    // burger

            // ── Fried Chicken (replaced uncertain ones) ───────────────────────
            'Korean Soy Garlic Chicken (6 pcs)' => '1627308595229-7830a5c91f9f', // fried chicken
            'Spicy Nashville Tenders (4 pcs)'   => '1598515214211-89d3c73ae83b', // tenders
            'Fried Chicken Rice Meal'            => '1562967914-608f82629710',    // chicken meal

            // ── Protein Plates (replaced uncertain one) ───────────────────────
            'Beef and Egg Power Plate' => '1546069901-522a0f44b4c5', // protein bowl
        ];

        $base   = self::BASE;
        $suffix = self::SUFFIX;

        foreach ($fixes as $name => $photoId) {
            $updated = MenuItem::where('restaurant_id', self::RESTAURANT_ID)
                ->where('name', $name)
                ->update(['image_path' => $base . $photoId . $suffix]);

            $this->command->line("  {$name}: " . ($updated ? '<info>fixed</info>' : '<comment>not found</comment>'));
        }
    }
}
