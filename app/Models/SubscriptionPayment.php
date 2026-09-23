<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $public_id
 * @property int $store_id
 * @property int $plan_id
 * @property int $amount_cents
 * @property string|null $cutluy_id
 * @property PaymentStatus $status
 * @property string|null $checkout_url
 * @property string|null $qr_string
 * @property CarbonInterface|null $paid_at
 */
#[Fillable(['public_id', 'store_id', 'plan_id', 'amount_cents', 'cutluy_id', 'status', 'checkout_url', 'qr_string', 'paid_at'])]
class SubscriptionPayment extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'status' => PaymentStatus::class,
            'paid_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Store, $this>
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * @return BelongsTo<Plan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
