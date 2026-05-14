<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerCheckoutRequest;
use App\Http\Resources\Api\CheckoutSessionResource;
use App\Http\Resources\Api\OperationsOrderResource;
use App\Models\Order;
use App\Support\OrderLifecycle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function show(Request $request): CheckoutSessionResource
    {
        $cartOrder = $this->currentCartOrder($request)->loadMissing(['restaurant', 'orderItems']);
        $cartOrder = $cartOrder ? $this->ensureOrderIdempotencyKey($cartOrder) : null;

        return CheckoutSessionResource::make([
            'cartOrderId' => $cartOrder?->id,
            'restaurant' => $cartOrder?->restaurant?->name,
            'summary' => $cartOrder ? $cartOrder->orderItems
                ->map(fn ($item) => $item->quantity.'x '.$item->name)
                ->join(', ') : null,
            'fulfillmentType' => $cartOrder?->fulfillment_type ?? 'delivery',
            'deliveryAddress' => $cartOrder?->delivery_address ?? '',
            'deliveryLatitude' => $cartOrder?->delivery_latitude,
            'deliveryLongitude' => $cartOrder?->delivery_longitude,
            'idempotencyKey' => $cartOrder?->idempotency_key,
            'paymentMethod' => $cartOrder?->payment_method ?? '',
            'driverNotes' => $cartOrder?->driver_notes ?? '',
            'customerNotes' => $cartOrder?->customer_notes ?? '',
            'scheduledFor' => $cartOrder?->scheduled_for?->toIso8601String(),
            'totalValue' => (int) ($cartOrder?->total ?? 0),
            'totalFormatted' => \App\Support\MoneyFormatter::format((int) ($cartOrder?->total ?? 0)),
        ]);
    }

    public function store(CustomerCheckoutRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $order = DB::transaction(function () use ($request, $validated) {
            $cartOrder = $request->user()
                ->orders()
                ->with(['restaurant', 'orderItems.menuItem', 'courier'])
                ->where('status', 'cart')
                ->latest('updated_at')
                ->lockForUpdate()
                ->first();

            if (! $cartOrder) {
                return $request->user()
                    ->orders()
                    ->with(['restaurant', 'orderItems', 'courier'])
                    ->where('idempotency_key', $validated['idempotency_key'])
                    ->where('status', '!=', 'cart')
                    ->latest('id')
                    ->first();
            }

            $cartOrder = $this->ensureOrderIdempotencyKey($cartOrder);

            if ($cartOrder->idempotency_key !== $validated['idempotency_key']) {
                $existingOrder = $request->user()
                    ->orders()
                    ->with(['restaurant', 'orderItems', 'courier'])
                    ->where('idempotency_key', $validated['idempotency_key'])
                    ->where('status', '!=', 'cart')
                    ->latest('id')
                    ->first();

                if ($existingOrder) {
                    return $existingOrder;
                }

                throw ValidationException::withMessages([
                    'checkout' => 'Your checkout session expired. Refresh the cart and place the order again.',
                ]);
            }

            unset($validated['idempotency_key']);

            if ($this->cartContainsUnavailableItems($cartOrder)) {
                if ($cartOrder->restaurant->autoRejectsUnavailableItems()) {
                    $cartOrder->update([
                        ...$validated,
                        'status' => OrderLifecycle::REJECTED,
                        'placed_at' => now(),
                        'rejection_reason_code' => 'item_unavailable',
                        'rejection_reason_note' => 'One or more dishes were unavailable when the merchant received the order.',
                        'rejected_at' => now(),
                    ]);

                    return $cartOrder->fresh(['restaurant', 'orderItems', 'courier']);
                }

                throw ValidationException::withMessages([
                    'checkout' => 'One or more dishes are no longer available. Refresh the cart and try again.',
                ]);
            }

            $initialStatus = $cartOrder->restaurant->autoAcceptsOrders()
                ? OrderLifecycle::ACCEPTED
                : OrderLifecycle::PENDING;

            $cartOrder->update([
                ...$validated,
                'status' => $initialStatus,
                'placed_at' => now(),
                'accepted_at' => $initialStatus === OrderLifecycle::ACCEPTED ? now() : null,
                'rejection_reason_code' => null,
                'rejection_reason_note' => null,
                'rejected_at' => null,
            ]);

            return $cartOrder->fresh(['restaurant', 'orderItems', 'courier']);
        });

        if (! $order) {
            return response()->json([
                'message' => 'No active cart was found for checkout.',
            ], 404);
        }

        return OperationsOrderResource::make($order)
            ->response()
            ->setStatusCode(200);
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

    private function cartContainsUnavailableItems(Order $order): bool
    {
        return $order->orderItems
            ->contains(fn ($item) => $item->menuItem && ! $item->menuItem->is_available);
    }
}