<?php

namespace App\Support;

use InvalidArgumentException;

class Money
{
    public static function toCents(string $amount): int
    {
        if (! preg_match('/^\d+(\.\d{1,2})?$/', $amount)) {
            throw new InvalidArgumentException('The amount must be a USD value with at most two decimal places.');
        }

        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '0');
        $fraction = str_pad(substr($fraction, 0, 2), 2, '0');

        return ((int) $whole * 100) + (int) $fraction;
    }

    public static function format(int $cents): string
    {
        return '$'.number_format($cents / 100, 2);
    }

    public static function dollars(int $cents): float
    {
        return round($cents / 100, 2);
    }
}
