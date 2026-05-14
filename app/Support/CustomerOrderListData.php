<?php

namespace App\Support;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CustomerOrderListData
{
    /**
     * @var array<int, Collection<int, MenuItem>>
     */
    private array $replacementCandidateCache = [];

    /**
     * @return array{filter: string, orders: array<int, array<string, mixed>>, overview: array<string, int>}
     */
    public function listFor(User $user, ?string $filter = null): array
    {
        $resolvedFilter = $this->resolveFilter($filter);

        return [
            'filter' => $resolvedFilter,
            'orders' => $this->applyFilter($this->baseQuery($user), $resolvedFilter)
                ->get()
                ->map(fn (Order $order) => $this->transformOrder($order))
                ->values()
                ->all(),
            'overview' => $this->overview($user),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function recentFor(User $user, int $limit = 3): array
    {
        return $this->baseQuery($user)
            ->limit($limit)
            ->get()
            ->map(fn (Order $order) => $this->transformOrder($order))
            ->values()
            ->all();
    }

    /**
     * @return array{filter: string, orders: array<int, array<string, mixed>>, pagination: array<string, int>, overview: array<string, int>}
     */
    public function paginatedFor(User $user, ?string $filter = null, int $perPage = 10): array
    {
        $resolvedFilter = $this->resolveFilter($filter);
        $query = $this->applyFilter($this->baseQuery($user), $resolvedFilter);
        /** @var LengthAwarePaginator $paginator */
        $paginator = $query->paginate($perPage)->withQueryString();

        return [
            'filter' => $resolvedFilter,
            'orders' => collect($paginator->items())
                ->map(fn (Order $order) => $this->transformOrder($order))
                ->values()
                ->all(),
            'pagination' => [
                'currentPage' => $paginator->currentPage(),
                'lastPage' => $paginator->lastPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'overview' => $this->overview($user),
        ];
    }

    public function canReorder(Order $order): bool
    {
        return (bool) data_get($this->reorderState($order), 'available', false);
    }

    /**
     * @return array{visible: bool, available: bool, label: ?string, description: ?string, items: array<int, array{name: string, reason: string, suggestions: array<int, array{id: int, slug: string, name: string, category: ?string, price: array{value: int, formatted: string}}>}>}
     */
    public function reorderState(Order $order): array
    {
        $order->loadMissing([
            'restaurant:id,is_visible',
            'orderItems.menuItem:id,restaurant_id,is_available,name,price,category',
        ]);

        if ($order->status !== 'delivered') {
            return [
                'visible' => false,
                'available' => false,
                'label' => null,
                'description' => null,
                'items' => [],
            ];
        }

        if (! $order->restaurant?->is_visible) {
            return [
                'visible' => true,
                'available' => false,
                'label' => 'Kitchen unavailable',
                'description' => 'This restaurant is not accepting orders right now, so BizLami cannot rebuild this meal automatically.',
                'items' => [],
            ];
        }

        if ($order->orderItems->isEmpty()) {
            return [
                'visible' => true,
                'available' => false,
                'label' => 'Changed since last order',
                'description' => 'This order no longer has repeatable line items saved, so rebuilding it automatically is not possible.',
                'items' => [],
            ];
        }

        $reorderIssues = $this->reorderIssues($order);

        if ($reorderIssues !== []) {
            return [
                'visible' => true,
                'available' => false,
                'label' => 'Changed since last order',
                'description' => 'Some items from this meal are no longer available, so BizLami cannot rebuild the full order with one tap.',
                'items' => $reorderIssues,
            ];
        }

        return [
            'visible' => true,
            'available' => true,
            'label' => 'Ready to reorder',
            'description' => 'Every item from this order is still available and can be rebuilt in your cart with one tap.',
            'items' => [],
        ];
    }

    /**
     * @return Collection<int, array{menuItem: MenuItem, quantity: int}>
     */
    public function reorderItems(Order $order): Collection
    {
        $order->loadMissing('orderItems.menuItem:id,restaurant_id,is_available,name,price,category');

        return $order->orderItems
            ->map(function (OrderItem $item) use ($order): ?array {
                $menuItem = $item->menuItem;

                if (! $menuItem || ! $menuItem->is_available || $menuItem->restaurant_id !== $order->restaurant_id) {
                    return null;
                }

                return [
                    'menuItem' => $menuItem,
                    'quantity' => (int) $item->quantity,
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * @return array<int, array{name: string, reason: string, suggestions: array<int, array{id: int, slug: string, name: string, category: ?string, price: array{value: int, formatted: string}}>}>
     */
    public function reorderIssues(Order $order): array
    {
        $order->loadMissing('orderItems.menuItem:id,restaurant_id,is_available,name,price,category');

        $replacementCandidates = $this->replacementCandidatesForRestaurant((int) $order->restaurant_id);

        return $order->orderItems
            ->map(function (OrderItem $item) use ($order, $replacementCandidates): ?array {
                $menuItem = $item->menuItem;

                if (! $menuItem) {
                    return [
                        'name' => $item->name,
                        'reason' => 'This dish is no longer listed on the kitchen menu.',
                        'suggestions' => $this->replacementSuggestions($item, $menuItem, $replacementCandidates),
                    ];
                }

                if ($menuItem->restaurant_id !== $order->restaurant_id) {
                    return [
                        'name' => $item->name,
                        'reason' => 'This dish is no longer part of the current kitchen menu.',
                        'suggestions' => $this->replacementSuggestions($item, $menuItem, $replacementCandidates),
                    ];
                }

                if (! $menuItem->is_available) {
                    return [
                        'name' => $item->name,
                        'reason' => 'This dish is temporarily unavailable right now.',
                        'suggestions' => $this->replacementSuggestions($item, $menuItem, $replacementCandidates),
                    ];
                }

                return null;
            })
            ->filter()
            ->values()
            ->all();
    }

    private function baseQuery(User $user): Builder
    {
        return Order::query()
            ->with([
                'restaurant:id,name,slug,is_visible',
                'orderItems.menuItem:id,restaurant_id,is_available,name,price,category',
            ])
            ->whereBelongsTo($user)
            ->where('status', '!=', 'cart')
            ->orderByDesc('placed_at')
            ->orderByDesc('id');
    }

    private function applyFilter(Builder $query, string $filter): Builder
    {
        return match ($filter) {
            'active' => $query->whereIn('status', ['preparing', 'on_the_way']),
            'history' => $query->whereIn('status', ['delivered', 'cancelled']),
            default => $query,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function transformOrder(Order $order): array
    {
        $reorder = $this->reorderState($order);

        return [
            'id' => $order->id,
            'orderNumber' => '#BL-'.str_pad((string) $order->id, 4, '0', STR_PAD_LEFT),
            'restaurant' => [
                'id' => $order->restaurant_id,
                'name' => $order->restaurant->name,
                'slug' => $order->restaurant->slug,
            ],
            'status' => [
                'key' => $order->status,
                'label' => str($order->status)->replace('_', ' ')->title()->toString(),
            ],
            'summary' => $order->orderItems
                ->map(fn ($item) => $item->quantity.'x '.$item->name)
                ->join(', '),
            'placedAt' => $order->placed_at?->toIso8601String(),
            'placedAgo' => $order->placed_at?->diffForHumans(),
            'total' => [
                'value' => (int) $order->total,
                'formatted' => $this->formatMoney((int) $order->total),
            ],
            'canTrack' => in_array($order->status, ['preparing', 'on_the_way'], true),
            'canReorder' => $reorder['available'],
            'reorder' => $reorder,
        ];
    }

    private function activeOrdersCount(User $user): int
    {
        return $user->orders()
            ->whereIn('status', ['preparing', 'on_the_way'])
            ->count();
    }

    private function completedOrdersCount(User $user): int
    {
        return $user->orders()
            ->whereIn('status', ['delivered', 'cancelled'])
            ->count();
    }

    private function totalOrdersCount(User $user): int
    {
        return $user->orders()
            ->where('status', '!=', 'cart')
            ->count();
    }

    /**
     * @return Collection<int, MenuItem>
     */
    private function replacementCandidatesForRestaurant(int $restaurantId): Collection
    {
        if (! array_key_exists($restaurantId, $this->replacementCandidateCache)) {
            $this->replacementCandidateCache[$restaurantId] = MenuItem::query()
                ->where('restaurant_id', $restaurantId)
                ->where('is_available', true)
                ->orderBy('name')
                ->get(['id', 'restaurant_id', 'name', 'slug', 'category', 'price']);
        }

        return $this->replacementCandidateCache[$restaurantId];
    }

    /**
     * @param  Collection<int, MenuItem>  $candidates
     * @return array<int, array{id: int, slug: string, name: string, category: ?string, price: array{value: int, formatted: string}}>
     */
    private function replacementSuggestions(OrderItem $item, ?MenuItem $menuItem, Collection $candidates): array
    {
        return $candidates
            ->map(fn (MenuItem $candidate) => [
                'candidate' => $candidate,
                'score' => $this->replacementScore($item, $menuItem, $candidate),
                'priceDiff' => abs((int) $candidate->price - (int) $item->unit_price),
            ])
            ->sort(function (array $left, array $right): int {
                return [
                    $right['score'],
                    $left['priceDiff'],
                    $left['candidate']->name,
                ] <=> [
                    $left['score'],
                    $right['priceDiff'],
                    $right['candidate']->name,
                ];
            })
            ->take(3)
            ->map(fn (array $entry) => [
                'id' => $entry['candidate']->id,
                'slug' => $entry['candidate']->slug,
                'name' => $entry['candidate']->name,
                'category' => $entry['candidate']->category,
                'price' => [
                    'value' => (int) $entry['candidate']->price,
                    'formatted' => $this->formatMoney((int) $entry['candidate']->price),
                ],
            ])
            ->values()
            ->all();
    }

    private function replacementScore(OrderItem $item, ?MenuItem $menuItem, MenuItem $candidate): int
    {
        $score = 10;

        if ($menuItem?->category && $candidate->category === $menuItem->category) {
            $score += 30;
        }

        $sharedTerms = collect($this->normalizedNameTerms($item->name))
            ->intersect($this->normalizedNameTerms($candidate->name))
            ->count();

        $score += min($sharedTerms * 14, 42);

        $priceDiff = abs((int) $candidate->price - (int) $item->unit_price);

        if ($priceDiff <= 40) {
            $score += 12;
        } elseif ($priceDiff <= 90) {
            $score += 8;
        } elseif ($priceDiff <= 150) {
            $score += 4;
        }

        return $score;
    }

    /**
     * @return array<int, string>
     */
    private function normalizedNameTerms(string $value): array
    {
        return collect(preg_split('/[^a-z0-9]+/i', Str::lower($value)) ?: [])
            ->map(fn (string $term) => trim($term))
            ->filter(fn (string $term) => Str::length($term) >= 3)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<string, int>
     */
    private function overview(User $user): array
    {
        return [
            'activeOrdersCount' => $this->activeOrdersCount($user),
            'completedOrdersCount' => $this->completedOrdersCount($user),
            'totalOrdersCount' => $this->totalOrdersCount($user),
        ];
    }

    private function resolveFilter(?string $filter): string
    {
        return in_array($filter, ['all', 'active', 'history'], true)
            ? $filter
            : 'all';
    }

    private function formatMoney(int $amount, ?string $zeroLabel = null): string
    {
        return \App\Support\MoneyFormatter::format($amount, $zeroLabel);
    }
}