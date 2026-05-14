<?php

namespace App\Http\Controllers\Api\Operations;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CourierWorkspaceResource;
use App\Http\Resources\Api\MerchantWorkspaceResource;
use App\Http\Resources\Api\OperationsOrderResource;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class OperationsController extends Controller
{
    public function merchantOverview(Request $request): MerchantWorkspaceResource
    {
        $restaurants = Restaurant::query()
            ->select([
                'id',
                'user_id',
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
            ])
            ->where('user_id', $request->user()->id)
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
                ->with($this->orderPreviewRelations())
                ->whereIn('restaurant_id', $restaurantIds)
                ->whereIn('status', $this->activeStatuses())
                ->latest('placed_at')
                ->latest('id')
                ->get();

        $recentMenuItems = $restaurantIds->isEmpty()
            ? collect()
            : MenuItem::query()
                ->select(['id', 'restaurant_id', 'name', 'category', 'image_path', 'price', 'is_available', 'updated_at'])
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

        return MerchantWorkspaceResource::make([
            'overview' => [
                'activeOrdersCount' => $activeOrders->count(),
                'preparingOrdersCount' => $activeOrders->where('status', 'preparing')->count(),
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
            'recentOrders' => $activeOrders->take(4)->values(),
            'recentMenuItems' => $recentMenuItems,
            'restaurants' => $restaurants,
        ]);
    }

    public function merchantQueue(Request $request): AnonymousResourceCollection
    {
        return OperationsOrderResource::collection(
            Order::query()
                ->with($this->orderPreviewRelations())
                ->whereIn('status', $this->activeStatuses())
                ->whereHas('restaurant', fn ($query) => $query->where('user_id', $request->user()->id))
                ->latest('placed_at')
                ->latest('id')
                ->get(),
        );
    }

    public function merchantDispatch(Request $request, Order $order): OperationsOrderResource
    {
        abort_unless($order->restaurant()->where('user_id', $request->user()->id)->exists(), 404);

        $this->transitionOrder($order, from: 'preparing', to: 'on_the_way', attributes: [
            'courier_id' => null,
        ]);

        return OperationsOrderResource::make($order->fresh($this->orderPreviewRelations()));
    }

    public function courierQueue(Request $request): AnonymousResourceCollection
    {
        $courierId = $request->user()->id;

        return OperationsOrderResource::collection(
            $this->courierVisibleOrders($courierId)->get(),
        );
    }

    public function courierOverview(Request $request): CourierWorkspaceResource
    {
        $courierId = $request->user()->id;

        $deliveries = $this->courierVisibleOrders($courierId)->get();

        $assignedDeliveries = $deliveries
            ->filter(fn (Order $delivery) => $delivery->courier_id === $courierId)
            ->values();

        $availableClaims = $deliveries
            ->filter(fn (Order $delivery) => $delivery->status === 'on_the_way' && $delivery->courier_id === null)
            ->values();

        $pickupQueue = $deliveries
            ->filter(fn (Order $delivery) => $delivery->status === 'preparing')
            ->values();

        $completedTodayCount = Order::query()
            ->where('courier_id', $courierId)
            ->where('status', 'delivered')
            ->whereDate('updated_at', today())
            ->count();

        return CourierWorkspaceResource::make([
            'overview' => [
                'activeRunsCount' => $assignedDeliveries->count(),
                'availableClaimsCount' => $availableClaims->count(),
                'pickupReadyCount' => $pickupQueue->count(),
                'mappedStopsCount' => $deliveries
                    ->filter(fn (Order $delivery) => $delivery->delivery_latitude !== null && $delivery->delivery_longitude !== null)
                    ->count(),
                'completedTodayCount' => $completedTodayCount,
            ],
            'assignedDeliveries' => $assignedDeliveries,
            'availableClaims' => $availableClaims,
            'pickupQueue' => $pickupQueue,
        ]);
    }

    public function courierClaim(Request $request, Order $order): OperationsOrderResource
    {
        DB::transaction(function () use ($request, $order) {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($lockedOrder->status !== 'on_the_way') {
                return;
            }

            if ($lockedOrder->courier_id === null) {
                $lockedOrder->update([
                    'courier_id' => $request->user()->id,
                ]);

                return;
            }

            abort_unless($lockedOrder->courier_id === $request->user()->id, 404);
        });

        return OperationsOrderResource::make($order->fresh($this->orderPreviewRelations()));
    }

    public function courierComplete(Request $request, Order $order): OperationsOrderResource
    {
        abort_unless($order->courier_id === $request->user()->id, 404);

        $this->transitionOrder($order, from: 'on_the_way', to: 'delivered');

        return OperationsOrderResource::make($order->fresh($this->orderPreviewRelations()));
    }

    public function adminOverview(): JsonResponse
    {
        $activeOrders = Order::query()
            ->with($this->orderPreviewRelations())
            ->whereIn('status', $this->activeStatuses())
            ->latest('placed_at')
            ->latest('id')
            ->get();

        return response()->json([
            'overview' => [
                'activeOrders' => $activeOrders->count(),
                'preparingOrders' => $activeOrders->where('status', 'preparing')->count(),
                'awaitingCourier' => $activeOrders
                    ->filter(fn (Order $order) => $order->status === 'on_the_way' && $order->courier_id === null)
                    ->count(),
                'claimedDeliveries' => $activeOrders->filter(fn (Order $order) => $order->courier_id !== null)->count(),
                'pinnedDestinations' => $activeOrders
                    ->filter(fn (Order $order) => $order->delivery_latitude !== null && $order->delivery_longitude !== null)
                    ->count(),
            ],
            'data' => OperationsOrderResource::collection($activeOrders)->resolve(),
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function activeStatuses(): array
    {
        return ['preparing', 'on_the_way'];
    }

    private function courierVisibleOrders(int $courierId)
    {
        return Order::query()
            ->with($this->orderPreviewRelations())
            ->where(function ($query) use ($courierId) {
                $query
                    ->where('status', 'preparing')
                    ->orWhere(function ($nestedQuery) use ($courierId) {
                        $nestedQuery
                            ->where('status', 'on_the_way')
                            ->where(function ($assignmentQuery) use ($courierId) {
                                $assignmentQuery
                                    ->whereNull('courier_id')
                                    ->orWhere('courier_id', $courierId);
                            });
                    });
            })
            ->latest('placed_at')
            ->latest('id');
    }

    /**
     * @return array<int, string>
     */
    private function orderPreviewRelations(): array
    {
        return [
            'restaurant:id,name,user_id',
            'user:id,name',
            'courier:id,name',
            'orderItems:id,order_id,name,quantity',
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function transitionOrder(Order $order, string $from, string $to, array $attributes = []): void
    {
        if ($order->status === $to || $order->status !== $from) {
            return;
        }

        $order->update([
            'status' => $to,
            ...$attributes,
        ]);
    }
}