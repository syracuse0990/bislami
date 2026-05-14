<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantOrderRejectRequest;
use App\Http\Requests\MerchantOrderUpdateRequest;
use App\Models\Order;
use App\Support\OrderLifecycle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class OrderOperationsPageController extends Controller
{
    public function courierOverview(Request $request): Response
    {
        $courierId = $request->user()->id;

        $deliveries = $this->courierVisibleOrders($courierId)
            ->get()
            ->map(fn (Order $order) => $this->transformOrder($order, $courierId))
            ->values();

        $assignedDeliveries = $deliveries
            ->filter(fn (array $delivery) => $delivery['assignment']['isAssignedToCurrentCourier'])
            ->values();

        $availableClaims = $deliveries
            ->filter(fn (array $delivery) => $delivery['assignment']['canClaim'])
            ->values();

        $pickupQueue = $deliveries
            ->filter(fn (array $delivery) => in_array(OrderLifecycle::normalize($delivery['statusKey']), [OrderLifecycle::PREPARING, OrderLifecycle::READY], true))
            ->values();

        $completedTodayCount = Order::query()
            ->where('courier_id', $courierId)
            ->where('status', OrderLifecycle::DELIVERED)
            ->whereDate('updated_at', today())
            ->count();

        return Inertia::render('Courier/Dashboard', [
            'overview' => [
                'activeRunsCount' => $assignedDeliveries->count(),
                'availableClaimsCount' => $availableClaims->count(),
                'pickupReadyCount' => $pickupQueue->count(),
                'mappedStopsCount' => $deliveries
                    ->filter(fn (array $delivery) => $delivery['destination']['hasCoordinates'])
                    ->count(),
                'completedTodayCount' => $completedTodayCount,
            ],
            'assignedDeliveries' => $assignedDeliveries,
            'availableClaims' => $availableClaims,
            'pickupQueue' => $pickupQueue,
        ]);
    }

    public function merchantQueue(Request $request): Response
    {
        $merchantRestaurantIds = $request->user()->managedRestaurants()->pluck('id');

        $baseQuery = Order::query()
            ->with($this->orderPreviewRelations())
            ->whereIn('status', $this->activeStatuses())
            ->whereIn('restaurant_id', $merchantRestaurantIds);

        $filters = [
            'restaurantId' => (int) $request->integer('restaurant_id'),
            'status' => $request->string('status')->toString() ?: 'all',
            'fulfillmentType' => $request->string('fulfillment_type')->toString() ?: 'all',
            'search' => trim($request->string('search')->toString()),
        ];

        $filteredQuery = $this->applyMerchantFilters(clone $baseQuery, $filters)
            ->latest('scheduled_for')
            ->latest('placed_at')
            ->latest('id');

        $allActiveOrders = (clone $baseQuery)->get();
        $queue = $filteredQuery
            ->get()
            ->map(fn (Order $order) => $this->transformOrder($order))
            ->values();

        $restaurants = $request->user()
            ->managedRestaurants()
            ->orderBy('name')
            ->get()
            ->map(fn ($restaurant) => [
                'id' => $restaurant->id,
                'name' => $restaurant->name,
                'orderSettings' => $restaurant->normalizedOrderSettings(),
            ])
            ->values();

        return Inertia::render('Merchant/Orders/Index', [
            'queue' => $queue,
            'filters' => $filters,
            'stats' => [
                'all' => $allActiveOrders->count(),
                'pending' => $allActiveOrders->where('status', OrderLifecycle::PENDING)->count(),
                'accepted' => $allActiveOrders->where('status', OrderLifecycle::ACCEPTED)->count(),
                'preparing' => $allActiveOrders->where('status', OrderLifecycle::PREPARING)->count(),
                'ready' => $allActiveOrders->where('status', OrderLifecycle::READY)->count(),
                'pickedUp' => $allActiveOrders->filter(fn (Order $order) => OrderLifecycle::normalize($order->status) === OrderLifecycle::PICKED_UP)->count(),
                'scheduled' => $allActiveOrders->filter(fn (Order $order) => $order->scheduled_for !== null)->count(),
            ],
            'restaurants' => $restaurants,
            'rejectionReasons' => $this->rejectionReasons(),
        ]);
    }

    public function merchantAccept(Request $request, Order $order): RedirectResponse
    {
        $this->abortUnlessMerchantOwnsOrder($request, $order);

        $this->transitionOrder($order, OrderLifecycle::ACCEPTED);

        return to_route('merchant.orders.index')->with('success', 'Order accepted.');
    }

    public function merchantReject(MerchantOrderRejectRequest $request, Order $order): RedirectResponse
    {
        $this->abortUnlessMerchantOwnsOrder($request, $order);

        $this->transitionOrder($order, OrderLifecycle::REJECTED, [
            'courier_id' => null,
            ...$request->validated(),
        ]);

        return to_route('merchant.orders.index')->with('warning', 'Order rejected.');
    }

    public function merchantUpdate(MerchantOrderUpdateRequest $request, Order $order): RedirectResponse
    {
        $this->abortUnlessMerchantOwnsOrder($request, $order);

        if (! OrderLifecycle::isMerchantEditable($order->status)) {
            return to_route('merchant.orders.index')->with('warning', 'Only pending or accepted orders can be modified.');
        }

        $attributes = $request->validated();

        if (($attributes['fulfillment_type'] ?? 'delivery') === 'pickup') {
            $attributes['delivery_address'] = null;
            $attributes['delivery_latitude'] = null;
            $attributes['delivery_longitude'] = null;
        }

        $order->update($attributes);

        return to_route('merchant.orders.index')->with('success', 'Order details updated.');
    }

    public function merchantStartPreparing(Request $request, Order $order): RedirectResponse
    {
        $this->abortUnlessMerchantOwnsOrder($request, $order);

        $this->transitionOrder($order, OrderLifecycle::PREPARING);

        return to_route('merchant.orders.index')->with('success', 'Order moved into preparation.');
    }

    public function merchantDispatch(Request $request, Order $order): RedirectResponse
    {
        $this->abortUnlessMerchantOwnsOrder($request, $order);

        $this->transitionOrder($order, OrderLifecycle::READY, [
            'courier_id' => null,
        ]);

        return to_route('merchant.orders.index')->with('success', 'Order marked ready.');
    }

    public function merchantCompletePickup(Request $request, Order $order): RedirectResponse
    {
        $this->abortUnlessMerchantOwnsOrder($request, $order);

        $this->transitionOrder($order, OrderLifecycle::DELIVERED);

        return to_route('merchant.orders.index')->with('success', 'Pickup completed.');
    }

    public function courierQueue(Request $request): Response
    {
        $courierId = $request->user()->id;

        $deliveries = $this->courierVisibleOrders($courierId)
            ->get()
            ->map(fn (Order $order) => $this->transformOrder($order, $courierId))
            ->values();

        return Inertia::render('Courier/Deliveries/Index', [
            'deliveries' => $deliveries,
        ]);
    }

    public function courierClaim(Request $request, Order $order): RedirectResponse
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

        return to_route('courier.deliveries.index');
    }

    public function courierComplete(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->courier_id === $request->user()->id, 404);

        if (! OrderLifecycle::isCourierCompletable($order->status, $order->fulfillment_type ?? 'delivery')) {
            return to_route('courier.deliveries.index');
        }

        $this->transitionOrder($order, OrderLifecycle::DELIVERED);

        return to_route('courier.deliveries.index');
    }

    public function adminOverview(): Response
    {
        $activeOrders = Order::query()
            ->with($this->orderPreviewRelations())
            ->whereIn('status', $this->activeStatuses())
            ->latest('placed_at')
            ->latest('id')
            ->get();

        return Inertia::render('Admin/Dashboard', [
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
            'dispatchBoard' => $activeOrders
                ->map(fn (Order $order) => $this->transformOrder($order))
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
     * @param  array{restaurantId:int,status:string,fulfillmentType:string,search:string}  $filters
     */
    private function applyMerchantFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['restaurantId'] > 0, fn (Builder $builder) => $builder->where('restaurant_id', $filters['restaurantId']))
            ->when($filters['status'] !== 'all', function (Builder $builder) use ($filters) {
                if ($filters['status'] === OrderLifecycle::PICKED_UP) {
                    return $builder->whereIn('status', [OrderLifecycle::PICKED_UP, OrderLifecycle::LEGACY_ON_THE_WAY]);
                }

                return $builder->where('status', $filters['status']);
            })
            ->when($filters['fulfillmentType'] !== 'all', fn (Builder $builder) => $builder->where('fulfillment_type', $filters['fulfillmentType']))
            ->when($filters['search'] !== '', function (Builder $builder) use ($filters) {
                $search = $filters['search'];

                $builder->where(function (Builder $nested) use ($search) {
                    if (is_numeric($search)) {
                        $nested->where('id', (int) $search)
                            ->orWhere('delivery_address', 'like', "%{$search}%")
                            ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('restaurant', fn (Builder $restaurantQuery) => $restaurantQuery->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('orderItems', fn (Builder $itemQuery) => $itemQuery->where('name', 'like', "%{$search}%"));

                        return;
                    }

                    $nested
                        ->where('delivery_address', 'like', "%{$search}%")
                        ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('restaurant', fn (Builder $restaurantQuery) => $restaurantQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('orderItems', fn (Builder $itemQuery) => $itemQuery->where('name', 'like', "%{$search}%"));
                });
            });
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
            'orderItems:id,order_id,name,quantity,unit_price,line_total',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function transformOrder(Order $order, ?int $currentUserId = null): array
    {
        $fulfillmentType = $order->fulfillment_type ?? 'delivery';
        $hasCoordinates = $order->delivery_latitude !== null && $order->delivery_longitude !== null;
        $isAssignedToCurrentCourier = $currentUserId !== null && $order->courier_id === $currentUserId;
        $canClaim = $currentUserId !== null
            && (OrderLifecycle::isCourierClaimable($order->status, $fulfillmentType)
                || ($order->status === OrderLifecycle::LEGACY_ON_THE_WAY && $fulfillmentType === 'delivery'))
            && $order->courier_id === null;
        $canComplete = $currentUserId !== null
            && OrderLifecycle::isCourierCompletable($order->status, $fulfillmentType)
            && $isAssignedToCurrentCourier;
        $normalizedStatus = OrderLifecycle::normalize($order->status);

        return [
            'id' => $order->id,
            'restaurantId' => $order->restaurant_id,
            'orderNumber' => $this->orderNumber($order),
            'restaurantName' => $order->restaurant->name,
            'customerName' => $order->user->name,
            'statusKey' => $order->status,
            'statusStageKey' => $normalizedStatus,
            'statusLabel' => OrderLifecycle::label($order->status, $fulfillmentType),
            'statusAccent' => OrderLifecycle::accent($order->status),
            'summary' => $order->orderItems
                ->map(fn ($item) => $item->quantity.'x '.$item->name)
                ->join(', '),
            'items' => $order->orderItems
                ->map(fn ($item) => [
                    'name' => $item->name,
                    'quantity' => (int) $item->quantity,
                    'unitPrice' => $this->formatMoney((int) $item->unit_price),
                    'unitPriceValue' => (int) $item->unit_price,
                    'lineTotal' => $this->formatMoney((int) $item->line_total),
                    'lineTotalValue' => (int) $item->line_total,
                ])
                ->values(),
            'placedAt' => $order->placed_at?->diffForHumans() ?? 'Just now',
            'placedAtDate' => $order->placed_at?->toDayDateTimeString() ?? 'Just now',
            'total' => $this->formatMoney($order->total),
            'paymentMethod' => $order->payment_method,
            'isScheduled' => $order->scheduled_for !== null,
            'scheduledFor' => $order->scheduled_for?->diffForHumans(),
            'scheduledForDate' => $order->scheduled_for?->toDayDateTimeString(),
            'scheduledForValue' => $order->scheduled_for?->format('Y-m-d\TH:i'),
            'fulfillment' => [
                'key' => $fulfillmentType,
                'label' => str($fulfillmentType)->title()->toString(),
            ],
            'notes' => [
                'customer' => $order->customer_notes,
                'delivery' => $order->driver_notes,
                'merchant' => $order->merchant_notes,
            ],
            'rejection' => [
                'code' => $order->rejection_reason_code,
                'label' => $this->rejectionReasonLabel($order->rejection_reason_code),
                'note' => $order->rejection_reason_note,
            ],
            'timeline' => $this->orderTimeline($order),
            'actions' => [
                'canAccept' => $normalizedStatus === OrderLifecycle::PENDING,
                'canReject' => in_array($normalizedStatus, [OrderLifecycle::PENDING, OrderLifecycle::ACCEPTED], true),
                'canEdit' => OrderLifecycle::isMerchantEditable($order->status),
                'canStartPreparing' => $normalizedStatus === OrderLifecycle::ACCEPTED,
                'canMarkReady' => $normalizedStatus === OrderLifecycle::PREPARING,
                'canCompletePickup' => $fulfillmentType === 'pickup' && $normalizedStatus === OrderLifecycle::READY,
            ],
            'assignment' => [
                'courierId' => $order->courier_id,
                'courierName' => $order->courier?->name,
                'label' => $this->assignmentLabel($order),
                'claimed' => $order->courier_id !== null,
                'isAssignedToCurrentCourier' => $isAssignedToCurrentCourier,
                'canClaim' => $canClaim,
                'canComplete' => $canComplete,
            ],
            'destination' => [
                'deliveryAddress' => $order->delivery_address,
                'address' => $order->delivery_address ?: ($fulfillmentType === 'pickup' ? 'Customer pickup at restaurant' : 'Address pending'),
                'shortLabel' => $this->shortAddress($order->delivery_address),
                'latitude' => $order->delivery_latitude,
                'longitude' => $order->delivery_longitude,
                'coordinatesLabel' => $hasCoordinates ? $order->delivery_latitude.', '.$order->delivery_longitude : null,
                'hasCoordinates' => $hasCoordinates,
                'mapsUrl' => $this->mapsUrl($order, $hasCoordinates),
            ],
        ];
    }

    private function orderNumber(Order $order): string
    {
        return '#BL-'.str_pad((string) $order->id, 4, '0', STR_PAD_LEFT);
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

    private function mapsUrl(Order $order, bool $hasCoordinates): ?string
    {
        if ($hasCoordinates) {
            return 'https://www.google.com/maps/search/?api=1&query='
                .rawurlencode($order->delivery_latitude.','.$order->delivery_longitude);
        }

        if (! $order->delivery_address) {
            return null;
        }

        return 'https://www.google.com/maps/search/?api=1&query='
            .rawurlencode($order->delivery_address);
    }

    private function formatMoney(int $amount): string
    {
        return \App\Support\MoneyFormatter::format($amount);
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

    /**
     * @return array<int, array<string, string|null>>
     */
    private function orderTimeline(Order $order): array
    {
        return collect([
            [
                'key' => 'placed',
                'label' => 'Placed',
                'relative' => $order->placed_at?->diffForHumans(),
                'date' => $order->placed_at?->toDayDateTimeString(),
            ],
            [
                'key' => 'scheduled',
                'label' => 'Scheduled',
                'relative' => $order->scheduled_for?->diffForHumans(),
                'date' => $order->scheduled_for?->toDayDateTimeString(),
            ],
            [
                'key' => 'accepted',
                'label' => 'Accepted',
                'relative' => $order->accepted_at?->diffForHumans(),
                'date' => $order->accepted_at?->toDayDateTimeString(),
            ],
            [
                'key' => 'preparing',
                'label' => 'Preparing',
                'relative' => $order->preparing_at?->diffForHumans(),
                'date' => $order->preparing_at?->toDayDateTimeString(),
            ],
            [
                'key' => 'ready',
                'label' => 'Ready',
                'relative' => $order->ready_at?->diffForHumans(),
                'date' => $order->ready_at?->toDayDateTimeString(),
            ],
            [
                'key' => 'picked_up',
                'label' => 'Picked up',
                'relative' => $order->picked_up_at?->diffForHumans(),
                'date' => $order->picked_up_at?->toDayDateTimeString(),
            ],
            [
                'key' => 'delivered',
                'label' => 'Delivered',
                'relative' => $order->delivered_at?->diffForHumans(),
                'date' => $order->delivered_at?->toDayDateTimeString(),
            ],
            [
                'key' => 'rejected',
                'label' => 'Rejected',
                'relative' => $order->rejected_at?->diffForHumans(),
                'date' => $order->rejected_at?->toDayDateTimeString(),
            ],
        ])
            ->filter(fn (array $entry) => $entry['date'] !== null)
            ->values()
            ->all();
    }

    private function rejectionReasonLabel(?string $code): ?string
    {
        if (! $code) {
            return null;
        }

        $matchedReason = collect($this->rejectionReasons())->firstWhere('value', $code);

        if ($matchedReason) {
            return $matchedReason['label'];
        }

        return str($code)->replace('_', ' ')->title()->toString();
    }

    private function abortUnlessMerchantOwnsOrder(Request $request, Order $order): void
    {
        abort_unless($order->restaurant()->where('user_id', $request->user()->id)->exists(), 404);
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function rejectionReasons(): array
    {
        return [
            ['value' => 'busy_capacity', 'label' => 'Busy capacity'],
            ['value' => 'item_unavailable', 'label' => 'Item unavailable'],
            ['value' => 'restaurant_closed', 'label' => 'Restaurant closed'],
            ['value' => 'customer_request', 'label' => 'Customer request'],
            ['value' => 'other', 'label' => 'Other'],
        ];
    }
}