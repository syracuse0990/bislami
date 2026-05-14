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
                'label' => OrderLifecycle::label($status),
                'accent' => $this->statusAccent($status),
            ],
            'guidance' => $this->guidance($status),
            'timeline' => $this->timeline($status),
        ];
    }

    private function statusAccent(string $status): string
    {
        return str(OrderLifecycle::accent($status))
            ->replace(' ring-amber-200', '')
            ->replace(' ring-blue-200', '')
            ->replace(' ring-orange-200', '')
            ->replace(' ring-violet-200', '')
            ->replace(' ring-sky-200', '')
            ->replace(' ring-emerald-200', '')
            ->replace(' ring-rose-200', '')
            ->replace(' ring-slate-200', '')
            ->toString();
    }

    /**
     * @return array<string, string>
     */
    private function guidance(string $status): array
    {
        return OrderLifecycle::guidance($status);
    }

    /**
     * @return array<int, array<string, string|bool>>
     */
    private function timeline(string $status): array
    {
        return OrderLifecycle::timeline($status);
    }
}