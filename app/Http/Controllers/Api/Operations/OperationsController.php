<?php

namespace App\Http\Controllers\Api\Operations;

use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantOrderRejectRequest;
use App\Http\Requests\MerchantOrderUpdateRequest;
use App\Http\Resources\Api\CourierWorkspaceResource;
use App\Http\Resources\Api\MerchantWorkspaceResource;
use App\Http\Resources\Api\OperationsOrderResource;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Support\OrderLifecycle;
use Illuminate\Database\Eloquent\Builder;
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
                'order_settings',
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
                ->latest('scheduled_for')
                ->latest('placed_at')
                ->latest('id')
                ->get(),
        );
    }

    public function merchantAccept(Request $request, Order $order): OperationsOrderResource
    {
        $this->abortUnlessMerchantOwnsOrder($request, $order);

        $this->transitionOrder($order, OrderLifecycle::ACCEPTED);

        return OperationsOrderResource::make($order->fresh($this->orderPreviewRelations()));
    }

    public function merchantReject(MerchantOrderRejectRequest $request, Order $order): OperationsOrderResource
    {
        $this->abortUnlessMerchantOwnsOrder($request, $order);

        $this->transitionOrder($order, OrderLifecycle::REJECTED, [
            'courier_id' => null,
            ...$request->validated(),
        ]);

        return OperationsOrderResource::make($order->fresh($this->orderPreviewRelations()));
    }

    public function merchantUpdate(MerchantOrderUpdateRequest $request, Order $order): OperationsOrderResource
    {
        $this->abortUnlessMerchantOwnsOrder($request, $order);
        abort_if(! OrderLifecycle::isMerchantEditable($order->status), 422, 'Only pending or accepted orders can be modified.');

        $attributes = $request->validated();

        if (($attributes['fulfillment_type'] ?? 'delivery') === 'pickup') {
            $attributes['delivery_address'] = null;
            $attributes['delivery_latitude'] = null;
            $attributes['delivery_longitude'] = null;
        }

        $order->update($attributes);

        return OperationsOrderResource::make($order->fresh($this->orderPreviewRelations()));
    }

    public function merchantStartPreparing(Request $request, Order $order): OperationsOrderResource
    {
        $this->abortUnlessMerchantOwnsOrder($request, $order);

        $this->transitionOrder($order, OrderLifecycle::PREPARING);

        return OperationsOrderResource::make($order->fresh($this->orderPreviewRelations()));
    }

    public function merchantDispatch(Request $request, Order $order): OperationsOrderResource
    {
        $this->abortUnlessMerchantOwnsOrder($request, $order);

        $this->transitionOrder($order, OrderLifecycle::READY, [
            'courier_id' => null,
        ]);

        return OperationsOrderResource::make($order->fresh($this->orderPreviewRelations()));
    }

    public function merchantCompletePickup(Request $request, Order $order): OperationsOrderResource
    {
        $this->abortUnlessMerchantOwnsOrder($request, $order);

        $this->transitionOrder($order, OrderLifecycle::DELIVERED);

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
            ->filter(fn (Order $delivery) => $this->canCourierClaim($delivery))
            ->values();

        $pickupQueue = $deliveries
            ->filter(fn (Order $delivery) => in_array(OrderLifecycle::normalize($delivery->status), [OrderLifecycle::PREPARING, OrderLifecycle::READY], true))
            ->values();

        $completedTodayCount = Order::query()
            ->where('courier_id', $courierId)
            ->where('status', OrderLifecycle::DELIVERED)
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
            $normalizedStatus = OrderLifecycle::normalize($lockedOrder->status);

            if ($normalizedStatus === OrderLifecycle::PICKED_UP && $lockedOrder->courier_id === $request->user()->id) {
                return;
            }

            if ($lockedOrder->status === OrderLifecycle::LEGACY_ON_THE_WAY) {
                if ($lockedOrder->courier_id === null) {
                    $lockedOrder->update([
                        'courier_id' => $request->user()->id,
                    ]);

                    return;
                }

                abort_unless($lockedOrder->courier_id === $request->user()->id, 404);

                return;
            }

            if (! OrderLifecycle::isCourierClaimable($lockedOrder->status, $lockedOrder->fulfillment_type ?? 'delivery')) {
                return;
            }

            if ($lockedOrder->courier_id === null) {
                $lockedOrder->update([
                    'courier_id' => $request->user()->id,
                    'status' => OrderLifecycle::PICKED_UP,
                    'picked_up_at' => now(),
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

        if (! OrderLifecycle::isCourierCompletable($order->status, $order->fulfillment_type ?? 'delivery')) {
            return OperationsOrderResource::make($order->fresh($this->orderPreviewRelations()));
        }

        $this->transitionOrder($order, OrderLifecycle::DELIVERED);

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
                'preparingOrders' => $activeOrders
                    ->filter(fn (Order $order) => in_array(OrderLifecycle::normalize($order->status), [OrderLifecycle::ACCEPTED, OrderLifecycle::PREPARING], true))
                    ->count(),
                'awaitingCourier' => $activeOrders
                    ->filter(fn (Order $order) => ($order->fulfillment_type ?? 'delivery') === 'delivery'
                        && (OrderLifecycle::normalize($order->status) === OrderLifecycle::READY
                            || $order->status === OrderLifecycle::LEGACY_ON_THE_WAY)
                        && $order->courier_id === null)
                    ->count(),
                'claimedDeliveries' => $activeOrders
                    ->filter(fn (Order $order) => $order->courier_id !== null && OrderLifecycle::normalize($order->status) === OrderLifecycle::PICKED_UP)
                    ->count(),
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
        return OrderLifecycle::activeStatuses();
    }

    private function courierVisibleOrders(int $courierId): Builder
    {
        return Order::query()
            ->with($this->orderPreviewRelations())
            ->where('fulfillment_type', 'delivery')
            ->where(function ($query) use ($courierId) {
                $query
                    ->whereIn('status', [OrderLifecycle::PREPARING, OrderLifecycle::READY])
                    ->orWhere(function ($nestedQuery) use ($courierId) {
                        $nestedQuery
                            ->whereIn('status', [OrderLifecycle::PICKED_UP, OrderLifecycle::LEGACY_ON_THE_WAY])
                            ->where(function ($assignmentQuery) use ($courierId) {
                                $assignmentQuery
                                    ->whereNull('courier_id')
                                    ->orWhere('courier_id', $courierId);
                            });
                    });
            })
                        ->latest('scheduled_for')
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
    private function transitionOrder(Order $order, string $to, array $attributes = []): void
    {
        if ($order->status === $to) {
            return;
        }

        if (! OrderLifecycle::canTransition($order->status, $to, $order->fulfillment_type ?? 'delivery')) {
            return;
        }

        $timestampColumn = OrderLifecycle::timestampColumnFor($to);

        $payload = [
            'status' => $to,
            ...$attributes,
        ];

        if ($timestampColumn) {
            $payload[$timestampColumn] = now();
        }

        if ($to !== OrderLifecycle::REJECTED) {
            $payload['rejection_reason_code'] = null;
            $payload['rejection_reason_note'] = null;
            $payload['rejected_at'] = null;
        }

        $order->update($payload);
    }

    private function canCourierClaim(Order $order): bool
    {
        $fulfillmentType = $order->fulfillment_type ?? 'delivery';

        return $order->courier_id === null
            && (OrderLifecycle::isCourierClaimable($order->status, $fulfillmentType)
                || ($order->status === OrderLifecycle::LEGACY_ON_THE_WAY && $fulfillmentType === 'delivery'));
    }

    private function abortUnlessMerchantOwnsOrder(Request $request, Order $order): void
    {
        abort_unless($order->restaurant()->where('user_id', $request->user()->id)->exists(), 404);
    }
}