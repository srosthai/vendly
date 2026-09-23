<?php

namespace App\Enums;

/**
 * How long one plan payment keeps a store on its plan.
 */
enum BillingPeriod: string
{
    case Monthly = 'monthly';
    case Yearly = 'yearly';

    public function months(): int
    {
        return match ($this) {
            self::Monthly => 1,
            self::Yearly => 12,
        };
    }
}
