<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
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
 * @property string|null $cutluy_api_key
 * @property string|null $cutluy_webhook_secret
 * @property string|null $cutluy_base_url
 * @property bool $google_enabled
 * @property string|null $google_client_id
 * @property string|null $google_client_secret
 * @property string|null $telegram_bot_token
 * @property string|null $telegram_webhook_secret
 */
#[Fillable(['admin_chat_id', 'bot_username', 'mini_app_short_name', 'company_name', 'address', 'phone', 'email', 'footer_text', 'social_links', 'cutluy_api_key', 'cutluy_webhook_secret', 'cutluy_base_url', 'google_enabled', 'google_client_id', 'google_client_secret', 'telegram_bot_token', 'telegram_webhook_secret'])]
#[Hidden(['cutluy_api_key', 'cutluy_webhook_secret', 'google_client_secret', 'telegram_bot_token', 'telegram_webhook_secret'])]
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
            'cutluy_api_key' => 'encrypted',
            'cutluy_webhook_secret' => 'encrypted',
            'google_enabled' => 'boolean',
            'google_client_secret' => 'encrypted',
            'telegram_bot_token' => 'encrypted',
            'telegram_webhook_secret' => 'encrypted',
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

    /**
     * The bot's username without a leading "@", as Telegram links need it.
     */
    public function botUsername(): string
    {
        $username = is_string($this->bot_username) && $this->bot_username !== ''
            ? $this->bot_username
            : (string) config('services.telegram.bot_username');

        return ltrim(trim($username), '@');
    }

    /**
     * The bot token: the one saved by the admin, or the environment's.
     */
    public function botToken(): string
    {
        return $this->telegram_bot_token ?: (string) config('services.telegram.bot_token');
    }

    /**
     * The secret Telegram sends with every webhook call: the one Vendly
     * generated when the bot was connected, or the environment's.
     */
    public function telegramWebhookSecret(): string
    {
        return $this->telegram_webhook_secret ?: (string) config('services.telegram.webhook_secret');
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

    /**
     * The CutLuy API key: the one saved by the admin, or the environment's.
     */
    public function cutluyApiKey(): string
    {
        return $this->cutluy_api_key ?: (string) config('services.cutluy.key');
    }

    /**
     * The secret CutLuy signs webhooks with: the admin's, or the environment's.
     */
    public function cutluyWebhookSecret(): string
    {
        return $this->cutluy_webhook_secret ?: (string) config('services.cutluy.webhook_secret');
    }

    public function cutluyBaseUrl(): string
    {
        return rtrim($this->cutluy_base_url ?: (string) config('services.cutluy.base_url'), '/');
    }

    public function googleClientId(): string
    {
        return $this->google_client_id ?: (string) config('services.google.client_id');
    }

    public function googleClientSecret(): string
    {
        return $this->google_client_secret ?: (string) config('services.google.client_secret');
    }

    /**
     * Where Google sends people back: the environment's address when set,
     * otherwise this site's callback route.
     */
    public function googleRedirectUrl(): string
    {
        return (string) config('services.google.redirect') ?: route('auth.google.callback');
    }

    /**
     * Google sign-in shows only when the admin has it on and both
     * credentials are known.
     */
    public function googleSignInReady(): bool
    {
        return ($this->google_enabled ?? true)
            && $this->googleClientId() !== ''
            && $this->googleClientSecret() !== '';
    }
}
