<?php

namespace App\Support;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Str;
use RuntimeException;

class CustomerCartService
{
    public function __construct(private readonly CustomerOrderListData $orderListData) {}

    /**
     * @return array{restaurantId: ?int, restaurantName: ?string, itemsCount: int}
     */
    public function context(User $user): array
    {
        $cartOrder = $this->currentCartOrder($user);

        if (! $cartOrder) {
            return [
                'restaurantId' => null,
                'restaurantName' => null,
                'itemsCount' => 0,
            ];
        }

        return [
            'restaurantId' => (int) $cartOrder->restaurant_id,
            'restaurantName' => $cartOrder->restaurant->name,
            'itemsCount' => (int) $cartOrder->orderItems->sum('quantity'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(User $user): array
    {
        return $this->transformCartOrder($this->currentCartOrder($user));
    }

    public function addMenuItem(User $user, MenuItem $menuItem, bool $replaceCart = false): Order
    {
        $menuItem->loadMissing('restaurant');

        if ($this->willReplaceCart($user, $menuItem) && ! $replaceCart) {
            throw new RuntimeException('This cart change requires confirmation.');
        }

        $cartOrder = $this->currentOrNewCartOrder($user, $menuItem);

        $this->incrementOrderItem($cartOrder, $menuItem);
        $this->syncCartOrderTotals($cartOrder);

        return $this->ensureOrderIdempotencyKey($cartOrder->fresh(['restaurant', 'orderItems']));
    }

    public function willReplaceCart(User $user, MenuItem $menuItem): bool
    {
        return $this->willReplaceCartForRestaurant($user, (int) $menuItem->restaurant_id);
    }

    public function willReplaceCartForOrder(User $user, Order $order): bool
    {
        return $this->willReplaceCartForRestaurant($user, (int) $order->restaurant_id);
    }

    public function willReplaceCartForRestaurant(User $user, int $restaurantId): bool
    {
        $currentCart = $this->currentCartOrder($user);

        return $currentCart !== null
            && $currentCart->restaurant_id !== $restaurantId
            && $currentCart->orderItems->isNotEmpty();
    }

    public function reorder(User $user, Order $order): Order
    {
        $reorderItems = $this->orderListData->reorderItems($order);
        $firstMenuItem = data_get($reorderItems->first(), 'menuItem');

        if (! $firstMenuItem instanceof MenuItem) {
            throw new RuntimeException('This order cannot be reordered.');
        }

        $cartOrder = $this->currentOrNewCartOrder($user, $firstMenuItem);

        foreach ($reorderItems as $reorderItem) {
            $this->incrementOrderItem(
                $cartOrder,
                $reorderItem['menuItem'],
                $reorderItem['quantity'],
            );
        }

        $this->syncCartOrderTotals($cartOrder);

        return $this->ensureOrderIdempotencyKey($cartOrder->fresh(['restaurant', 'orderItems']));
    }

    private function currentCartOrder(User $user): ?Order
    {
        return $user->orders()
            ->with(['restaurant', 'orderItems'])
            ->where('status', 'cart')
            ->latest('updated_at')
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function transformCartOrder(?Order $cartOrder): array
    {
        if (! $cartOrder) {
            return $this->emptyCart();
        }

        $cartOrder = $this->ensureOrderIdempotencyKey($cartOrder);

        return [
            'restaurant' => $cartOrder->restaurant->name,
            'idempotencyKey' => $cartOrder->idempotency_key,
            'items' => $cartOrder->orderItems
                ->map(fn ($item) => [
                    'menuItemId' => $item->menu_item_id,
                    'name' => $item->name,
                    'restaurant' => $cartOrder->restaurant->name,
                    'quantityLabel' => $item->quantity.'x',
                    'quantityValue' => $item->quantity,
                    'lineTotal' => [
                        'value' => (int) $item->line_total,
                        'formatted' => $this->formatMoney((int) $item->line_total),
                    ],
                ])
                ->values(),
            'totals' => [
                [
                    'label' => 'Subtotal',
                    'value' => (int) $cartOrder->subtotal,
                    'formatted' => $this->formatMoney((int) $cartOrder->subtotal),
                ],
                [
                    'label' => 'Delivery',
                    'value' => (int) $cartOrder->delivery_fee,
                    'formatted' => $this->formatMoney((int) $cartOrder->delivery_fee, 'Free delivery'),
                ],
                [
                    'label' => 'Service fee',
                    'value' => (int) $cartOrder->service_fee,
                    'formatted' => $this->formatMoney((int) $cartOrder->service_fee),
                ],
            ],
            'totalValue' => (int) $cartOrder->total,
            'totalFormatted' => $this->formatMoney((int) $cartOrder->total),
        ];
    }

    private function currentOrNewCartOrder(User $user, MenuItem $menuItem): Order
    {
        $menuItem->loadMissing('restaurant');

        $cartOrder = $this->currentCartOrder($user);

        if (! $cartOrder) {
            return Order::create([
                'user_id' => $user->id,
                'restaurant_id' => $menuItem->restaurant_id,
                'status' => 'cart',
                'subtotal' => 0,
                'delivery_fee' => $menuItem->restaurant->delivery_fee,
                'service_fee' => 25,
                'total' => $menuItem->restaurant->delivery_fee + 25,
                'payment_method' => 'Cash on delivery',
                'idempotency_key' => (string) Str::uuid(),
                'delivery_address' => '',
                'delivery_latitude' => null,
                'delivery_longitude' => null,
                'driver_notes' => null,
                'placed_at' => null,
            ])->load(['restaurant', 'orderItems']);
        }

        if ($cartOrder->restaurant_id !== $menuItem->restaurant_id) {
            $cartOrder->orderItems()->delete();

            $cartOrder->update([
                'restaurant_id' => $menuItem->restaurant_id,
                'status' => 'cart',
                'subtotal' => 0,
                'delivery_fee' => $menuItem->restaurant->delivery_fee,
                'service_fee' => 25,
                'total' => $menuItem->restaurant->delivery_fee + 25,
                'payment_method' => 'Cash on delivery',
                'idempotency_key' => (string) Str::uuid(),
                'delivery_address' => '',
                'delivery_latitude' => null,
                'delivery_longitude' => null,
                'driver_notes' => null,
                'placed_at' => null,
            ]);

            return $cartOrder->fresh(['restaurant', 'orderItems']);
        }

        return $cartOrder;
    }

    private function incrementOrderItem(Order $cartOrder, MenuItem $menuItem, int $quantity = 1): void
    {
        $orderItem = $cartOrder->orderItems()
            ->where('menu_item_id', $menuItem->id)
            ->first();

        if (! $orderItem) {
            $cartOrder->orderItems()->create([
                'menu_item_id' => $menuItem->id,
                'name' => $menuItem->name,
                'quantity' => $quantity,
                'unit_price' => $menuItem->price,
                'line_total' => $menuItem->price * $quantity,
            ]);

            return;
        }

        $quantity += $orderItem->quantity;

        $orderItem->update([
            'quantity' => $quantity,
            'line_total' => $orderItem->unit_price * $quantity,
        ]);
    }

    private function syncCartOrderTotals(Order $cartOrder): void
    {
        $cartOrder->loadMissing('restaurant');

        $subtotal = (int) $cartOrder->orderItems()->sum('line_total');

        $cartOrder->update([
            'subtotal' => $subtotal,
            'delivery_fee' => $cartOrder->restaurant->delivery_fee,
            'service_fee' => 25,
            'total' => $subtotal + $cartOrder->restaurant->delivery_fee + 25,
        ]);
    }

    private function ensureOrderIdempotencyKey(Order $order): Order
    {
        if ($order->idempotency_key) {
            return $order;
        }

        $order->forceFill([
            'idempotency_key' => (string) Str::uuid(),
        ])->save();

        return $order;
    }

    private function formatMoney(int $amount, ?string $zeroLabel = null): string
    {
        return \App\Support\MoneyFormatter::format($amount, $zeroLabel);
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyCart(): array
    {
        return [
            'restaurant' => null,
            'idempotencyKey' => null,
            'items' => [],
            'totals' => [],
            'totalValue' => 0,
            'totalFormatted' => $this->formatMoney(0),
        ];
    }
}