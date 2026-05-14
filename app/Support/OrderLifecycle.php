<?php

namespace App\Support;

class OrderLifecycle
{
    public const CART = 'cart';

    public const PENDING = 'pending';

    public const ACCEPTED = 'accepted';

    public const PREPARING = 'preparing';

    public const READY = 'ready';

    public const PICKED_UP = 'picked_up';

    public const DELIVERED = 'delivered';

    public const REJECTED = 'rejected';

    public const LEGACY_ON_THE_WAY = 'on_the_way';

    /**
     * @return array<int, string>
     */
    public static function activeStatuses(): array
    {
        return [
            self::PENDING,
            self::ACCEPTED,
            self::PREPARING,
            self::READY,
            self::PICKED_UP,
            self::LEGACY_ON_THE_WAY,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function customerTrackableStatuses(): array
    {
        return [
            self::PENDING,
            self::ACCEPTED,
            self::PREPARING,
            self::READY,
            self::PICKED_UP,
            self::LEGACY_ON_THE_WAY,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaultOrderSettings(): array
    {
        return [
            'auto_accept_enabled' => false,
            'auto_reject_unavailable_items' => true,
            'sound_alerts_enabled' => true,
        ];
    }

    public static function normalize(string $status): string
    {
        return $status === self::LEGACY_ON_THE_WAY
            ? self::PICKED_UP
            : $status;
    }

    public static function label(string $status, string $fulfillmentType = 'delivery'): string
    {
        return match (self::normalize($status)) {
            self::CART => 'Cart',
            self::PENDING => 'Pending',
            self::ACCEPTED => 'Accepted',
            self::PREPARING => 'Preparing',
            self::READY => 'Ready',
            self::PICKED_UP => $fulfillmentType === 'pickup' ? 'Collected' : 'Picked up',
            self::DELIVERED => 'Delivered',
            self::REJECTED => 'Rejected',
            default => str($status)->replace('_', ' ')->title()->toString(),
        };
    }

    public static function accent(string $status): string
    {
        return match (self::normalize($status)) {
            self::PENDING => 'bg-amber-50 text-amber-700 ring-amber-200',
            self::ACCEPTED => 'bg-blue-50 text-blue-700 ring-blue-200',
            self::PREPARING => 'bg-orange-50 text-orange-700 ring-orange-200',
            self::READY => 'bg-violet-50 text-violet-700 ring-violet-200',
            self::PICKED_UP => 'bg-sky-50 text-sky-700 ring-sky-200',
            self::DELIVERED => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            self::REJECTED => 'bg-rose-50 text-rose-700 ring-rose-200',
            default => 'bg-slate-100 text-slate-700 ring-slate-200',
        };
    }

    public static function isCourierClaimable(string $status, string $fulfillmentType = 'delivery'): bool
    {
        return $fulfillmentType === 'delivery'
            && self::normalize($status) === self::READY;
    }

    public static function isCourierCompletable(string $status, string $fulfillmentType = 'delivery'): bool
    {
        if ($fulfillmentType !== 'delivery') {
            return false;
        }

        return self::normalize($status) === self::PICKED_UP;
    }

    public static function isMerchantEditable(string $status): bool
    {
        return in_array(self::normalize($status), [self::PENDING, self::ACCEPTED], true);
    }

    public static function canTransition(string $from, string $to, string $fulfillmentType = 'delivery'): bool
    {
        $from = self::normalize($from);
        $to = self::normalize($to);

        return in_array($to, match ($from) {
            self::PENDING => [self::ACCEPTED, self::REJECTED],
            self::ACCEPTED => [self::PREPARING, self::REJECTED],
            self::PREPARING => [self::READY],
            self::READY => $fulfillmentType === 'pickup' ? [self::DELIVERED] : [self::PICKED_UP],
            self::PICKED_UP => [self::DELIVERED],
            default => [],
        }, true);
    }

    public static function timestampColumnFor(string $status): ?string
    {
        return match (self::normalize($status)) {
            self::ACCEPTED => 'accepted_at',
            self::PREPARING => 'preparing_at',
            self::READY => 'ready_at',
            self::PICKED_UP => 'picked_up_at',
            self::DELIVERED => 'delivered_at',
            self::REJECTED => 'rejected_at',
            default => null,
        };
    }

    /**
     * @return array<string, string>
     */
    public static function guidance(string $status, string $fulfillmentType = 'delivery'): array
    {
        if ($status === self::LEGACY_ON_THE_WAY) {
            return [
                'title' => 'Your rider is currently on the way.',
                'description' => 'Delivery is in motion. Double-check the address and be ready for drop-off notes or arrival calls.',
            ];
        }

        $normalized = self::normalize($status);

        return match ($normalized) {
            self::PENDING => [
                'title' => 'The restaurant is reviewing your order now.',
                'description' => 'Your order is in the merchant approval queue. Any schedule, allergies, or special instructions are visible before preparation starts.',
            ],
            self::ACCEPTED => [
                'title' => 'The restaurant has accepted your order.',
                'description' => 'The kitchen has confirmed it can fulfill this order and will move it into preparation next.',
            ],
            self::PREPARING => [
                'title' => 'The kitchen is preparing your order now.',
                'description' => 'Your restaurant is actively working on the ticket and getting dishes ready for the next handoff milestone.',
            ],
            self::READY => [
                'title' => $fulfillmentType === 'pickup'
                    ? 'Your order is ready for pickup.'
                    : 'Your order is packed and ready for pickup by a rider.',
                'description' => $fulfillmentType === 'pickup'
                    ? 'Head to the restaurant when you are ready. The merchant can complete the order as soon as collection happens.'
                    : 'The restaurant has finished the order. The next update will confirm when a courier has picked it up.',
            ],
            self::PICKED_UP => [
                'title' => $fulfillmentType === 'pickup'
                    ? 'Your pickup has been collected.'
                    : 'Your rider has picked up the order.',
                'description' => $fulfillmentType === 'pickup'
                    ? 'The restaurant has marked the pickup as completed.'
                    : 'Delivery is in motion. Double-check the address and be ready for arrival calls or drop-off instructions.',
            ],
            self::DELIVERED => [
                'title' => 'This order has been completed successfully.',
                'description' => 'Everything in this order is now in history, with totals, destination context, and payment details kept here for quick reference.',
            ],
            self::REJECTED => [
                'title' => 'This order could not be fulfilled.',
                'description' => 'The merchant rejected the order before preparation began. Review the rejection reason and place a new order when you are ready.',
            ],
            default => [
                'title' => 'Your order status has been captured.',
                'description' => 'BizLami will update the next milestone here as your order moves through preparation and delivery.',
            ],
        };
    }

    /**
     * @return array<int, array<string, string|bool>>
     */
    public static function timeline(string $status, string $fulfillmentType = 'delivery'): array
    {
        if ($status === self::LEGACY_ON_THE_WAY) {
            return collect([
                [
                    'key' => 'placed',
                    'label' => 'Order placed',
                    'description' => 'Checkout details were accepted and the kitchen received your order.',
                ],
                [
                    'key' => self::PREPARING,
                    'label' => 'Kitchen preparing',
                    'description' => 'The restaurant is packing dishes and getting the order ready for handoff.',
                ],
                [
                    'key' => self::LEGACY_ON_THE_WAY,
                    'label' => 'Rider on the way',
                    'description' => 'Delivery has left the kitchen and is moving toward your address.',
                ],
                [
                    'key' => self::DELIVERED,
                    'label' => 'Delivered',
                    'description' => 'The order has arrived and is now saved in your history.',
                ],
            ])
                ->values()
                ->map(fn (array $step, int $index) => [
                    ...$step,
                    'state' => $index < 2 ? 'complete' : ($index === 2 ? 'current' : 'upcoming'),
                    'isComplete' => $index < 2,
                    'isCurrent' => $index === 2,
                ])
                ->all();
        }

        $steps = collect([
            [
                'key' => 'placed',
                'label' => 'Order placed',
                'description' => 'Checkout details were captured and the restaurant received the ticket.',
            ],
            [
                'key' => self::PENDING,
                'label' => 'Merchant review',
                'description' => 'The restaurant reviews the order, notes, and requested timing before preparation begins.',
            ],
            [
                'key' => self::ACCEPTED,
                'label' => 'Accepted',
                'description' => 'The merchant confirmed the order and reserved kitchen capacity for it.',
            ],
            [
                'key' => self::PREPARING,
                'label' => 'Kitchen preparing',
                'description' => 'The restaurant is cooking, packing, and staging the order.',
            ],
            [
                'key' => self::READY,
                'label' => 'Ready',
                'description' => $fulfillmentType === 'pickup'
                    ? 'The order is waiting at the restaurant for customer pickup.'
                    : 'The order is packed and waiting for courier pickup.',
            ],
            [
                'key' => self::PICKED_UP,
                'label' => $fulfillmentType === 'pickup' ? 'Collected' : 'Picked up',
                'description' => $fulfillmentType === 'pickup'
                    ? 'The pickup has been collected from the restaurant.'
                    : 'A courier has picked up the order and the delivery is in motion.',
            ],
            [
                'key' => self::DELIVERED,
                'label' => 'Delivered',
                'description' => 'The order has been completed and stored in history.',
            ],
        ]);

        $normalized = self::normalize($status);
        $currentIndex = match ($normalized) {
            self::PENDING => 1,
            self::ACCEPTED => 2,
            self::PREPARING => 3,
            self::READY => 4,
            self::PICKED_UP => 5,
            self::DELIVERED => 6,
            self::REJECTED => 1,
            default => 0,
        };

        return $steps
            ->values()
            ->map(fn (array $step, int $index) => [
                ...$step,
                'state' => $index < $currentIndex ? 'complete' : ($index === $currentIndex ? 'current' : 'upcoming'),
                'isComplete' => $index < $currentIndex,
                'isCurrent' => $index === $currentIndex,
            ])
            ->all();
    }
}