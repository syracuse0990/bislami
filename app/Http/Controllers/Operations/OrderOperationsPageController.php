<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use App\Models\Order;
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
            ->filter(fn (array $delivery) => $delivery['statusKey'] === 'preparing')
            ->values();

        $completedTodayCount = Order::query()
            ->where('courier_id', $courierId)
            ->where('status', 'delivered')
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

    public function merchantDispatch(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->restaurant()->where('user_id', $request->user()->id)->exists(), 404);

        $this->transitionOrder($order, from: 'preparing', to: 'on_the_way', attributes: [
            'courier_id' => null,
        ]);

        return to_route('merchant.orders.index');
    }

    public function courierClaim(Request $request, Order $order): RedirectResponse
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

        return to_route('courier.deliveries.index');
    }

    public function courierComplete(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->courier_id === $request->user()->id, 404);

        $this->transitionOrder($order, from: 'on_the_way', to: 'delivered');

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
                'preparingOrders' => $activeOrders->where('status', 'preparing')->count(),
                'awaitingCourier' => $activeOrders
                    ->filter(fn (Order $order) => $order->status === 'on_the_way' && $order->courier_id === null)
                    ->count(),
                'claimedDeliveries' => $activeOrders->filter(fn (Order $order) => $order->courier_id !== null)->count(),
                'pinnedDestinations' => $activeOrders
                    ->filter(fn (Order $order) => $order->delivery_latitude !== null && $order->delivery_longitude !== null)
                    ->count(),
            ],
            'dispatchBoard' => $activeOrders
                ->map(fn (Order $order) => $this->transformOrder($order))
                ->values(),
        ]);
    }

    public function merchantQueue(Request $request): Response
    {
        $queue = Order::query()
            ->with($this->orderPreviewRelations())
            ->whereIn('status', $this->activeStatuses())
            ->whereHas('restaurant', fn ($query) => $query->where('user_id', $request->user()->id))
            ->latest('placed_at')
            ->latest('id')
            ->get()
            ->map(fn (Order $order) => $this->transformOrder($order))
            ->values();

        return Inertia::render('Merchant/Orders/Index', [
            'queue' => $queue,
        ]);
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
     * @return array<string, mixed>
     */
    private function transformOrder(Order $order, ?int $currentUserId = null): array
    {
        $hasCoordinates = $order->delivery_latitude !== null && $order->delivery_longitude !== null;
        $isAssignedToCurrentCourier = $currentUserId !== null && $order->courier_id === $currentUserId;
        $canClaim = $currentUserId !== null && $order->status === 'on_the_way' && $order->courier_id === null;
        $canComplete = $currentUserId !== null && $order->status === 'on_the_way' && $isAssignedToCurrentCourier;

        return [
            'id' => $order->id,
            'orderNumber' => $this->orderNumber($order),
            'restaurantName' => $order->restaurant->name,
            'customerName' => $order->user->name,
            'statusKey' => $order->status,
            'statusLabel' => str($order->status)->replace('_', ' ')->title()->toString(),
            'statusAccent' => $this->statusAccent($order->status),
            'summary' => $order->orderItems
                ->map(fn ($item) => $item->quantity.'x '.$item->name)
                ->join(', '),
            'placedAt' => $order->placed_at?->diffForHumans() ?? 'Just now',
            'total' => $this->formatMoney($order->total),
            'driverNotes' => $order->driver_notes,
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
                'address' => $order->delivery_address ?: 'Address pending',
                'shortLabel' => $this->shortAddress($order->delivery_address),
                'latitude' => $order->delivery_latitude,
                'longitude' => $order->delivery_longitude,
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

    private function statusAccent(string $status): string
    {
        return match ($status) {
            'preparing' => 'bg-orange-50 text-orange-700 ring-orange-200',
            'on_the_way' => 'bg-sky-50 text-sky-700 ring-sky-200',
            default => 'bg-slate-100 text-slate-700 ring-slate-200',
        };
    }

    private function formatMoney(int $amount): string
    {
        return \App\Support\MoneyFormatter::format($amount);
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

    private function assignmentLabel(Order $order): string
    {
        if ($order->courier) {
            return $order->courier->name;
        }

        return $order->status === 'on_the_way'
            ? 'Available to claim'
            : 'Awaiting dispatch';
    }
}