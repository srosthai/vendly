<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Scanned = 'scanned';
    case Paid = 'paid';
    case Expired = 'expired';
    case Failed = 'failed';
}
