<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerCheckoutRequest;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Support\CustomerCatalogData;
use App\Support\CustomerCartService;
use App\Support\CustomerOrderListData;
use App\Support\CustomerOrderTrackingData;
use App\Support\CustomerWorkspaceData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CustomerPageController extends Controller
{
    public function dashboard(Request $request, CustomerWorkspaceData $workspaceData): Response
    {
        return Inertia::render('Customer/Dashboard', $workspaceData->build($request->user()));
    }

    public function restaurants(CustomerCatalogData $catalogData): Response
    {
        return Inertia::render('Customer/Restaurants/Index', $catalogData->build());
    }

    public function showRestaurant(Restaurant $restaurant, CustomerCatalogData $catalogData): Response
    {
        abort_unless($restaurant->is_visible, 404);

        return Inertia::render('Customer/Restaurants/Show', [
            'restaurant' => $catalogData->restaurantDetail($restaurant),
        ]);
    }

    public function cart(Request $request): Response
    {
        return Inertia::render('Customer/Cart/Index', [
            'cart' => $this->transformCartOrder($request),
        ]);
    }

    public function storeCart(Request $request, MenuItem $menuItem, CustomerCartService $cartService): RedirectResponse
    {
        $menuItem->loadMissing('restaurant');

        abort_unless($menuItem->is_available && $menuItem->restaurant?->is_visible, 404);

        $cartContext = $cartService->context($request->user());
        $willReplaceCart = $cartService->willReplaceCart($request->user(), $menuItem);

        if ($willReplaceCart && ! $request->boolean('replace_cart')) {
            return $this->redirectToRequestedPath($request, route('customer.cart.index'))
                ->with('error', 'Your cart already contains items from '.$cartContext['restaurantName'].'. Confirm replacing it before adding '.$menuItem->name.'.');
        }

        $cartService->addMenuItem($request->user(), $menuItem, $request->boolean('replace_cart'));

        $message = $menuItem->name.' added to cart.';

        if ($willReplaceCart) {
            $message .= ' Your previous cart from '.$cartContext['restaurantName'].' was replaced.';
        }

        return $this->redirectToRequestedPath($request, route('customer.cart.index'))
            ->with('success', $message);
    }

    public function incrementCartItem(Request $request, MenuItem $menuItem): RedirectResponse
    {
        $menuItem->loadMissing('restaurant');

        abort_unless($menuItem->is_available && $menuItem->restaurant?->is_visible, 404);

        $cartOrder = $this->currentCartOrder($request);

        if (! $cartOrder || $cartOrder->restaurant_id !== $menuItem->restaurant_id) {
            return redirect()->route('customer.cart.index');
        }

        $this->incrementOrderItem($cartOrder, $menuItem);
        $this->syncCartOrderTotals($cartOrder);

        return redirect()->route('customer.cart.index');
    }

    public function decrementCartItem(Request $request, MenuItem $menuItem): RedirectResponse
    {
        $cartOrder = $this->currentCartOrder($request);

        if (! $cartOrder || $cartOrder->restaurant_id !== $menuItem->restaurant_id) {
            return redirect()->route('customer.cart.index');
        }

        $orderItem = $cartOrder->orderItems()
            ->where('menu_item_id', $menuItem->id)
            ->first();

        if (! $orderItem) {
            return redirect()->route('customer.cart.index');
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

            return redirect()->route('customer.cart.index');
        }

        $this->syncCartOrderTotals($cartOrder);

        return redirect()->route('customer.cart.index');
    }

    public function checkout(Request $request): Response
    {
        $cartOrder = $this->currentCartOrder($request);
        $cartOrder = $cartOrder ? $this->ensureOrderIdempotencyKey($cartOrder) : null;

        return Inertia::render('Customer/Checkout/Index', [
            'checkout' => $cartOrder ? [
                'restaurant' => $cartOrder->restaurant->name,
                'summary' => $this->summarizeItems($cartOrder),
                'deliveryAddress' => $cartOrder->delivery_address,
                'deliveryLatitude' => $cartOrder->delivery_latitude,
                'deliveryLongitude' => $cartOrder->delivery_longitude,
                'idempotencyKey' => $cartOrder->idempotency_key,
                'paymentMethod' => $cartOrder->payment_method,
                'driverNotes' => $cartOrder->driver_notes ?? '',
                'total' => $this->formatMoney($cartOrder->total),
            ] : [
                'restaurant' => null,
                'summary' => null,
                'deliveryAddress' => '',
                'deliveryLatitude' => null,
                'deliveryLongitude' => null,
                'idempotencyKey' => null,
                'paymentMethod' => '',
                'driverNotes' => '',
                'total' => $this->formatMoney(0),
            ],
        ]);
    }

    public function placeOrder(CustomerCheckoutRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $placedOrder = DB::transaction(function () use ($request, $validated) {
            $cartOrder = $request->user()
                ->orders()
                ->with(['restaurant', 'orderItems'])
                ->where('status', 'cart')
                ->latest('updated_at')
                ->lockForUpdate()
                ->first();

            if (! $cartOrder) {
                return $request->user()
                    ->orders()
                    ->where('idempotency_key', $validated['idempotency_key'])
                    ->where('status', '!=', 'cart')
                    ->latest('id')
                    ->first();
            }

            $cartOrder = $this->ensureOrderIdempotencyKey($cartOrder);

            if ($cartOrder->idempotency_key !== $validated['idempotency_key']) {
                $existingOrder = $request->user()
                    ->orders()
                    ->where('idempotency_key', $validated['idempotency_key'])
                    ->where('status', '!=', 'cart')
                    ->latest('id')
                    ->first();

                if ($existingOrder) {
                    return $existingOrder;
                }

                throw ValidationException::withMessages([
                    'checkout' => 'Your checkout session expired. Refresh the page and place the order again.',
                ]);
            }

            unset($validated['idempotency_key']);

            $cartOrder->update([
                ...$validated,
                'status' => 'preparing',
                'placed_at' => now(),
            ]);

            return $cartOrder;
        });

        if (! $placedOrder) {
            return redirect()->route('customer.restaurants.index');
        }

        return redirect()->route('customer.orders.show', $placedOrder);
    }

    public function orders(Request $request, CustomerOrderListData $orderListData): Response
    {
        $payload = $orderListData->listFor(
            $request->user(),
            $request->string('status')->toString(),
        );

        return Inertia::render('Customer/Orders/Index', [
            'orders' => collect($payload['orders'])
                ->map(fn (array $order) => $this->transformOrderCard($order))
                ->values(),
            'filters' => [
                'status' => $payload['filter'],
            ],
            'overview' => $payload['overview'],
        ]);
    }

    public function showOrder(Request $request, Order $order): Response
    {
        abort_unless($order->user_id === $request->user()->id && $order->status !== 'cart', 404);

        $order->loadMissing(['restaurant', 'orderItems']);

        return Inertia::render('Customer/Orders/Show', [
            'order' => $this->transformOrderDetail($order),
        ]);
    }

    public function reorder(
        Request $request,
        Order $order,
        CustomerOrderListData $orderListData,
        CustomerCartService $cartService,
    ): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id && $order->status !== 'cart', 404);
        abort_unless($orderListData->canReorder($order), 404);

        $order->load('restaurant:id,name');
        $cartContext = $cartService->context($request->user());
        $willReplaceCart = $cartService->willReplaceCartForOrder($request->user(), $order);

        if ($willReplaceCart && ! $request->boolean('replace_cart')) {
            return $this->redirectToRequestedPath($request, route('customer.cart.index'))
                ->with('error', 'Your cart already contains items from '.$cartContext['restaurantName'].'. Confirm replacing it before reordering '.$this->orderNumber($order).'.');
        }

        $cartService->reorder($request->user(), $order);

        $message = 'Order '.$this->orderNumber($order).' was rebuilt in cart.';

        if ($willReplaceCart) {
            $message .= ' Your previous cart from '.$cartContext['restaurantName'].' was replaced.';
        }

        return $this->redirectToRequestedPath($request, route('customer.cart.index'))
            ->with('success', $message);
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

    private function transformCartOrder(Request $request): ?array
    {
        $cartOrder = $this->currentCartOrder($request);

        if (! $cartOrder) {
            return [
                'restaurant' => null,
                'items' => [],
                'totals' => [],
                'total' => $this->formatMoney(0),
            ];
        }

        return [
            'restaurant' => $cartOrder->restaurant->name,
            'items' => $cartOrder->orderItems
                ->map(fn ($item) => [
                    'menuItemId' => $item->menu_item_id,
                    'name' => $item->name,
                    'restaurant' => $cartOrder->restaurant->name,
                    'quantity' => $item->quantity.'x',
                    'quantityValue' => $item->quantity,
                    'price' => $this->formatMoney($item->line_total),
                ])
                ->values(),
            'totals' => [
                ['label' => 'Subtotal', 'value' => $this->formatMoney($cartOrder->subtotal)],
                ['label' => 'Delivery', 'value' => $this->formatMoney($cartOrder->delivery_fee, 'Free delivery')],
                ['label' => 'Service fee', 'value' => $this->formatMoney($cartOrder->service_fee)],
            ],
            'total' => $this->formatMoney($cartOrder->total),
        ];
    }

    private function summarizeItems(Order $order): string
    {
        return $order->orderItems
            ->map(fn ($item) => $item->quantity.'x '.$item->name)
            ->join(', ');
    }

    /**
     * @return array<string, mixed>
     */
    private function transformOrderCard(array $order): array
    {
        $statusKey = (string) data_get($order, 'status.key', '');

        return [
            'id' => (int) data_get($order, 'id'),
            'orderNumber' => (string) data_get($order, 'orderNumber'),
            'restaurantId' => (int) data_get($order, 'restaurant.id'),
            'restaurant' => (string) data_get($order, 'restaurant.name'),
            'restaurantSlug' => (string) data_get($order, 'restaurant.slug'),
            'status' => (string) data_get($order, 'status.label'),
            'statusKey' => $statusKey,
            'summary' => (string) data_get($order, 'summary'),
            'placedAt' => (string) (data_get($order, 'placedAgo') ?: 'Just now'),
            'accent' => $this->statusAccent($statusKey),
            'total' => (string) data_get($order, 'total.formatted'),
            'canTrack' => (bool) data_get($order, 'canTrack', false),
            'canReorder' => (bool) data_get($order, 'canReorder', false),
            'reorder' => data_get($order, 'reorder', [
                'visible' => false,
                'available' => false,
                'label' => null,
                'description' => null,
                'items' => [],
            ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function transformOrderDetail(Order $order): array
    {
        $tracking = app(CustomerOrderTrackingData::class)->build($order->status);
        $reorder = app(CustomerOrderListData::class)->reorderState($order);

        return [
            'id' => $order->id,
            'orderNumber' => $this->orderNumber($order),
            'restaurant' => [
                'id' => $order->restaurant->id,
                'name' => $order->restaurant->name,
                'slug' => $order->restaurant->slug,
                'category' => $order->restaurant->category,
                'cuisine' => $order->restaurant->cuisine,
                'featured' => $order->restaurant->featured_text,
                'eta' => "{$order->restaurant->min_delivery_time}-{$order->restaurant->max_delivery_time} min",
                'deliveryFee' => $this->formatMoney((int) $order->restaurant->delivery_fee, 'Free delivery'),
            ],
            'status' => $tracking['status'],
            'guidance' => $tracking['guidance'],
            'timeline' => $tracking['timeline'],
            'summary' => $this->summarizeItems($order),
            'placedAt' => $order->placed_at?->diffForHumans() ?? 'Just now',
            'placedAtDate' => $order->placed_at?->toDayDateTimeString() ?? 'Just now',
            'paymentMethod' => $order->payment_method,
            'driverNotes' => $order->driver_notes,
            'canTrack' => in_array($order->status, ['preparing', 'on_the_way'], true),
            'canReorder' => $reorder['available'],
            'reorder' => $reorder,
            'items' => $order->orderItems
                ->map(fn ($item) => [
                    'name' => $item->name,
                    'quantityLabel' => $item->quantity.'x',
                    'quantityValue' => $item->quantity,
                    'unitPrice' => $this->formatMoney((int) $item->unit_price),
                    'lineTotal' => $this->formatMoney((int) $item->line_total),
                ])
                ->values(),
            'totals' => [
                'subtotal' => [
                    'value' => (int) $order->subtotal,
                    'formatted' => $this->formatMoney((int) $order->subtotal),
                ],
                'delivery' => [
                    'value' => (int) $order->delivery_fee,
                    'formatted' => $this->formatMoney((int) $order->delivery_fee, 'Free delivery'),
                ],
                'serviceFee' => [
                    'value' => (int) $order->service_fee,
                    'formatted' => $this->formatMoney((int) $order->service_fee),
                ],
                'total' => [
                    'value' => (int) $order->total,
                    'formatted' => $this->formatMoney((int) $order->total),
                ],
            ],
            'destination' => [
                'address' => $order->delivery_address,
                'shortLabel' => $this->shortAddress($order->delivery_address),
                'hasCoordinates' => $order->delivery_latitude !== null && $order->delivery_longitude !== null,
                'mapsUrl' => $this->mapsUrl($order),
            ],
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

    private function statusAccent(string $status): string
    {
        return match ($status) {
            'preparing' => 'bg-orange-50 text-orange-700',
            'on_the_way' => 'bg-sky-50 text-sky-700',
            'delivered' => 'bg-emerald-50 text-emerald-700',
            default => 'bg-gray-100 text-gray-700',
        };
    }

    private function orderNumber(Order $order): string
    {
        return '#BL-'.str_pad((string) $order->id, 4, '0', STR_PAD_LEFT);
    }

    private function redirectToRequestedPath(Request $request, string $fallback): RedirectResponse
    {
        return redirect()->to($this->requestedRedirectPath($request, $fallback));
    }

    private function requestedRedirectPath(Request $request, string $fallback): string
    {
        $target = $request->string('redirect_to')->toString();

        if ($target !== '' && Str::startsWith($target, '/') && ! Str::startsWith($target, '//')) {
            return $target;
        }

        return $fallback;
    }

    private function shortAddress(?string $address): string
    {
        if (! $address) {
            return 'Address pending';
        }

        return collect(explode(',', $address))
            ->map(fn (string $segment) => trim($segment))
            ->filter()
            ->take(2)
            ->join(', ');
    }

    private function mapsUrl(Order $order): ?string
    {
        if ($order->delivery_latitude !== null && $order->delivery_longitude !== null) {
            return 'https://www.google.com/maps/search/?api=1&query='
                .rawurlencode($order->delivery_latitude.','.$order->delivery_longitude);
        }

        if (! $order->delivery_address) {
            return null;
        }

        return 'https://www.google.com/maps/search/?api=1&query='
            .rawurlencode($order->delivery_address);
    }

    private function formatMoney(int $amount, ?string $zeroLabel = null): string
    {
        return \App\Support\MoneyFormatter::format($amount, $zeroLabel);
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
}