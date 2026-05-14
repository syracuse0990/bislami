<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminManagementPageController extends Controller
{
    public function usersIndex(Request $request): Response
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'role' => $request->string('role')->toString(),
        ];

        $users = User::query()
            ->withCount([
                'managedRestaurants',
                'orders',
                'assignedDeliveries',
                'assignedDeliveries as active_assigned_deliveries_count' => fn (Builder $query) => $query
                    ->whereIn('status', $this->activeOrderStatuses()),
            ])
            ->when($filters['search'] !== '', function (Builder $query) use ($filters) {
                $search = '%'.$filters['search'].'%';

                $query->where(function (Builder $innerQuery) use ($search) {
                    $innerQuery
                        ->where('name', 'like', $search)
                        ->orWhere('email', 'like', $search);
                });
            })
            ->when($filters['role'] !== '', fn (Builder $query) => $query->where('role', $filters['role']))
            ->latest('id')
            ->paginate(8)
            ->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'overview' => $this->userOverview(),
            'roles' => ['customer', 'merchant', 'courier', 'admin'],
            'filters' => $filters,
            'users' => $users->getCollection()
                ->map(fn (User $user) => $this->transformUser($user, $request->user()))
                ->values(),
            'usersPagination' => $this->transformPagination($users),
        ]);
    }

    public function usersShow(Request $request, User $user): Response
    {
        $user->loadCount([
            'managedRestaurants',
            'orders',
            'assignedDeliveries',
            'assignedDeliveries as active_assigned_deliveries_count' => fn (Builder $query) => $query
                ->whereIn('status', $this->activeOrderStatuses()),
        ]);

        $user->load([
            'managedRestaurants' => fn ($query) => $query
                ->withCount([
                    'menuItems',
                    'menuItems as available_menu_items_count' => fn (Builder $menuQuery) => $menuQuery->where('is_available', true),
                    'orders as active_orders_count' => fn (Builder $orderQuery) => $orderQuery->whereIn('status', $this->activeOrderStatuses()),
                ])
                ->latest('id')
                ->limit(6),
            'orders' => fn ($query) => $query
                ->with([
                    'restaurant:id,name,slug',
                    'orderItems:id,order_id,name,quantity',
                ])
                ->latest('placed_at')
                ->latest('id')
                ->limit(6),
            'assignedDeliveries' => fn ($query) => $query
                ->with([
                    'restaurant:id,name,slug',
                    'user:id,name',
                    'orderItems:id,order_id,name,quantity',
                ])
                ->latest('placed_at')
                ->latest('id')
                ->limit(6),
        ]);

        return Inertia::render('Admin/Users/Show', [
            'user' => $this->transformUser($user, $request->user()),
            'managedRestaurants' => $user->managedRestaurants
                ->map(fn (Restaurant $restaurant) => $this->transformRestaurantPreview($restaurant))
                ->values(),
            'recentOrders' => $user->orders
                ->map(fn (Order $order) => $this->transformOrderPreview($order))
                ->values(),
            'assignedDeliveries' => $user->assignedDeliveries
                ->map(fn (Order $order) => $this->transformOrderPreview($order))
                ->values(),
        ]);
    }

    public function suspendUser(Request $request, User $user): RedirectResponse
    {
        if ($request->user()?->is($user)) {
            return back()->with('error', 'You cannot suspend your own admin account.');
        }

        if ($user->isSuspended()) {
            return back()->with('success', $user->name.' is already suspended.');
        }

        $user->update([
            'is_suspended' => true,
            'suspended_at' => now(),
        ]);

        return back()->with('success', $user->name.' has been suspended across web and API access.');
    }

    public function restoreUser(User $user): RedirectResponse
    {
        if (! $user->isSuspended()) {
            return back()->with('success', $user->name.' already has active access.');
        }

        $user->update([
            'is_suspended' => false,
            'suspended_at' => null,
        ]);

        return back()->with('success', $user->name.' access has been restored.');
    }

    public function approveMerchant(User $user): RedirectResponse
    {
        if ($user->role !== 'merchant') {
            return back()->with('error', 'Only merchant accounts can be approved.');
        }

        if ($user->isMerchantVerified()) {
            return back()->with('success', $user->name.' is already merchant-approved.');
        }

        $user->update([
            'merchant_verified_at' => now(),
        ]);

        return back()->with('success', $user->name.' has been marked as a verified merchant.');
    }

    public function revokeMerchantApproval(User $user): RedirectResponse
    {
        if ($user->role !== 'merchant') {
            return back()->with('error', 'Only merchant accounts have approval status.');
        }

        if (! $user->isMerchantVerified()) {
            return back()->with('success', $user->name.' is already awaiting merchant approval.');
        }

        $user->update([
            'merchant_verified_at' => null,
        ]);

        return back()->with('success', $user->name.' merchant approval has been revoked.');
    }

    public function restaurantsIndex(Request $request): Response
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'category' => $request->string('category')->toString(),
            'merchant' => $request->string('merchant')->toString(),
        ];

        $restaurants = Restaurant::query()
            ->with('merchant:id,name,email')
            ->withCount([
                'menuItems',
                'menuItems as available_menu_items_count' => fn (Builder $query) => $query->where('is_available', true),
                'orders as active_orders_count' => fn (Builder $query) => $query
                    ->whereIn('status', $this->activeOrderStatuses()),
            ])
            ->when($filters['search'] !== '', function (Builder $query) use ($filters) {
                $search = '%'.$filters['search'].'%';

                $query->where(function (Builder $innerQuery) use ($search) {
                    $innerQuery
                        ->where('name', 'like', $search)
                        ->orWhere('slug', 'like', $search)
                        ->orWhere('category', 'like', $search)
                        ->orWhere('cuisine', 'like', $search)
                        ->orWhere('featured_text', 'like', $search)
                        ->orWhereHas('merchant', fn (Builder $merchantQuery) => $merchantQuery
                            ->where('name', 'like', $search)
                            ->orWhere('email', 'like', $search));
                });
            })
            ->when($filters['category'] !== '', fn (Builder $query) => $query->where('category', $filters['category']))
            ->when($filters['merchant'] !== '', fn (Builder $query) => $query->whereHas('merchant', fn (Builder $merchantQuery) => $merchantQuery
                ->where('name', 'like', '%'.$filters['merchant'].'%')
                ->orWhere('email', 'like', '%'.$filters['merchant'].'%')))
            ->latest('id')
            ->paginate(8)
            ->withQueryString();

        return Inertia::render('Admin/Restaurants/Index', [
            'overview' => $this->restaurantOverview(),
            'categories' => Restaurant::query()
                ->orderBy('category')
                ->pluck('category')
                ->filter()
                ->unique()
                ->values()
                ->all(),
            'filters' => $filters,
            'restaurants' => $restaurants->getCollection()
                ->map(fn (Restaurant $restaurant) => $this->transformRestaurant($restaurant))
                ->values(),
            'restaurantsPagination' => $this->transformPagination($restaurants),
        ]);
    }

    public function hideRestaurant(Restaurant $restaurant): RedirectResponse
    {
        if (! $restaurant->is_visible) {
            return back()->with('success', $restaurant->name.' is already hidden from discovery.');
        }

        $restaurant->update([
            'is_visible' => false,
        ]);

        return back()->with('success', $restaurant->name.' is now hidden from public and customer discovery.');
    }

    public function revealRestaurant(Restaurant $restaurant): RedirectResponse
    {
        if ($restaurant->is_visible) {
            return back()->with('success', $restaurant->name.' is already visible in discovery.');
        }

        $restaurant->update([
            'is_visible' => true,
        ]);

        return back()->with('success', $restaurant->name.' is visible in public and customer discovery again.');
    }

    public function restaurantsShow(Restaurant $restaurant): Response
    {
        $restaurant->loadCount([
            'menuItems',
            'menuItems as available_menu_items_count' => fn (Builder $query) => $query->where('is_available', true),
            'orders as active_orders_count' => fn (Builder $query) => $query->whereIn('status', $this->activeOrderStatuses()),
        ]);

        $restaurant->load([
            'merchant:id,name,email,merchant_verified_at',
            'menuItems' => fn ($query) => $query
                ->latest('id')
                ->limit(8),
            'orders' => fn ($query) => $query
                ->whereIn('status', $this->activeOrderStatuses())
                ->with([
                    'user:id,name',
                    'courier:id,name',
                    'orderItems:id,order_id,name,quantity',
                ])
                ->latest('placed_at')
                ->latest('id')
                ->limit(6),
        ]);

        $restaurant->merchant?->loadCount('managedRestaurants');

        return Inertia::render('Admin/Restaurants/Show', [
            'restaurant' => $this->transformRestaurant($restaurant),
            'merchant' => $restaurant->merchant ? [
                'name' => $restaurant->merchant->name,
                'email' => $restaurant->merchant->email,
                'managedRestaurantsCount' => (int) $restaurant->merchant->managed_restaurants_count,
                'emailUrl' => 'mailto:'.$restaurant->merchant->email,
                'usersDetailUrl' => route('admin.users.show', $restaurant->merchant),
                'approvalLabel' => $restaurant->merchant->isMerchantVerified() ? 'Merchant approved' : 'Merchant awaiting approval',
            ] : null,
            'menuItems' => $restaurant->menuItems
                ->map(fn ($menuItem) => [
                    'id' => $menuItem->id,
                    'name' => $menuItem->name,
                    'slug' => $menuItem->slug,
                    'category' => $menuItem->category,
                    'description' => $menuItem->description,
                    'imageUrl' => $menuItem->imageUrl(),
                    'price' => $this->formatMoney((int) $menuItem->price),
                    'isAvailable' => (bool) $menuItem->is_available,
                    'statusLabel' => $menuItem->is_available ? 'Visible in public discovery' : 'Hidden from public browse',
                    'statusAccent' => $menuItem->is_available
                        ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                        : 'bg-orange-50 text-orange-700 ring-orange-200',
                    'updatedAt' => $menuItem->updated_at?->diffForHumans() ?? 'Recently updated',
                    'publicDetailUrl' => $menuItem->is_available && $restaurant->is_visible ? route('foods.show', $menuItem->slug) : null,
                ])
                ->values(),
            'activeOrders' => $restaurant->orders
                ->map(fn (Order $order) => $this->transformOrderPreview($order))
                ->values(),
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function userOverview(): array
    {
        $roleCounts = User::query()
            ->select('role', DB::raw('count(*) as aggregate'))
            ->groupBy('role')
            ->pluck('aggregate', 'role');

        return [
            'totalUsers' => (int) $roleCounts->sum(),
            'customerUsers' => (int) ($roleCounts['customer'] ?? 0),
            'merchantUsers' => (int) ($roleCounts['merchant'] ?? 0),
            'courierUsers' => (int) ($roleCounts['courier'] ?? 0),
            'adminUsers' => (int) ($roleCounts['admin'] ?? 0),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function restaurantOverview(): array
    {
        return [
            'totalRestaurants' => Restaurant::query()->count(),
            'activeMerchants' => Restaurant::query()
                ->whereNotNull('user_id')
                ->distinct('user_id')
                ->count('user_id'),
            'liveMenus' => Restaurant::query()
                ->whereHas('menuItems', fn (Builder $query) => $query->where('is_available', true))
                ->count(),
            'activeOrders' => Order::query()
                ->whereIn('status', $this->activeOrderStatuses())
                ->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function transformUser(User $user, ?User $actor = null): array
    {
        $isMerchant = $user->role === 'merchant';
        $isSelf = $actor?->is($user) ?? false;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'roleLabel' => str($user->role)->title()->toString(),
            'roleAccent' => $this->roleAccent($user->role),
            'joinedAt' => $user->created_at?->diffForHumans() ?? 'Recently added',
            'verificationLabel' => $user->email_verified_at ? 'Verified email' : 'Email pending verification',
            'accessLabel' => $user->isSuspended() ? 'Suspended access' : 'Active access',
            'accessAccent' => $user->isSuspended()
                ? 'bg-rose-50 text-rose-700 ring-rose-200'
                : 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'isSuspended' => $user->isSuspended(),
            'suspendedAt' => $user->suspended_at?->diffForHumans(),
            'merchantApprovalLabel' => $isMerchant
                ? ($user->isMerchantVerified() ? 'Merchant approved' : 'Merchant awaiting approval')
                : null,
            'merchantApprovalAccent' => $isMerchant
                ? ($user->isMerchantVerified()
                    ? 'bg-sky-50 text-sky-700 ring-sky-200'
                    : 'bg-orange-50 text-orange-700 ring-orange-200')
                : null,
            'merchantApprovedAt' => $user->merchant_verified_at?->diffForHumans(),
            'managedRestaurantsCount' => (int) $user->managed_restaurants_count,
            'ordersCount' => (int) $user->orders_count,
            'assignedDeliveriesCount' => (int) $user->assigned_deliveries_count,
            'activeAssignedDeliveriesCount' => (int) $user->active_assigned_deliveries_count,
            'workloadLabel' => $this->userWorkloadLabel($user),
            'emailUrl' => 'mailto:'.$user->email,
            'detailUrl' => route('admin.users.show', $user),
            'suspendUrl' => $isSelf ? null : route('admin.users.suspend', $user),
            'restoreUrl' => route('admin.users.restore', $user),
            'approveMerchantUrl' => $isMerchant ? route('admin.users.approve-merchant', $user) : null,
            'revokeMerchantApprovalUrl' => $isMerchant ? route('admin.users.revoke-merchant-approval', $user) : null,
            'canSuspend' => ! $isSelf && ! $user->isSuspended(),
            'canRestore' => $user->isSuspended(),
            'canApproveMerchant' => $isMerchant && ! $user->isMerchantVerified(),
            'canRevokeMerchantApproval' => $isMerchant && $user->isMerchantVerified(),
            'restaurantsIndexUrl' => $user->role === 'merchant'
                ? route('admin.restaurants.index', ['merchant' => $user->email])
                : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function transformRestaurant(Restaurant $restaurant): array
    {
        return [
            'id' => $restaurant->id,
            'name' => $restaurant->name,
            'slug' => $restaurant->slug,
            'category' => $restaurant->category,
            'cuisine' => $restaurant->cuisine,
            'ratingLabel' => number_format($restaurant->rating, 1).' rating',
            'deliveryFee' => \App\Support\MoneyFormatter::format((int) $restaurant->delivery_fee, 'Free delivery'),
            'featured' => $restaurant->featured_text,
            'merchantName' => $restaurant->merchant?->name,
            'merchantEmail' => $restaurant->merchant?->email,
            'menuItemsCount' => (int) $restaurant->menu_items_count,
            'availableMenuItemsCount' => (int) $restaurant->available_menu_items_count,
            'activeOrdersCount' => (int) $restaurant->active_orders_count,
            'statusLabel' => ! $restaurant->is_visible
                ? 'Hidden from discovery'
                : ($restaurant->available_menu_items_count > 0 ? 'Public menu live' : 'Menu still hidden'),
            'statusAccent' => ! $restaurant->is_visible
                ? 'bg-slate-100 text-slate-700 ring-slate-200'
                : ($restaurant->available_menu_items_count > 0
                    ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                    : 'bg-orange-50 text-orange-700 ring-orange-200'),
            'isVisible' => (bool) $restaurant->is_visible,
            'visibilityLabel' => $restaurant->is_visible ? 'Discovery visible' : 'Discovery hidden',
            'visibilityAccent' => $restaurant->is_visible
                ? 'bg-sky-50 text-sky-700 ring-sky-200'
                : 'bg-slate-100 text-slate-700 ring-slate-200',
            'updatedAt' => $restaurant->updated_at?->diffForHumans() ?? 'Recently updated',
            'detailUrl' => route('admin.restaurants.show', $restaurant),
            'hideUrl' => route('admin.restaurants.hide', $restaurant),
            'revealUrl' => route('admin.restaurants.reveal', $restaurant),
            'publicMenuUrl' => $restaurant->is_visible && $restaurant->available_menu_items_count > 0 ? route('restaurants.show', $restaurant) : null,
            'merchantMailUrl' => $restaurant->merchant?->email ? 'mailto:'.$restaurant->merchant->email : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function transformRestaurantPreview(Restaurant $restaurant): array
    {
        return [
            'id' => $restaurant->id,
            'name' => $restaurant->name,
            'slug' => $restaurant->slug,
            'category' => $restaurant->category,
            'cuisine' => $restaurant->cuisine,
            'availableMenuItemsCount' => (int) $restaurant->available_menu_items_count,
            'activeOrdersCount' => (int) $restaurant->active_orders_count,
            'statusLabel' => ! $restaurant->is_visible
                ? 'Hidden from discovery'
                : ($restaurant->available_menu_items_count > 0 ? 'Live menu' : 'Menu hidden'),
            'statusAccent' => ! $restaurant->is_visible
                ? 'bg-slate-100 text-slate-700 ring-slate-200'
                : ($restaurant->available_menu_items_count > 0
                    ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                    : 'bg-orange-50 text-orange-700 ring-orange-200'),
            'visibilityLabel' => $restaurant->is_visible ? 'Discovery visible' : 'Discovery hidden',
            'visibilityAccent' => $restaurant->is_visible
                ? 'bg-sky-50 text-sky-700 ring-sky-200'
                : 'bg-slate-100 text-slate-700 ring-slate-200',
            'detailUrl' => route('admin.restaurants.show', $restaurant),
            'publicMenuUrl' => $restaurant->is_visible && $restaurant->available_menu_items_count > 0 ? route('restaurants.show', $restaurant) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function transformOrderPreview(Order $order): array
    {
        return [
            'id' => $order->id,
            'orderNumber' => '#BL-'.str_pad((string) $order->id, 4, '0', STR_PAD_LEFT),
            'restaurantName' => $order->restaurant?->name,
            'restaurantUrl' => $order->restaurant ? route('admin.restaurants.show', $order->restaurant) : null,
            'customerName' => $order->user?->name,
            'statusLabel' => str($order->status)->replace('_', ' ')->title()->toString(),
            'statusAccent' => $this->orderStatusAccent($order->status),
            'total' => $this->formatMoney((int) $order->total),
            'placedAt' => $order->placed_at?->diffForHumans() ?? 'Just now',
            'summary' => $order->orderItems
                ->map(fn ($item) => $item->quantity.'x '.$item->name)
                ->join(', '),
            'courierName' => $order->courier?->name,
            'destinationShortLabel' => $this->shortAddress($order->delivery_address),
        ];
    }

    /**
     * @return array<string, int|null>
     */
    private function transformPagination(LengthAwarePaginator $paginator): array
    {
        return [
            'currentPage' => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
            'perPage' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function activeOrderStatuses(): array
    {
        return ['preparing', 'on_the_way'];
    }

    private function roleAccent(string $role): string
    {
        return match ($role) {
            'merchant' => 'bg-[var(--brand-orange)]/10 text-[var(--brand-orange-deep)] ring-[var(--brand-orange)]/20',
            'courier' => 'bg-sky-50 text-sky-700 ring-sky-200',
            'admin' => 'bg-[var(--brand-teal)]/10 text-[var(--brand-teal)] ring-[var(--brand-teal)]/20',
            default => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        };
    }

    private function orderStatusAccent(string $status): string
    {
        return match ($status) {
            'preparing' => 'bg-orange-50 text-orange-700 ring-orange-200',
            'on_the_way' => 'bg-sky-50 text-sky-700 ring-sky-200',
            'delivered' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            default => 'bg-slate-100 text-slate-700 ring-slate-200',
        };
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

    private function userWorkloadLabel(User $user): string
    {
        return match ($user->role) {
            'merchant' => $user->managed_restaurants_count.' restaurants managed',
            'courier' => $user->active_assigned_deliveries_count.' active deliveries assigned',
            'admin' => 'Platform administration access',
            default => $user->orders_count.' lifetime orders placed',
        };
    }
}