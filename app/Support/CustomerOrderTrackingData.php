<?php

namespace App\Support;

class CustomerOrderTrackingData
{
    /**
     * @return array<string, mixed>
     */
    public function build(string $status): array
    {
        return [
            'status' => [
                'key' => $status,
                'label' => str($status)->replace('_', ' ')->title()->toString(),
                'accent' => $this->statusAccent($status),
            ],
            'guidance' => $this->guidance($status),
            'timeline' => $this->timeline($status),
        ];
    }

    private function statusAccent(string $status): string
    {
        return match ($status) {
            'preparing' => 'bg-orange-50 text-orange-700',
            'on_the_way' => 'bg-sky-50 text-sky-700',
            'delivered' => 'bg-emerald-50 text-emerald-700',
            default => 'bg-gray-100 text-gray-700',
        };
    }

    /**
     * @return array<string, string>
     */
    private function guidance(string $status): array
    {
        return match ($status) {
            'preparing' => [
                'title' => 'The kitchen is finishing your order now.',
                'description' => 'Your restaurant has the ticket and is preparing the handoff. Keep this screen nearby so you know when delivery starts moving.',
            ],
            'on_the_way' => [
                'title' => 'Your rider is currently on the way.',
                'description' => 'Delivery is in motion. Double-check the address and be ready for drop-off notes or arrival calls.',
            ],
            'delivered' => [
                'title' => 'This order has been completed successfully.',
                'description' => 'Everything in this order is now in history, with totals, destination context, and payment details kept here for quick reference.',
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
    private function timeline(string $status): array
    {
        $currentIndex = match ($status) {
            'preparing' => 1,
            'on_the_way' => 2,
            'delivered' => 3,
            default => 0,
        };

        return collect([
            [
                'key' => 'placed',
                'label' => 'Order placed',
                'description' => 'Checkout details were accepted and the kitchen received your order.',
            ],
            [
                'key' => 'preparing',
                'label' => 'Kitchen preparing',
                'description' => 'The restaurant is packing dishes and getting the order ready for handoff.',
            ],
            [
                'key' => 'on_the_way',
                'label' => 'Rider on the way',
                'description' => 'Delivery has left the kitchen and is moving toward your address.',
            ],
            [
                'key' => 'delivered',
                'label' => 'Delivered',
                'description' => 'The order has arrived and is now saved in your history.',
            ],
        ])
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