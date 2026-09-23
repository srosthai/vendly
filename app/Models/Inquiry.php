<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $public_id
 * @property int $store_id
 * @property int|null $user_id
 * @property string $customer_name
 * @property string|null $contact
 * @property CarbonInterface|null $admin_notified_at
 * @property CarbonInterface|null $vendor_notified_at
 */
#[Fillable(['public_id', 'store_id', 'user_id', 'customer_name', 'contact', 'admin_notified_at', 'vendor_notified_at'])]
class Inquiry extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'admin_notified_at' => 'datetime',
            'vendor_notified_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany<InquiryItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(InquiryItem::class)->orderBy('id');
    }
}
