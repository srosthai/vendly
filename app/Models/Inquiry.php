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
 * @property int|null $number
 * @property bool $from_cart
 * @property string|null $admin_error
 * @property string|null $vendor_error
 * @property int $store_id
 * @property int|null $user_id
 * @property string $customer_name
 * @property string|null $contact
 * @property CarbonInterface|null $admin_notified_at
 * @property CarbonInterface|null $vendor_notified_at
 * @property CarbonInterface|null $handled_at
 */
#[Fillable(['public_id', 'number', 'store_id', 'user_id', 'customer_name', 'contact', 'from_cart', 'admin_notified_at', 'vendor_notified_at', 'admin_error', 'vendor_error', 'handled_at'])]
class Inquiry extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'handled_at' => 'datetime',
            'admin_notified_at' => 'datetime',
            'vendor_notified_at' => 'datetime',
            'from_cart' => 'boolean',
            'number' => 'integer',
        ];
    }

    /**
     * The number people read out, such as "#42".
     */
    public function reference(): string
    {
        return '#'.($this->number ?? $this->id);
    }

    /**
     * The chats that have not received this request yet. The vendor chat only
     * counts once the vendor has connected Telegram.
     *
     * @return list<'admin'|'vendor'>
     */
    public function undeliveredDestinations(): array
    {
        $this->loadMissing('store');
        $destinations = [];

        if ($this->admin_notified_at === null) {
            $destinations[] = 'admin';
        }

        if ($this->vendor_notified_at === null && filled($this->store->telegram_chat_id)) {
            $destinations[] = 'vendor';
        }

        return $destinations;
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
