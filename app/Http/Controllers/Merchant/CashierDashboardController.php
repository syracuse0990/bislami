<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\RestaurantStaff;
use App\Support\MoneyFormatter;
use App\Support\OrderLifecycle;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CashierDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        // Resolve restaurant(s) — owner first, then staff assignment
        $restaurants = $user->managedRestaurants()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn ($r) => ['id' => $r->id, 'name' => $r->name]);

        if ($restaurants->isEmpty()) {
            $restaurants = $user->staffAssignments()
                ->with('restaurant:id,name')
                ->where('status', 'active')
                ->get()
                ->map(fn ($s) => $s->restaurant)
                ->filter()
                ->unique('id')
                ->map(fn ($r) => ['id' => $r->id, 'name' => $r->name])
                ->values();
        }

        abort_if($restaurants->isEmpty(), 403, 'No restaurant assigned.');

        $defaultRestaurantId = (int) ($request->query('restaurant_id', $restaurants->first()['id']));
        $currentRestaurant   = $restaurants->firstWhere('id', $defaultRestaurantId) ?? $restaurants->first();
        $restaurantId        = $currentRestaurant['id'];

        $today      = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();

        // ── Revenue stats ────────────────────────────────────────────────────
        $todayStats = Order::query()
            ->where('restaurant_id', $restaurantId)
            ->whereDate('placed_at', $today)
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(total), 0) as revenue')
            ->first();

        $monthStats = Order::query()
            ->where('restaurant_id', $restaurantId)
            ->where('placed_at', '>=', $monthStart)
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(total), 0) as revenue')
            ->first();

        $allTimeStats = Order::query()
            ->where('restaurant_id', $restaurantId)
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(total), 0) as revenue')
            ->first();

        // ── Active orders ────────────────────────────────────────────────────
        $activeStatuses  = [OrderLifecycle::PENDING, OrderLifecycle::ACCEPTED, OrderLifecycle::PREPARING, OrderLifecycle::READY];
        $activeOrderRows = Order::query()
            ->where('restaurant_id', $restaurantId)
            ->whereIn('status', $activeStatuses)
            ->orderByRaw("FIELD(status, 'pending', 'accepted', 'preparing', 'ready')")
            ->orderByDesc('placed_at')
            ->limit(30)
            ->get(['id', 'status', 'fulfillment_type', 'total', 'placed_at', 'customer_notes', 'payment_method'])
            ->map(fn (Order $o) => [
                'id'             => $o->id,
                'orderNumber'    => '#BL-'.str_pad((string) $o->id, 4, '0', STR_PAD_LEFT),
                'status'         => $o->status,
                'fulfillmentType'=> $o->fulfillment_type,
                'total'          => (int) $o->total,
                'totalFormatted' => MoneyFormatter::format((int) $o->total),
                'placedAt'       => $o->placed_at?->diffForHumans() ?? '—',
                'waitMinutes'    => $o->placed_at ? (int) now()->diffInMinutes($o->placed_at) : 0,
                'customerNotes'  => $o->customer_notes,
                'paymentMethod'  => $o->payment_method ?? 'unknown',
            ]);

        $orderCounts = [
            'pending'   => $activeOrderRows->where('status', OrderLifecycle::PENDING)->count(),
            'preparing' => $activeOrderRows->whereIn('status', [OrderLifecycle::ACCEPTED, OrderLifecycle::PREPARING])->count(),
            'ready'     => $activeOrderRows->where('status', OrderLifecycle::READY)->count(),
            'total'     => $activeOrderRows->count(),
        ];

        // ── Rider queue ──────────────────────────────────────────────────────
        $riderQueue = Order::query()
            ->where('restaurant_id', $restaurantId)
            ->where('fulfillment_type', 'delivery')
            ->whereIn('status', [OrderLifecycle::READY, OrderLifecycle::ACCEPTED, OrderLifecycle::PREPARING])
            ->orderByDesc('placed_at')
            ->get(['id', 'status', 'total', 'placed_at', 'customer_notes'])
            ->map(fn (Order $o) => [
                'id'            => $o->id,
                'orderNumber'   => '#BL-'.str_pad((string) $o->id, 4, '0', STR_PAD_LEFT),
                'status'        => $o->status,
                'totalFormatted'=> MoneyFormatter::format((int) $o->total),
                'placedAt'      => $o->placed_at?->format('h:i A') ?? '—',
                'waitMinutes'   => $o->placed_at ? (int) now()->diffInMinutes($o->placed_at) : 0,
                'customerNotes' => $o->customer_notes,
                'isDelayed'     => $o->placed_at ? now()->diffInMinutes($o->placed_at) > 30 : false,
            ])
            ->values();

        // ── Top selling items today ──────────────────────────────────────────
        $topItems = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.restaurant_id', $restaurantId)
            ->whereDate('orders.placed_at', $today)
            ->select(
                'order_items.name',
                DB::raw('SUM(order_items.quantity) as sold'),
                DB::raw('SUM(order_items.line_total) as revenue'),
            )
            ->groupBy('order_items.name')
            ->orderByDesc('sold')
            ->limit(10)
            ->get()
            ->map(fn ($item) => [
                'name'           => $item->name,
                'sold'           => (int) $item->sold,
                'revenueFormatted' => MoneyFormatter::format((int) $item->revenue),
            ]);

        // ── Problem / rejected orders today ──────────────────────────────────
        $problemOrders = Order::query()
            ->where('restaurant_id', $restaurantId)
            ->where('status', OrderLifecycle::REJECTED)
            ->whereDate('placed_at', $today)
            ->orderByDesc('placed_at')
            ->get(['id', 'total', 'placed_at', 'customer_notes', 'payment_method', 'rejection_reason_code', 'rejection_reason_note'])
            ->map(fn (Order $o) => [
                'id'            => $o->id,
                'orderNumber'   => '#BL-'.str_pad((string) $o->id, 4, '0', STR_PAD_LEFT),
                'totalFormatted'=> MoneyFormatter::format((int) $o->total),
                'placedAt'      => $o->placed_at?->format('h:i A') ?? '—',
                'paymentMethod' => $o->payment_method ?? 'unknown',
                'reason'        => $o->rejection_reason_note ?? $o->rejection_reason_code ?? 'No reason provided',
                'customerNotes' => $o->customer_notes,
            ])
            ->values();

        // ── Today's ledger ───────────────────────────────────────────────────
        $ledger = Order::query()
            ->where('restaurant_id', $restaurantId)
            ->whereDate('placed_at', $today)
            ->withCount('orderItems as item_count')
            ->orderByDesc('placed_at')
            ->orderByDesc('id')
            ->get(['id', 'fulfillment_type', 'payment_method', 'total', 'placed_at', 'customer_notes', 'status'])
            ->map(fn (Order $order) => [
                'id'             => $order->id,
                'orderNumber'    => '#BL-'.str_pad((string) $order->id, 4, '0', STR_PAD_LEFT),
                'placedAt'       => $order->placed_at?->format('h:i A') ?? '—',
                'fulfillmentType'=> $order->fulfillment_type,
                'paymentMethod'  => $order->payment_method ?? 'unknown',
                'total'          => (int) $order->total,
                'totalFormatted' => MoneyFormatter::format((int) $order->total),
                'itemCount'      => (int) $order->item_count,
                'customerNotes'  => $order->customer_notes,
                'status'         => $order->status,
            ])
            ->values();

        // ── Payment breakdown (EOD) ──────────────────────────────────────────
        $paymentBreakdown = Order::query()
            ->where('restaurant_id', $restaurantId)
            ->whereDate('placed_at', $today)
            ->whereNotNull('payment_method')
            ->groupBy('payment_method')
            ->selectRaw('payment_method, COUNT(*) as cnt, COALESCE(SUM(total), 0) as total')
            ->get()
            ->map(fn ($row) => [
                'method'         => $row->payment_method,
                'count'          => (int) $row->cnt,
                'total'          => (int) $row->total,
                'totalFormatted' => MoneyFormatter::format((int) $row->total),
            ])
            ->sortBy('method')
            ->values();

        // ── Shift info ───────────────────────────────────────────────────────
        $staffAssignment = $user->staffAssignments()
            ->where('restaurant_id', $restaurantId)
            ->where('status', 'active')
            ->first(['role', 'accepted_at']);

        $supervisor = RestaurantStaff::query()
            ->where('restaurant_id', $restaurantId)
            ->where('role', 'manager')
            ->where('status', 'active')
            ->with('user:id,name')
            ->first();

        return Inertia::render('Merchant/Cashier/Dashboard', [
            'restaurant'       => $currentRestaurant,
            'restaurants'      => $restaurants,
            'stats' => [
                'todayRevenue'           => (int) $todayStats->revenue,
                'todayRevenueFormatted'  => MoneyFormatter::format((int) $todayStats->revenue),
                'todayOrders'            => (int) $todayStats->cnt,
                'monthRevenue'           => (int) $monthStats->revenue,
                'monthRevenueFormatted'  => MoneyFormatter::format((int) $monthStats->revenue),
                'monthOrders'            => (int) $monthStats->cnt,
                'allTimeRevenue'         => (int) $allTimeStats->revenue,
                'allTimeRevenueFormatted'=> MoneyFormatter::format((int) $allTimeStats->revenue),
                'allTimeOrders'          => (int) $allTimeStats->cnt,
            ],
            'activeOrders' => [
                'counts' => $orderCounts,
                'list'   => $activeOrderRows->values(),
            ],
            'riderQueue'       => $riderQueue,
            'topItems'         => $topItems,
            'problemOrders'    => $problemOrders,
            'ledger'           => $ledger,
            'paymentBreakdown' => $paymentBreakdown,
            'shift' => [
                'cashierName'    => $user->name,
                'role'           => RestaurantStaff::ROLE_LABELS[$staffAssignment?->role ?? ''] ?? 'Staff',
                'supervisorName' => $supervisor?->user?->name ?? 'Not assigned',
                'date'           => now()->format('F d, Y'),
            ],
            'generatedAt'   => now()->format('M d, Y'),
            'generatedTime' => now()->format('h:i A'),
        ]);
    }
}

