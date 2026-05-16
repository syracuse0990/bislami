<?php

use App\Http\Controllers\Admin\AdminManagementPageController;
use App\Http\Controllers\Customer\CustomerPageController;
use App\Http\Controllers\FoodPageController;
use App\Http\Controllers\HomePageController;
use App\Http\Controllers\Merchant\MerchantActivityLogPageController;
use App\Http\Controllers\Merchant\MerchantDailyMenuController;
use App\Http\Controllers\Merchant\MerchantMenuPageController;
use App\Http\Controllers\Merchant\MerchantMenuItemController;
use App\Http\Controllers\Merchant\MerchantStaffController;
use App\Http\Controllers\Merchant\MerchantStaffPageController;
use App\Http\Controllers\Merchant\CashierOrderController;
use App\Http\Controllers\Merchant\CashierDashboardController;
use App\Http\Controllers\Merchant\CashierPosPageController;
use App\Http\Controllers\Merchant\MerchantWorkspacePageController;
use App\Http\Controllers\Merchant\MerchantMaintenanceController;
use App\Http\Controllers\Merchant\MerchantOrderSettingsController;
use App\Http\Controllers\Merchant\MerchantStoreController;
use App\Http\Controllers\Merchant\MerchantStorePageController;
use App\Http\Controllers\Operations\OrderOperationsPageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicCartController;
use App\Http\Controllers\RestaurantPageController;
use App\Http\Controllers\SeoAssetController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap.xml', [SeoAssetController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoAssetController::class, 'robots'])->name('robots');
Route::get('/', HomePageController::class)->name('home');
Route::get('/foods/{menuItem:slug}', [FoodPageController::class, 'show'])->name('foods.show');
Route::get('/restaurants', [RestaurantPageController::class, 'index'])->name('restaurants.index');
Route::get('/restaurants/{restaurant}', [RestaurantPageController::class, 'show'])->name('restaurants.show');
Route::get('/cart', [PublicCartController::class, 'index'])->name('cart.index');
Route::post('/menu-items/{menuItem}/cart', [PublicCartController::class, 'store'])->name('cart.store');
Route::post('/cart/items/{menuItem}/increment', [PublicCartController::class, 'increment'])->name('cart.items.increment');
Route::post('/cart/items/{menuItem}/decrement', [PublicCartController::class, 'decrement'])->name('cart.items.decrement');
Route::get('/checkout', [PublicCartController::class, 'checkout'])->name('checkout.index');

