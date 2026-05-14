<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Customer\CartResource;
use App\Models\MenuItem;
use App\Models\Order;
use App\Support\CustomerCartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartController extends Controller
{
    public function show(Request $request): CartResource
    {
        return CartResource::make($this->transformCartOrder($request));
    }

    public function store(Request $request, MenuItem $menuItem, CustomerCartService $cartService): CartResource|JsonResponse
    {
        $menuItem->loadMissing('restaurant');

        abort_unless($menuItem->is_available && $menuItem->restaurant?->is_visible, 404);

        $cartContext = $cartService->context($request->user());

        if ($cartService->willReplaceCart($request->user(), $menuItem) && ! $request->boolean('replace_cart')) {
            return $this->replaceCartConflictResponse(
                $cartContext,
                'Your cart already contains items from '.$cartContext['restaurantName'].'. Confirm replacing it before adding '.$menuItem->name.'.',
                [
                    'restaurantId' => (int) $menuItem->restaurant_id,
                    'restaurantName' => $menuItem->restaurant->name,
                    'menuItemId' => $menuItem->id,
                    'menuItemName' => $menuItem->name,
                ],
            );
        }

        $cartService->addMenuItem($request->user(), $menuItem, $request->boolean('replace_cart'));

        return CartResource::make($cartService->payload($request->user()));
    }

    public function increment(Request $request, MenuItem $menuItem): CartResource
    {
        abort_unless($menuItem->is_available, 404);

        $cartOrder = $this->currentCartOrder($request);

        if (! $cartOrder || $cartOrder->restaurant_id !== $menuItem->restaurant_id) {
            return CartResource::make($this->transformCartOrder($request));
        }

        $this->incrementOrderItem($cartOrder, $menuItem);
        $this->syncCartOrderTotals($cartOrder);

        return CartResource::make($this->transformCartOrder($request));
    }

    public function decrement(Request $request, MenuItem $menuItem): CartResource
    {
        $cartOrder = $this->currentCartOrder($request);

        if (! $cartOrder || $cartOrder->restaurant_id !== $menuItem->restaurant_id) {
            return CartResource::make($this->transformCartOrder($request));
        }

        $orderItem = $cartOrder->orderItems()
            ->where('menu_item_id', $menuItem->id)
            ->first();

        if (! $orderItem) {
            return CartResource::make($this->transformCartOrder($request));
        }

        if ($orderItem->quantity <= 1) {
            $orderItem->delete();
        } else {
            $quantity = $orderItem->quantity - 1;

            $orderItem->update([
                'quantity' => $quantity,
                'line_total' => $orderItem->unit_price * $quantity,
            ]);
        }

        if (! $cartOrder->orderItems()->exists()) {
            $cartOrder->delete();

            return CartResource::make($this->emptyCart());
        }

        $this->syncCartOrderTotals($cartOrder);

        return CartResource::make($this->transformCartOrder($request));
    }

    private function currentCartOrder(Request $request): ?Order
    {
        return $request->user()
            ->orders()
            ->with(['restaurant', 'orderItems'])
            ->where('status', 'cart')
            ->latest('updated_at')
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function transformCartOrder(Request $request): array
    {
        $cartOrder = $this->currentCartOrder($request);

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
                        'formatted' => $this->formatMoney($item->line_total),
                    ],
                ])
                ->values(),
            'totals' => [
                [
                    'label' => 'Subtotal',
                    'value' => (int) $cartOrder->subtotal,
                    'formatted' => $this->formatMoney($cartOrder->subtotal),
                ],
                [
                    'label' => 'Delivery',
                    'value' => (int) $cartOrder->delivery_fee,
                    'formatted' => $this->formatMoney($cartOrder->delivery_fee, 'Free delivery'),
                ],
                [
                    'label' => 'Service fee',
                    'value' => (int) $cartOrder->service_fee,
                    'formatted' => $this->formatMoney($cartOrder->service_fee),
                ],
            ],
            'totalValue' => (int) $cartOrder->total,
            'totalFormatted' => $this->formatMoney($cartOrder->total),
        ];
    }

    private function currentOrNewCartOrder(Request $request, MenuItem $menuItem): Order
    {
        $cartOrder = $this->currentCartOrder($request);

        if (! $cartOrder) {
            return Order::create([
                'user_id' => $request->user()->id,
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

    private function incrementOrderItem(Order $cartOrder, MenuItem $menuItem): void
    {
        $orderItem = $cartOrder->orderItems()
            ->where('menu_item_id', $menuItem->id)
            ->first();

        if (! $orderItem) {
            $cartOrder->orderItems()->create([
                'menu_item_id' => $menuItem->id,
                'name' => $menuItem->name,
                'quantity' => 1,
                'unit_price' => $menuItem->price,
                'line_total' => $menuItem->price,
            ]);

            return;
        }

        $quantity = $orderItem->quantity + 1;

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

    /**
     * @param  array{restaurantId: ?int, restaurantName: ?string, itemsCount: int}  $cartContext
     * @param  array<string, mixed>  $incoming
     */
    private function replaceCartConflictResponse(array $cartContext, string $message, array $incoming): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'conflict' => [
                'type' => 'replace_cart',
                'currentCart' => $cartContext,
                'incoming' => $incoming,
            ],
        ], 409);
    }
}