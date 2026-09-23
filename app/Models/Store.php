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
 * @property string|null $accent
 * @property string|null $phone
 * @property string|null $address
 * @property string|null $hours
 * @property array<string, string>|null $social_links
 * @property-read int|null $published_products_count Only when loaded with withCount in a listing.
 */
#[Fillable(['user_id', 'name', 'slug', 'description', 'logo_path', 'currency', 'telegram_chat_id', 'accent', 'phone', 'address', 'hours', 'social_links'])]
class Store extends Model
{
    /**
     * Telegram's startapp value is at most 64 characters, so store slugs are too.
     */
    public const MaxSlugLength = 64;

    /**
     * The colors a store can pick for its mark and header. Each is dark or
     * bright enough to carry its text in both themes.
     *
     * @var list<string>
     */
    public const Accents = ['blue', 'orange', 'green', 'teal', 'purple', 'pink', 'red', 'slate'];

    /**
     * The places a store can link to, in display order.
     *
     * @var list<string>
     */
    public const SocialNetworks = ['facebook', 'instagram', 'tiktok', 'website'];

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
            'social_links' => 'array',
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

    /**
     * What customers see about the store beyond its name: only the details
     * the vendor filled in, with social links in display order.
     *
     * @return array{accent: string|null, phone: string|null, address: string|null, hours: string|null, socials: array<string, string>}
     */
    public function publicProfile(): array
    {
        return [
            'accent' => $this->accent,
            'phone' => $this->phone,
            'address' => $this->address,
            'hours' => $this->hours,
            'socials' => collect(self::SocialNetworks)
                ->mapWithKeys(fn (string $network): array => [$network => $this->social_links[$network] ?? null])
                ->filter()
                ->all(),
        ];
    }
}
