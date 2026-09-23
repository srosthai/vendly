<?php

namespace App\Models;

use App\Enums\BillingPeriod;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property int $price_cents
 * @property int|null $yearly_price_cents
 * @property int $product_limit
 * @property bool $is_active
 * @property bool $is_default
 */
#[Fillable(['name', 'price_cents', 'yearly_price_cents', 'product_limit', 'is_active', 'is_default'])]
class Plan extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'yearly_price_cents' => 'integer',
            'product_limit' => 'integer',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Subscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function isFree(): bool
    {
        return $this->price_cents === 0;
    }

    /**
     * A paid plan can also be bought for a year once the admin sets a
     * yearly price.
     */
    public function offersYearly(): bool
    {
        return ! $this->isFree() && $this->yearly_price_cents !== null && $this->yearly_price_cents > 0;
    }

    /**
     * What one payment costs for a period, or null when the plan is not
     * sold that way.
     */
    public function priceFor(BillingPeriod $period): ?int
    {
        return match ($period) {
            BillingPeriod::Monthly => $this->price_cents,
            BillingPeriod::Yearly => $this->offersYearly() ? $this->yearly_price_cents : null,
        };
    }
}
