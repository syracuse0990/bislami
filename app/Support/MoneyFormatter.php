<?php

namespace App\Support;

class MoneyFormatter
{
    public static function format(int|float $amount, ?string $zeroLabel = null): string
    {
        $normalizedAmount = (int) round($amount);

        if ($normalizedAmount === 0 && $zeroLabel) {
            return $zeroLabel;
        }

        return '₱'.number_format($normalizedAmount);
    }

    public static function currencyCode(): string
    {
        return 'PHP';
    }
}