Route::middleware(['auth', 'active-user', 'verified'])->group(function () {
    Route::get('/dashboard', function (Request $request) {
        return redirect()->route($request->user()->homeRouteName());
    })->name('dashboard');

    Route::middleware('role:customer')->prefix('customer')->name('customer.')->group(function () {
        Route::get('/', [CustomerPageController::class, 'dashboard'])->name('dashboard');

        Route::get('/restaurants', [CustomerPageController::class, 'restaurants'])->name('restaurants.index');
        Route::get('/restaurants/{restaurant}', [CustomerPageController::class, 'showRestaurant'])->name('restaurants.show');
        Route::post('/menu-items/{menuItem}/cart', [CustomerPageController::class, 'storeCart'])->name('cart.store');
        Route::post('/cart/items/{menuItem}/increment', [CustomerPageController::class, 'incrementCartItem'])->name('cart.items.increment');
        Route::post('/cart/items/{menuItem}/decrement', [CustomerPageController::class, 'decrementCartItem'])->name('cart.items.decrement');
        Route::get('/cart', [CustomerPageController::class, 'cart'])->name('cart.index');
        Route::get('/checkout', [CustomerPageController::class, 'checkout'])->name('checkout.index');
        Route::post('/checkout/place', [CustomerPageController::class, 'placeOrder'])->name('checkout.place');
        Route::get('/orders', [CustomerPageController::class, 'orders'])->name('orders.index');
        Route::post('/orders/{order}/reorder', [CustomerPageController::class, 'reorder'])->name('orders.reorder');
        Route::get('/orders/{order}', [CustomerPageController::class, 'showOrder'])->name('orders.show');
    });

    Route::middleware('role:merchant')->prefix('merchant')->name('merchant.')->group(function () {
        Route::get('/', MerchantWorkspacePageController::class)->name('dashboard');

        Route::middleware('approved-merchant')->group(function () {
            Route::get('/profile', [MerchantStorePageController::class, 'show'])->name('profile.show');
            Route::put('/profile', [MerchantStoreController::class, 'upsert'])->name('profile.update');

            Route::get('/menu', [MerchantMenuPageController::class, 'index'])->name('menu.index');
            Route::get('/menu/daily', [MerchantDailyMenuController::class, 'index'])->name('menu.daily.index');
            Route::put('/menu/daily', [MerchantDailyMenuController::class, 'bulkUpdate'])->name('menu.daily.save');
            Route::get('/menu/create', [MerchantMenuPageController::class, 'create'])->name('menu.create');
            Route::get('/menu/{menuItem}/edit', [MerchantMenuPageController::class, 'edit'])->name('menu.edit');
            Route::post('/menu', [MerchantMenuItemController::class, 'store'])->name('menu.store');
            Route::patch('/menu/{menuItem}', [MerchantMenuItemController::class, 'update'])->name('menu.update');
            Route::patch('/menu/{menuItem}/availability', [MerchantMenuItemController::class, 'toggleAvailability'])->name('menu.availability');
            Route::delete('/menu/{menuItem}', [MerchantMenuItemController::class, 'destroy'])->name('menu.destroy');

            Route::get('/staff', [MerchantStaffPageController::class, 'index'])->name('staff.index');
            Route::post('/staff', [MerchantStaffController::class, 'store'])->name('staff.store');
            Route::put('/staff/{staff}', [MerchantStaffController::class, 'update'])->name('staff.update');
            Route::delete('/staff/{staff}', [MerchantStaffController::class, 'destroy'])->name('staff.destroy');
            Route::get('/staff/activity', [MerchantActivityLogPageController::class, 'index'])->name('staff.activity');

            Route::get('/orders', [OrderOperationsPageController::class, 'merchantQueue'])->name('orders.index');
            Route::patch('/orders/{order}', [OrderOperationsPageController::class, 'merchantUpdate'])->name('orders.update');
            Route::post('/orders/{order}/accept', [OrderOperationsPageController::class, 'merchantAccept'])->name('orders.accept');
            Route::post('/orders/{order}/reject', [OrderOperationsPageController::class, 'merchantReject'])->name('orders.reject');
            Route::post('/orders/{order}/start-preparing', [OrderOperationsPageController::class, 'merchantStartPreparing'])->name('orders.start-preparing');
            Route::post('/orders/{order}/dispatch', [OrderOperationsPageController::class, 'merchantDispatch'])->name('orders.dispatch');
            Route::post('/orders/{order}/complete-pickup', [OrderOperationsPageController::class, 'merchantCompletePickup'])->name('orders.complete-pickup');
            Route::patch('/restaurants/{restaurant}/order-settings', [MerchantOrderSettingsController::class, 'update'])->name('restaurants.order-settings.update');

            Route::get('/cashier', CashierDashboardController::class)->name('cashier.dashboard');
            Route::get('/cashier/pos', CashierPosPageController::class)->name('cashier.pos');
            Route::post('/cashier/orders', [CashierOrderController::class, 'store'])->name('cashier.orders.store');

            Route::get('/maintenance', [MerchantMaintenanceController::class, 'index'])->name('maintenance.index');
            Route::put('/maintenance', [MerchantMaintenanceController::class, 'update'])->name('maintenance.update');
            Route::put('/maintenance/floor-plan', [MerchantMaintenanceController::class, 'updateFloorPlan'])->name('maintenance.floor-plan');
        });
    });

    Route::middleware('role:courier')->prefix('courier')->name('courier.')->group(function () {
        Route::get('/', [OrderOperationsPageController::class, 'courierOverview'])->name('dashboard');

        Route::get('/deliveries', [OrderOperationsPageController::class, 'courierQueue'])->name('deliveries.index');
        Route::post('/deliveries/{order}/claim', [OrderOperationsPageController::class, 'courierClaim'])->name('deliveries.claim');
        Route::post('/deliveries/{order}/complete', [OrderOperationsPageController::class, 'courierComplete'])->name('deliveries.complete');
    });

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [OrderOperationsPageController::class, 'adminOverview'])->name('dashboard');

        Route::get('/restaurants', [AdminManagementPageController::class, 'restaurantsIndex'])->name('restaurants.index');
        Route::get('/restaurants/{restaurant}', [AdminManagementPageController::class, 'restaurantsShow'])->name('restaurants.show');
        Route::post('/restaurants/{restaurant}/hide', [AdminManagementPageController::class, 'hideRestaurant'])->name('restaurants.hide');
        Route::post('/restaurants/{restaurant}/reveal', [AdminManagementPageController::class, 'revealRestaurant'])->name('restaurants.reveal');
        Route::get('/users', [AdminManagementPageController::class, 'usersIndex'])->name('users.index');
        Route::get('/users/{user}', [AdminManagementPageController::class, 'usersShow'])->name('users.show');
        Route::post('/users/{user}/suspend', [AdminManagementPageController::class, 'suspendUser'])->name('users.suspend');
        Route::post('/users/{user}/restore', [AdminManagementPageController::class, 'restoreUser'])->name('users.restore');
        Route::post('/users/{user}/approve-merchant', [AdminManagementPageController::class, 'approveMerchant'])->name('users.approve-merchant');
        Route::post('/users/{user}/revoke-merchant-approval', [AdminManagementPageController::class, 'revokeMerchantApproval'])->name('users.revoke-merchant-approval');
    });
});

Route::middleware(['auth', 'active-user'])->group(function () {
    Route::redirect('/profile', '/customer/settings');

    Route::get('/customer/settings', [ProfileController::class, 'edit'])->name('customer.settings.edit');
    Route::patch('/customer/settings', [ProfileController::class, 'update'])->name('customer.settings.update');
    Route::delete('/customer/settings', [ProfileController::class, 'destroy'])->name('customer.settings.destroy');
});

require __DIR__.'/auth.php';
