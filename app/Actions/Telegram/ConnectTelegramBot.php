<?php

namespace App\Actions\Telegram;

use App\Models\PlatformSetting;
use App\Services\Telegram\TelegramClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Sets the Vendly bot up from its token alone: Telegram confirms the token
 * and says the bot's username, Vendly creates a secret for the webhook, and
 * Telegram is told to send the bot's messages to this site.
 */
class ConnectTelegramBot
{
    public function __construct(private TelegramClient $telegram) {}

    /**
     * Check a new token and save it with the bot's username. Nothing is
     * saved when Telegram does not accept the token.
     *
     * @throws ValidationException
     */
    public function saveToken(PlatformSetting $settings, string $token): void
    {
        try {
            $bot = $this->telegram->getMe($token);
        } catch (RequestException) {
            throw ValidationException::withMessages([
                'bot_token' => 'Telegram did not accept this token. Copy it again from @BotFather.',
            ]);
        } catch (ConnectionException) {
            throw ValidationException::withMessages([
                'bot_token' => 'Telegram could not be reached. Try again in a moment.',
            ]);
        }

        $settings->telegram_bot_token = $token;
        $settings->bot_username = $bot['username'];
        $settings->save();
    }

    /**
     * Register this site as the bot's webhook, creating the shared secret
     * first if there is none.
     *
     * @return array{ok: bool, message: string}
     */
    public function registerWebhook(PlatformSetting $settings): array
    {
        $token = $settings->botToken();
        $url = $this->webhookUrl();

        if ($token === '') {
            return ['ok' => false, 'message' => 'Add the bot token first.'];
        }

        if (! str_starts_with($url, 'https://')) {
            return ['ok' => false, 'message' => 'Telegram only sends updates to an https address. Set APP_URL to the public https address, such as the Cloudflare Tunnel, then register again.'];
        }

        if ($settings->telegramWebhookSecret() === '') {
            $settings->telegram_webhook_secret = Str::random(48);
            $settings->save();
        }

        try {
            $this->telegram->setWebhook($token, $url, $settings->telegramWebhookSecret());
        } catch (RequestException $exception) {
            return ['ok' => false, 'message' => 'Telegram refused the webhook: '.(string) $exception->response->json('description', 'unknown reason').'.'];
        } catch (ConnectionException) {
            return ['ok' => false, 'message' => 'Telegram could not be reached. Try again in a moment.'];
        }

        return ['ok' => true, 'message' => 'Telegram now sends the bot\'s messages to '.$url.'.'];
    }

    /**
     * The webhook address on the site's public URL, whatever address the
     * admin happens to be using.
     */
    public function webhookUrl(): string
    {
        return rtrim((string) config('app.url'), '/').route('webhooks.telegram', absolute: false);
    }
}
