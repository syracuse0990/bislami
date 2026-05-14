<?php

use App\Http\Controllers\Api\Auth\TokenController;
use App\Http\Controllers\Api\Customer\CartController;
use App\Http\Controllers\Api\Customer\CatalogController;
use App\Http\Controllers\Api\Customer\CheckoutController;
use App\Http\Controllers\Api\Customer\OrderController;
use App\Http\Controllers\Api\Customer\OverviewController;
use App\Http\Controllers\Api\Guest\DiscoveryController;
use App\Http\Controllers\Api\Operations\OperationsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/tokens', [TokenController::class, 'store'])->name('api.tokens.store');
    Route::prefix('discovery')->name('api.discovery.')->group(function () {
        Route::get('/', [DiscoveryController::class, 'index'])->name('index');
        Route::get('/restaurants', [DiscoveryController::class, 'restaurants'])->name('restaurants.index');
        Route::get('/restaurants/{restaurant}', [DiscoveryController::class, 'showRestaurant'])->name('restaurants.show');
        Route::get('/foods/{menuItem:slug}', [DiscoveryController::class, 'showFood'])->name('foods.show');
    });

    Route::middleware(['auth:sanctum', 'active-user'])->group(function () {
        Route::get('/me', function (Request $request) {
            return response()->json([
                'data' => [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'role' => $request->user()->role,
                    'merchantVerifiedAt' => $request->user()->merchant_verified_at?->toAtomString(),
                ],
            ]);
        })->name('api.me.show');

        Route::middleware('role:customer')->prefix('customer')->name('api.customer.')->group(function () {
            Route::get('/overview', OverviewController::class)->name('overview.show');
            Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
            Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
            Route::post('/orders/{order}/reorder', [OrderController::class, 'reorder'])->name('orders.reorder');
            Route::get('/restaurants', [CatalogController::class, 'index'])->name('restaurants.index');
            Route::get('/restaurants/{restaurant}', [CatalogController::class, 'show'])->name('restaurants.show');
            Route::get('/cart', [CartController::class, 'show'])->name('cart.show');
            Route::post('/menu-items/{menuItem}/cart', [CartController::class, 'store'])->name('cart.store');
            Route::post('/cart/items/{menuItem}/increment', [CartController::class, 'increment'])->name('cart.items.increment');
            Route::post('/cart/items/{menuItem}/decrement', [CartController::class, 'decrement'])->name('cart.items.decrement');
            Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
            Route::post('/checkout/place', [CheckoutController::class, 'store'])->name('checkout.store');
        });

        Route::middleware(['role:merchant', 'approved-merchant'])->prefix('merchant')->name('api.merchant.')->group(function () {
            Route::get('/overview', [OperationsController::class, 'merchantOverview'])->name('overview.show');
            Route::get('/orders', [OperationsController::class, 'merchantQueue'])->name('orders.index');
            Route::patch('/orders/{order}', [OperationsController::class, 'merchantUpdate'])->name('orders.update');
            Route::post('/orders/{order}/accept', [OperationsController::class, 'merchantAccept'])->name('orders.accept');
            Route::post('/orders/{order}/reject', [OperationsController::class, 'merchantReject'])->name('orders.reject');
            Route::post('/orders/{order}/start-preparing', [OperationsController::class, 'merchantStartPreparing'])->name('orders.start-preparing');
            Route::post('/orders/{order}/dispatch', [OperationsController::class, 'merchantDispatch'])->name('orders.dispatch');
            Route::post('/orders/{order}/complete-pickup', [OperationsController::class, 'merchantCompletePickup'])->name('orders.complete-pickup');
        });

        Route::middleware('role:courier')->prefix('courier')->name('api.courier.')->group(function () {
            Route::get('/overview', [OperationsController::class, 'courierOverview'])->name('overview.show');
            Route::get('/deliveries', [OperationsController::class, 'courierQueue'])->name('deliveries.index');
            Route::post('/deliveries/{order}/claim', [OperationsController::class, 'courierClaim'])->name('deliveries.claim');
            Route::post('/deliveries/{order}/complete', [OperationsController::class, 'courierComplete'])->name('deliveries.complete');
        });

        Route::middleware('role:admin')->prefix('admin')->name('api.admin.')->group(function () {
            Route::get('/dispatch', [OperationsController::class, 'adminOverview'])->name('dispatch.index');
        });
    });
});