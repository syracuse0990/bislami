<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Customer\CartResource;
use App\Http\Resources\Api\CustomerOrderDetailResource;
use App\Http\Resources\Api\CustomerWorkspaceOrderResource;
use App\Models\Order;
use App\Support\CustomerCartService;
use App\Support\CustomerOrderListData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    public function index(Request $request, CustomerOrderListData $orderListData): AnonymousResourceCollection
    {
        $payload = $orderListData->paginatedFor(
            $request->user(),
            $request->string('status')->toString(),
        );

        return CustomerWorkspaceOrderResource::collection(collect($payload['orders']))
            ->additional([
                'meta' => [
                    'filters' => [
                        'status' => $payload['filter'],
                    ],
                    'overview' => $payload['overview'],
                    'pagination' => $payload['pagination'],
                ],
            ]);
    }

    public function show(Request $request, Order $order): CustomerOrderDetailResource
    {
        abort_unless($order->user_id === $request->user()->id && $order->status !== 'cart', 404);

        $order->loadMissing(['restaurant', 'orderItems']);

        return CustomerOrderDetailResource::make($order);
    }

    public function reorder(
        Request $request,
        Order $order,
        CustomerOrderListData $orderListData,
        CustomerCartService $cartService,
    ): CartResource|JsonResponse
    {
        abort_unless($order->user_id === $request->user()->id && $order->status !== 'cart', 404);
        abort_unless($orderListData->canReorder($order), 404);

        $order->load('restaurant:id,name');
        $cartContext = $cartService->context($request->user());

        if ($cartService->willReplaceCartForOrder($request->user(), $order) && ! $request->boolean('replace_cart')) {
            return $this->replaceCartConflictResponse(
                $cartContext,
                'Your cart already contains items from '.$cartContext['restaurantName'].'. Confirm replacing it before reordering '.$this->orderNumber($order).'.',
                [
                    'restaurantId' => (int) $order->restaurant_id,
                    'restaurantName' => $order->restaurant->name,
                    'orderId' => $order->id,
                    'orderNumber' => $this->orderNumber($order),
                ],
            );
        }

        $cartService->reorder($request->user(), $order);

        return CartResource::make($cartService->payload($request->user()));
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

    private function orderNumber(Order $order): string
    {
        return '#BL-'.str_pad((string) $order->id, 4, '0', STR_PAD_LEFT);
    }
}