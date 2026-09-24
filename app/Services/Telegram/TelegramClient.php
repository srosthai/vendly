<?php

namespace App\Services\Telegram;

use App\Models\PlatformSetting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class TelegramClient
{
    /**
     * @param  array<string, mixed>  $options  Extra Bot API fields, such as parse_mode or reply_markup.
     */
    public function sendMessage(string $chatId, string $text, array $options = []): bool
    {
        $token = PlatformSetting::current()->botToken();

        if ($token === '' || $chatId === '') {
            return false;
        }

        $this->call($token, 'sendMessage', ['chat_id' => $chatId, 'text' => $text, ...$options]);

        return true;
    }

    /**
     * Who a token belongs to. Throws when Telegram does not accept it.
     *
     * @return array{id: int, username: string, first_name: string}
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getMe(string $token): array
    {
        $bot = $this->call($token, 'getMe');

        return [
            'id' => (int) ($bot['id'] ?? 0),
            'username' => (string) ($bot['username'] ?? ''),
            'first_name' => (string) ($bot['first_name'] ?? ''),
        ];
    }

    /**
     * Point the bot's updates at this site. Only messages are needed: they
     * carry the /start link that connects a vendor's chat.
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    public function setWebhook(string $token, string $url, string $secret): void
    {
        $this->call($token, 'setWebhook', [
            'url' => $url,
            'secret_token' => $secret,
            'allowed_updates' => ['message'],
            'drop_pending_updates' => true,
        ]);
    }

    /**
     * @return array{url: string, pending_update_count: int, last_error_message: string|null, last_error_date: int|null}
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getWebhookInfo(string $token): array
    {
        $info = $this->call($token, 'getWebhookInfo');

        return [
            'url' => (string) ($info['url'] ?? ''),
            'pending_update_count' => (int) ($info['pending_update_count'] ?? 0),
            'last_error_message' => isset($info['last_error_message']) ? (string) $info['last_error_message'] : null,
            'last_error_date' => isset($info['last_error_date']) ? (int) $info['last_error_date'] : null,
        ];
    }

    /**
     * One Bot API call. The token is part of the URL, so a connection error
     * would carry it in its message; that error is rethrown with the token
     * removed and without the original exception attached.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    private function call(string $token, string $method, array $payload = []): array
    {
        try {
            $response = Http::baseUrl('https://api.telegram.org')
                ->connectTimeout(3)
                ->timeout(10)
                ->asJson()
                ->post('/bot'.$token.'/'.$method, $payload);
        } catch (ConnectionException $exception) {
            throw new ConnectionException(str_replace($token, '[bot-token]', $exception->getMessage()));
        }

        $response->throw();
        $result = $response->json('result');

        return is_array($result) ? $result : [];
    }
}
