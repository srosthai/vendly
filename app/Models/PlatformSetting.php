<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string|null $admin_chat_id
 * @property string|null $bot_username
 * @property string|null $mini_app_short_name
 * @property string|null $company_name
 * @property string|null $address
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $footer_text
 * @property array<string, string>|null $social_links
 */
#[Fillable(['admin_chat_id', 'bot_username', 'mini_app_short_name', 'company_name', 'address', 'phone', 'email', 'footer_text', 'social_links'])]
class PlatformSetting extends Model
{
    /**
     * The social networks the footer can link to, in display order.
     *
     * @var list<string>
     */
    public const SocialNetworks = ['facebook', 'instagram', 'tiktok', 'youtube', 'telegram', 'linkedin'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'social_links' => 'array',
        ];
    }

    /**
     * Everything the website footer shows. Empty values are left out, so
     * the footer only draws what the admin filled in.
     *
     * @return array<string, mixed>
     */
    public function footer(): array
    {
        $socials = collect(self::SocialNetworks)
            ->mapWithKeys(fn (string $network): array => [$network => $this->social_links[$network] ?? null])
            ->filter()
            ->all();

        return [
            'company_name' => $this->company_name ?: 'Vendly',
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'footer_text' => $this->footer_text,
            'socials' => $socials,
            'payment_methods' => PaymentMethod::query()
                ->orderBy('sort')
                ->orderBy('id')
                ->get()
                ->map(fn (PaymentMethod $method): array => ['name' => $method->name, 'logo' => $method->logoUrl()])
                ->all(),
        ];
    }

    public static function current(): self
    {
        $setting = static::query()->first();

        if ($setting instanceof self) {
            return $setting;
        }

        return static::query()->create([]);
    }

    public function adminChatId(): string
    {
        if (is_string($this->admin_chat_id) && $this->admin_chat_id !== '') {
            return $this->admin_chat_id;
        }

        return (string) config('services.telegram.admin_chat_id');
    }

    public function botUsername(): string
    {
        if (is_string($this->bot_username) && $this->bot_username !== '') {
            return $this->bot_username;
        }

        return (string) config('services.telegram.bot_username');
    }

    /**
     * The mini app link for a store, or null until the bot and mini app are set.
     */
    public function miniAppLink(string $slug): ?string
    {
        $username = $this->botUsername();
        $short = is_string($this->mini_app_short_name) && $this->mini_app_short_name !== ''
            ? $this->mini_app_short_name
            : (string) config('services.telegram.mini_app_short_name');

        if ($username === '' || $short === '') {
            return null;
        }

        return 'https://t.me/'.$username.'/'.$short.'?startapp='.$slug;
    }

    /**
     * The mini app link that opens one product. Store slugs never contain an
     * underscore, so a "p_" start value cannot be mistaken for a store.
     */
    public function miniAppProductLink(int $productId): ?string
    {
        return $this->miniAppLink('p_'.$productId);
    }
}
