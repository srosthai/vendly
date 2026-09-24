<?php

namespace App\Models;

use App\Enums\BillingPeriod;
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
 * @property BillingPeriod $period
 * @property string|null $cutluy_id
 * @property PaymentStatus $status
 * @property string|null $checkout_url
 * @property string|null $qr_string
 * @property CarbonInterface|null $paid_at
 * @property CarbonInterface|null $expires_at
 */
#[Fillable(['public_id', 'store_id', 'plan_id', 'amount_cents', 'period', 'cutluy_id', 'status', 'checkout_url', 'qr_string', 'paid_at', 'expires_at'])]
class SubscriptionPayment extends Model
{
    /**
     * @var array<string, string>
     */
    protected $attributes = [
        'period' => 'monthly',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'status' => PaymentStatus::class,
            'period' => BillingPeriod::class,
            'paid_at' => 'datetime',
            'expires_at' => 'datetime',
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

    /**
     * An open payment whose QR ran out of time. CutLuy also reports it, but
     * the vendor should not wait for that.
     */
    public function hasLapsed(): bool
    {
        return $this->status->isOpen() && $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * CutLuy's KHQR image for this payment's QR text.
     */
    public function qrImageUrl(): ?string
    {
        if ($this->qr_string === null || $this->qr_string === '') {
            return null;
        }

        return PlatformSetting::current()->cutluyBaseUrl().'/api/render/khqr/'.rawurlencode($this->qr_string).'.svg';
    }
}
