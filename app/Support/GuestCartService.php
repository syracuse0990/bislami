<?php

namespace App\Support;

use App\Models\MenuItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use RuntimeException;

class GuestCartService
{
    private const SESSION_KEY = 'guest_cart';

    /**
     * @return array{restaurantId: ?int, restaurantName: ?string, itemsCount: int}
     */
    public function context(Request $request): array
    {
        $payload = $this->payload($request);

        return [
            'restaurantId' => data_get($payload, 'restaurantId'),
            'restaurantName' => data_get($payload, 'restaurantName'),
            'itemsCount' => (int) collect(data_get($payload, 'items', []))->sum('quantityValue'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(Request $request): array
    {
        $cart = $this->cart($request);

        if (! filled($cart['restaurant_id'] ?? null) || ($cart['items'] ?? []) === []) {
            $this->clear($request);

            return $this->emptyCart();
        }

        $menuItems = $this->validMenuItems($cart);

        if ($menuItems->isEmpty()) {
            $this->clear($request);

            return $this->emptyCart();
        }

        $restaurant = $menuItems->first()->restaurant;

        if (! $restaurant) {
            $this->clear($request);

            return $this->emptyCart();
        }

        $items = $menuItems
            ->map(function (MenuItem $menuItem) use ($cart, $restaurant): ?array {
                $quantity = (int) data_get($cart, 'items.'.$menuItem->id, 0);

                if ($quantity < 1) {
                    return null;
                }

                $lineTotal = (int) $menuItem->price * $quantity;

                return [
                    'menuItemId' => $menuItem->id,
                    'name' => $menuItem->name,
                    'restaurant' => $restaurant->name,
                    'imageUrl' => $menuItem->imageUrl(),
                    'quantity' => $quantity.'x',
                    'quantityValue' => $quantity,
                    'price' => $this->formatMoney($lineTotal),
                    'lineTotal' => [
                        'value' => $lineTotal,
                        'formatted' => $this->formatMoney($lineTotal),
                    ],
                ];
            })
            ->filter()
            ->values();

        if ($items->isEmpty()) {
            $this->clear($request);

            return $this->emptyCart();
        }

        $this->persist($request, [
            'restaurant_id' => $restaurant->id,
            'items' => $items
                ->mapWithKeys(fn (array $item) => [$item['menuItemId'] => $item['quantityValue']])
                ->all(),
        ]);

        $subtotal = (int) $items->sum(fn (array $item) => (int) data_get($item, 'lineTotal.value', 0));
        $deliveryFee = (int) $restaurant->delivery_fee;
        $serviceFee = 25;
        $total = $subtotal + $deliveryFee + $serviceFee;

        return [
            'restaurantId' => $restaurant->id,
            'restaurantName' => $restaurant->name,
            'restaurant' => $restaurant->name,
            'items' => $items->all(),
            'totals' => [
                ['label' => 'Subtotal', 'value' => $this->formatMoney($subtotal)],
                ['label' => 'Delivery', 'value' => $this->formatMoney($deliveryFee, 'Free delivery')],
                ['label' => 'Service fee', 'value' => $this->formatMoney($serviceFee)],
            ],
            'total' => $this->formatMoney($total),
            'totalValue' => $total,
        ];
    }

    public function addMenuItem(Request $request, MenuItem $menuItem, bool $replaceCart = false): void
    {
        if ($this->willReplaceCart($request, $menuItem) && ! $replaceCart) {
            throw new RuntimeException('This cart change requires confirmation.');
        }

        $cart = $this->cart($request);

        if (($cart['restaurant_id'] ?? null) !== $menuItem->restaurant_id) {
            $cart = [
                'restaurant_id' => $menuItem->restaurant_id,
                'items' => [],
            ];
        }

        $cart['items'][$menuItem->id] = (int) data_get($cart, 'items.'.$menuItem->id, 0) + 1;

        $this->persist($request, $cart);
        $this->payload($request);
    }

    public function increment(Request $request, MenuItem $menuItem): void
    {
        $cart = $this->cart($request);

        if (($cart['restaurant_id'] ?? null) !== $menuItem->restaurant_id) {
            return;
        }

        if (! array_key_exists($menuItem->id, $cart['items'] ?? [])) {
            return;
        }

        $cart['items'][$menuItem->id] = (int) $cart['items'][$menuItem->id] + 1;

        $this->persist($request, $cart);
        $this->payload($request);
    }

    public function decrement(Request $request, MenuItem $menuItem): void
    {
        $cart = $this->cart($request);

        if (($cart['restaurant_id'] ?? null) !== $menuItem->restaurant_id) {
            return;
        }

        if (! array_key_exists($menuItem->id, $cart['items'] ?? [])) {
            return;
        }

        $quantity = max(0, (int) $cart['items'][$menuItem->id] - 1);

        if ($quantity === 0) {
            unset($cart['items'][$menuItem->id]);
        } else {
            $cart['items'][$menuItem->id] = $quantity;
        }

        if (($cart['items'] ?? []) === []) {
            $this->clear($request);

            return;
        }

        $this->persist($request, $cart);
        $this->payload($request);
    }

    public function willReplaceCart(Request $request, MenuItem $menuItem): bool
    {
        $cart = $this->cart($request);

        return filled($cart['restaurant_id'] ?? null)
            && (int) $cart['restaurant_id'] !== (int) $menuItem->restaurant_id
            && collect($cart['items'] ?? [])->filter(fn (mixed $quantity) => (int) $quantity > 0)->isNotEmpty();
    }

    public function hasItems(Request $request): bool
    {
        return $this->context($request)['itemsCount'] > 0;
    }

    public function mergeIntoCustomer(Request $request, User $user, CustomerCartService $customerCartService): void
    {
        $items = $this->menuItemsWithQuantities($request);

        if ($items->isEmpty()) {
            $this->clear($request);

            return;
        }

        foreach ($items as $entry) {
            $replaceCart = $customerCartService->willReplaceCart($user, $entry['menuItem']);

            for ($index = 0; $index < $entry['quantity']; $index++) {
                $customerCartService->addMenuItem($user, $entry['menuItem'], $replaceCart);
                $replaceCart = false;
            }
        }

        $this->clear($request);
    }

    public function clear(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
    }

    /**
     * @return array{restaurant_id: ?int, items: array<int, int>}
     */
    private function cart(Request $request): array
    {
        $cart = $request->session()->get(self::SESSION_KEY, []);

        return [
            'restaurant_id' => data_get($cart, 'restaurant_id'),
            'items' => collect(data_get($cart, 'items', []))
                ->mapWithKeys(fn (mixed $quantity, mixed $menuItemId) => [(int) $menuItemId => (int) $quantity])
                ->filter(fn (int $quantity) => $quantity > 0)
                ->all(),
        ];
    }

    /**
     * @param  array{restaurant_id: ?int, items: array<int, int>}  $cart
     */
    private function persist(Request $request, array $cart): void
    {
        $request->session()->put(self::SESSION_KEY, $cart);
    }

    /**
     * @param  array{restaurant_id: ?int, items: array<int, int>}  $cart
     * @return Collection<int, MenuItem>
     */
    private function validMenuItems(array $cart): Collection
    {
        $restaurantId = (int) ($cart['restaurant_id'] ?? 0);
        $itemIds = array_keys($cart['items'] ?? []);

        if ($restaurantId === 0 || $itemIds === []) {
            return collect();
        }

        return MenuItem::query()
            ->with('restaurant')
            ->whereIn('id', $itemIds)
            ->where('restaurant_id', $restaurantId)
            ->where('is_available', true)
            ->get()
            ->filter(fn (MenuItem $menuItem) => $menuItem->restaurant?->is_visible)
            ->values();
    }

    /**
     * @return Collection<int, array{menuItem: MenuItem, quantity: int}>
     */
    private function menuItemsWithQuantities(Request $request): Collection
    {
        $cart = $this->cart($request);

        return $this->validMenuItems($cart)
            ->map(function (MenuItem $menuItem) use ($cart): ?array {
                $quantity = (int) data_get($cart, 'items.'.$menuItem->id, 0);

                if ($quantity < 1) {
                    return null;
                }

                return [
                    'menuItem' => $menuItem,
                    'quantity' => $quantity,
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyCart(): array
    {
        return [
            'restaurantId' => null,
            'restaurantName' => null,
            'restaurant' => null,
            'items' => [],
            'totals' => [],
            'total' => $this->formatMoney(0),
            'totalValue' => 0,
        ];
    }

    private function formatMoney(int $amount, ?string $zeroLabel = null): string
    {
        return \App\Support\MoneyFormatter::format($amount, $zeroLabel);
    }
}