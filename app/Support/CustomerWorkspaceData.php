<?php

namespace App\Support;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Str;

class CustomerWorkspaceData
{
    public function __construct(private readonly CustomerOrderListData $orderListData) {}

    /**
     * @return array<string, mixed>
     */
    public function build(User $user): array
    {
        $catalog = app(CustomerCatalogData::class)->publicHome();
        $cartOrder = $this->currentCartOrder($user);

        return [
            'overview' => [
                'restaurantsCount' => (int) data_get($catalog, 'stats.restaurantsCount', 0),
                'foodsCount' => (int) data_get($catalog, 'stats.foodsCount', 0),
                'activeOrdersCount' => $user->orders()->whereIn('status', ['preparing', 'on_the_way'])->count(),
                'recentOrdersCount' => $user->orders()->where('status', '!=', 'cart')->count(),
                'cartItemsCount' => (int) ($cartOrder?->orderItems->sum('quantity') ?? 0),
                'cartTotalValue' => (int) ($cartOrder?->total ?? 0),
                'averageDeliveryMinutes' => (int) data_get($catalog, 'stats.averageDeliveryMinutes', 0),
                'highestRatedRestaurant' => data_get($catalog, 'stats.highestRatedRestaurant'),
            ],
            'spotlightRestaurants' => collect(data_get($catalog, 'restaurants', []))
                ->take(6)
                ->values(),
            'spotlightFoods' => collect(data_get($catalog, 'foods', []))
                ->take(6)
                ->values(),
            'cart' => $this->transformCartOrder($cartOrder),
            'recentOrders' => $this->orderListData->recentFor($user),
        ];
    }

    private function currentCartOrder(User $user): ?Order
    {
        $cartOrder = $user->orders()
            ->with(['restaurant:id,name', 'orderItems'])
            ->where('status', 'cart')
            ->latest('updated_at')
            ->first();

        return $cartOrder ? $this->ensureOrderIdempotencyKey($cartOrder) : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function transformCartOrder(?Order $cartOrder): array
    {
        if (! $cartOrder) {
            return $this->emptyCart();
        }

        return [
            'restaurant' => $cartOrder->restaurant?->name,
            'idempotencyKey' => $cartOrder->idempotency_key,
            'items' => $cartOrder->orderItems
                ->map(fn ($item) => [
                    'menuItemId' => $item->menu_item_id,
                    'name' => $item->name,
                    'restaurant' => $cartOrder->restaurant?->name,
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