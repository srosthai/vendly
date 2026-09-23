<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $logo_path
 * @property string $currency
 * @property CarbonInterface|null $suspended_at
 * @property string|null $telegram_chat_id
 * @property-read int|null $published_products_count Only when loaded with withCount in a listing.
 */
#[Fillable(['user_id', 'name', 'slug', 'description', 'logo_path', 'currency', 'telegram_chat_id'])]
class Store extends Model
{
    /**
     * Telegram's startapp value is at most 64 characters, so store slugs are too.
     */
    public const MaxSlugLength = 64;

    /**
     * Words that would read like a Vendly page rather than a shop.
     *
     * @var list<string>
     */
    public const ReservedSlugs = [
        'admin', 'api', 'app', 'auth', 'cart', 'dashboard', 'help', 'login', 'logout',
        'm', 'register', 'settings', 'shop', 'start-selling', 'store', 'stores',
        'support', 'telegram', 'vendly', 'vendor', 'webhooks',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'suspended_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasOne<Subscription, $this>
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * @return HasMany<Category, $this>
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * @return HasMany<Brand, $this>
     */
    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class);
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    /**
     * @return HasMany<Inquiry, $this>
     */
    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class);
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path === null ? null : Storage::disk('public')->url($this->logo_path);
    }
}
