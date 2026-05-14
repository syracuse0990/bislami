<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Support\OrderLifecycle;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MerchantWorkspacePageController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $merchant = $request->user();

        if (! $merchant->isMerchantVerified()) {
            $merchant->loadCount('managedRestaurants');

            return Inertia::render('Merchant/PendingApproval', [
                'merchant' => [
                    'name' => $merchant->name,
                    'email' => $merchant->email,
                    'managedRestaurantsCount' => (int) $merchant->managed_restaurants_count,
                    'joinedAt' => $merchant->created_at?->diffForHumans() ?? 'Recently',
                    'verificationLabel' => $merchant->email_verified_at ? 'Email verified' : 'Email verification pending',
                ],
            ]);
        }

        $restaurants = $merchant->managedRestaurants()
            ->select([
                'id',
                'name',
                'slug',
                'category',
                'cuisine',
                'min_delivery_time',
                'max_delivery_time',
                'rating',
                'delivery_fee',
                'featured_text',
                'is_visible',
                'order_settings',
            ])
            ->withCount([
                'menuItems',
                'menuItems as live_menu_items_count' => fn ($query) => $query->where('is_available', true),
                'menuItems as paused_menu_items_count' => fn ($query) => $query->where('is_available', false),
                'orders as active_orders_count' => fn ($query) => $query->whereIn('status', $this->activeStatuses()),
            ])
            ->orderBy('name')
            ->get();

        $restaurantIds = $restaurants->pluck('id');

        $activeOrders = $restaurantIds->isEmpty()
            ? collect()
            : Order::query()
                ->with($this->dashboardOrderRelations())
                ->whereIn('restaurant_id', $restaurantIds)
                ->whereIn('status', $this->activeStatuses())
                ->latest('placed_at')
                ->latest('id')
                ->get();

        $recentMenuItems = $restaurantIds->isEmpty()
            ? collect()
            : MenuItem::query()
                ->select(['id', 'restaurant_id', 'name', 'category', 'price', 'is_available', 'updated_at'])
                ->with('restaurant:id,name')
                ->whereIn('restaurant_id', $restaurantIds)
                ->latest('updated_at')
                ->latest('id')
                ->limit(4)
                ->get();

        $ordersTodayCount = $restaurantIds->isEmpty()
            ? 0
            : Order::query()
                ->whereIn('restaurant_id', $restaurantIds)
                ->whereDate('placed_at', today())
                ->count();

        return Inertia::render('Merchant/Dashboard', [
            'overview' => [
                'activeOrdersCount' => $activeOrders->count(),
                'pendingOrdersCount' => $activeOrders->where('status', OrderLifecycle::PENDING)->count(),
                'acceptedOrdersCount' => $activeOrders->where('status', OrderLifecycle::ACCEPTED)->count(),
                'preparingOrdersCount' => $activeOrders
                    ->filter(fn (Order $order) => in_array(OrderLifecycle::normalize($order->status), [OrderLifecycle::ACCEPTED, OrderLifecycle::PREPARING], true))
                    ->count(),
                'readyOrdersCount' => $activeOrders->where('status', OrderLifecycle::READY)->count(),
                'scheduledOrdersCount' => $activeOrders->filter(fn (Order $order) => $order->scheduled_for !== null)->count(),
                'ordersTodayCount' => $ordersTodayCount,
                'liveMenuItemsCount' => (int) $restaurants->sum('live_menu_items_count'),
                'pausedMenuItemsCount' => (int) $restaurants->sum('paused_menu_items_count'),
                'restaurantsCount' => $restaurants->count(),
                'visibleRestaurantsCount' => $restaurants->where('is_visible', true)->count(),
                'hiddenRestaurantsCount' => $restaurants->where('is_visible', false)->count(),
                'pinnedDestinationsCount' => $activeOrders
                    ->filter(fn (Order $order) => $order->delivery_latitude !== null && $order->delivery_longitude !== null)
                    ->count(),
            ],
            'recentOrders' => $activeOrders
                ->take(4)
                ->map(fn (Order $order) => $this->transformDashboardOrder($order))
                ->values(),
            'recentMenuItems' => $recentMenuItems
                ->map(fn (MenuItem $menuItem) => $this->transformDashboardMenuItem($menuItem))
                ->values(),
            'restaurants' => $restaurants
                ->map(fn (Restaurant $restaurant) => $this->transformDashboardRestaurant($restaurant))
                ->values(),
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function activeStatuses(): array
    {
        return OrderLifecycle::activeStatuses();
    }

    /**
     * @return array<int, string>
     */
    private function dashboardOrderRelations(): array
    {
        return [
            'restaurant:id,name',
            'user:id,name',
            'courier:id,name',
            'orderItems:id,order_id,name,quantity',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function transformDashboardOrder(Order $order): array
    {
        $fulfillmentType = $order->fulfillment_type ?? 'delivery';

        return [
            'id' => $order->id,
            'orderNumber' => '#BL-'.str_pad((string) $order->id, 4, '0', STR_PAD_LEFT),
            'restaurantName' => $order->restaurant->name,
            'customerName' => $order->user->name,
            'statusKey' => $order->status,
            'statusStageKey' => OrderLifecycle::normalize($order->status),
            'statusLabel' => OrderLifecycle::label($order->status, $fulfillmentType),
            'statusAccent' => OrderLifecycle::accent($order->status),
            'summary' => $order->orderItems
                ->map(fn ($item) => $item->quantity.'x '.$item->name)
                ->join(', '),
            'placedAt' => $order->placed_at?->diffForHumans() ?? 'Just now',
            'total' => $this->formatMoney($order->total),
            'fulfillmentType' => $fulfillmentType,
            'scheduledFor' => $order->scheduled_for?->diffForHumans(),
            'destinationShortLabel' => $fulfillmentType === 'pickup' ? 'Customer pickup' : $this->shortAddress($order->delivery_address),
            'destinationHasCoordinates' => $order->delivery_latitude !== null && $order->delivery_longitude !== null,
            'assignmentLabel' => $this->assignmentLabel($order),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function transformDashboardMenuItem(MenuItem $menuItem): array
    {
        return [
            'id' => $menuItem->id,
            'name' => $menuItem->name,
            'restaurantName' => $menuItem->restaurant->name,
            'category' => $menuItem->category,
            'price' => $this->formatMoney($menuItem->price),
            'availabilityLabel' => $menuItem->is_available ? 'Live' : 'Paused',
            'availabilityAccent' => $menuItem->is_available
                ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                : 'bg-slate-100 text-slate-700 ring-slate-200',
            'updatedAt' => $menuItem->updated_at?->diffForHumans() ?? 'Just now',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function transformDashboardRestaurant(Restaurant $restaurant): array
    {
        return [
            'id' => $restaurant->id,
            'name' => $restaurant->name,
            'slug' => $restaurant->slug,
            'category' => $restaurant->category,
            'cuisine' => $restaurant->cuisine,
            'featuredText' => $restaurant->featured_text,
            'ratingLabel' => number_format((float) $restaurant->rating, 1).' rating',
            'deliveryWindow' => $restaurant->min_delivery_time.'-'.$restaurant->max_delivery_time.' min',
            'deliveryFee' => $restaurant->delivery_fee > 0
                ? $this->formatMoney((int) $restaurant->delivery_fee).' delivery'
                : 'Free delivery',
            'menuItemsCount' => (int) $restaurant->menu_items_count,
            'liveMenuItemsCount' => (int) $restaurant->live_menu_items_count,
            'pausedMenuItemsCount' => (int) $restaurant->paused_menu_items_count,
            'activeOrdersCount' => (int) $restaurant->active_orders_count,
            'orderSettings' => $restaurant->normalizedOrderSettings(),
            'visibilityLabel' => $restaurant->is_visible ? 'Visible to customers' : 'Hidden from discovery',
            'visibilityAccent' => $restaurant->is_visible
                ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                : 'bg-slate-100 text-slate-700 ring-slate-200',
        ];
    }

    private function formatMoney(int $amount): string
    {
        return \App\Support\MoneyFormatter::format($amount);
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

    private function assignmentLabel(Order $order): string
    {
        if (($order->fulfillment_type ?? 'delivery') === 'pickup') {
            return 'Customer pickup';
        }

        if ($order->status === OrderLifecycle::LEGACY_ON_THE_WAY && $order->courier_id === null) {
            return 'Available to claim';
        }

        if ($order->courier) {
            return $order->courier->name;
        }

        return match (OrderLifecycle::normalize($order->status)) {
            OrderLifecycle::READY => 'Available to claim',
            OrderLifecycle::PICKED_UP => 'Courier assigned',
            default => 'Awaiting kitchen progress',
        };
    }
}