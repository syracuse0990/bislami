<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('orders')
            ->select(['id', 'items', 'created_at', 'updated_at'])
            ->orderBy('id')
            ->cursor()
            ->each(function ($order): void {
                $items = is_array($order->items)
                    ? $order->items
                    : json_decode($order->items ?? '[]', true, flags: JSON_THROW_ON_ERROR);

                if (! is_array($items) || $items === []) {
                    return;
                }

                foreach ($items as $item) {
                    $quantity = max((int) ($item['quantity'] ?? 1), 1);
                    $lineTotal = (int) ($item['price'] ?? 0);
                    $unitPrice = (int) round($lineTotal / $quantity);

                    DB::table('order_items')->insert([
                        'order_id' => $order->id,
                        'menu_item_id' => $item['menu_item_id'] ?? null,
                        'name' => $item['name'] ?? 'Unknown item',
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'line_total' => $lineTotal,
                        'created_at' => $order->created_at,
                        'updated_at' => $order->updated_at,
                    ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Historical order items are intentionally not re-serialized back into orders.items.
    }
};