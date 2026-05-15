<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Support\OrderLifecycle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CashierOrderController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'restaurant_id' => ['required', 'integer', 'exists:restaurants,id'],
            'fulfillment_type' => ['required', 'in:dine_in,pickup'],
            'customer_name' => ['nullable', 'string', 'max:100'],
            'table_number' => ['nullable', 'string', 'max:20'],
            'payment_method' => ['required', 'in:cash,card,gcash,maya'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_item_id' => ['required', 'integer', 'exists:menu_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.unit_price' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();

        // Verify restaurant ownership or active staff assignment
        $ownsRestaurant = $user->managedRestaurants()
            ->where('id', $validated['restaurant_id'])
            ->exists();

        if (! $ownsRestaurant) {
            $isStaff = $user->staffAssignments()
                ->where('restaurant_id', $validated['restaurant_id'])
                ->where('status', 'active')
                ->exists();

            abort_unless($isStaff, 403, 'You do not have access to this restaurant.');
        }

        $order = DB::transaction(function () use ($validated, $user): Order {
            $subtotal = (int) collect($validated['items'])
                ->sum(fn (array $item) => $item['unit_price'] * $item['quantity']);

            $notesParts = [];
            if (! empty($validated['customer_name'])) {
                $notesParts[] = 'Customer: '.$validated['customer_name'];
            }
            if (! empty($validated['table_number'])) {
                $notesParts[] = 'Table: '.$validated['table_number'];
            }
            if (! empty($validated['notes'])) {
                $notesParts[] = $validated['notes'];
            }

            $order = Order::create([
                'user_id' => $user->id,
                'restaurant_id' => $validated['restaurant_id'],
                'status' => OrderLifecycle::ACCEPTED,
                'fulfillment_type' => $validated['fulfillment_type'],
                'subtotal' => $subtotal,
                'delivery_fee' => 0,
                'service_fee' => 0,
                'total' => $subtotal,
                'payment_method' => $validated['payment_method'],
                'idempotency_key' => (string) Str::uuid(),
                'customer_notes' => $notesParts ? implode(' | ', $notesParts) : null,
                'placed_at' => now(),
                'accepted_at' => now(),
            ]);

            foreach ($validated['items'] as $item) {
                $menuItem = MenuItem::findOrFail($item['menu_item_id']);

                OrderItem::create([
                    'order_id' => $order->id,
                    'menu_item_id' => $item['menu_item_id'],
                    'name' => $menuItem->name,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => $item['unit_price'] * $item['quantity'],
                ]);
            }

            return $order;
        });

        ActivityLog::record(
            $user,
            $validated['restaurant_id'],
            'order.pos_created',
            "POS order #{$order->id} created via cashier terminal.",
            $order,
        );

        return redirect()->route('merchant.cashier.pos')
            ->with('pos_order_success', [
                'orderId' => $order->id,
                'orderNumber' => '#BL-'.str_pad((string) $order->id, 4, '0', STR_PAD_LEFT),
            ]);
    }
}
