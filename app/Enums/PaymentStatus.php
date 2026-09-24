<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Scanned = 'scanned';
    case Paid = 'paid';
    case Expired = 'expired';
    case Failed = 'failed';
    case Canceled = 'canceled';

    /**
     * Whether the QR can still be paid.
     */
    public function isOpen(): bool
    {
        return $this === self::Pending || $this === self::Scanned;
    }
}
