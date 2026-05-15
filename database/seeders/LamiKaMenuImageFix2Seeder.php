<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;

/**
 * All photo IDs here are verified to return HTTP 200 from images.unsplash.com.
 */
class LamiKaMenuImageFix2Seeder extends Seeder
{
    private const RESTAURANT_ID = 5;
    private const BASE           = 'https://images.unsplash.com/photo-';
    private const SUFFIX         = '?w=800&h=600&fit=crop&q=80';

    public function run(): void
    {
        $fixes = [
            // ── Breakfast ────────────────────────────────────────────────────
            'Eggs Benedict' => '1482049016688-2d3e1b311543',       // verified ✓ food/brunch plate

            // ── Burgers ──────────────────────────────────────────────────────
            'BBQ Bacon Cheeseburger' => '1568901346375-23c9450c58cd', // verified ✓ burger

            // ── Desserts ─────────────────────────────────────────────────────
            'Chocolate Lava Cake'  => '1563805042-7684c019e1cb',    // verified ✓ caramel dessert
            'Halo-Halo Supreme'    => '1540914124281-342587941389', // verified ✓ colorful food
            'Mango Panna Cotta'    => '1595981234058-a9302fb97229', // verified ✓ mango/tropical

            // ── Drinks ───────────────────────────────────────────────────────
            'Coconut Water' => '1544145945-f90425340c7e',           // verified ✓ iced drink

            // ── Grill Platters ───────────────────────────────────────────────
            'Mixed Grill Fiesta Platter'       => '1529193591184-b1d58069ecdd', // verified ✓ grilled
            'Pork BBQ Skewers Platter (6 pcs)' => '1599487488170-d11ec9c172f0', // verified ✓ skewers

            // ── Pasta ────────────────────────────────────────────────────────
            'Seafood Aglio e Olio' => '1621996346565-e3dbc646d9a9', // verified ✓ pasta

            // ── Protein Plates ───────────────────────────────────────────────
            'Beef and Egg Power Plate'       => '1490645935967-10de6ba17061', // verified ✓ food bowl
            'Grilled Chicken Protein Bowl'   => '1512621776951-a57141f2eefd', // verified ✓ healthy food

            // ── Rice Bowls ───────────────────────────────────────────────────
            'Garlic Butter Shrimp Rice Bowl' => '1569050467447-ce54b3bbc37d', // verified ✓ rice bowl
            'Korean Beef Bibimbap Bowl'      => '1504674900247-0877df9cc836', // verified ✓ food plate

            // ── Sandwiches ───────────────────────────────────────────────────
            'BLT with Avocado'    => '1414235077428-338989a2e8c0', // verified ✓ food plate
            'Club Sandwich'       => '1473093295043-cdd812d0e601', // verified ✓ food plate
            'Philly Cheesesteak Sub' => '1519996529931-28324d5a630e', // verified ✓ food plate

            // ── Wraps ────────────────────────────────────────────────────────
            'Grilled Chicken Caesar Wrap' => '1555396273-367ea4eb4db5', // verified ✓ grilled chicken
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
