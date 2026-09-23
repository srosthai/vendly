<?php

namespace App\Services\Telegram;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class TelegramClient
{
    /**
     * The bot token is part of the API URL, so a connection error would carry
     * it in its message. That error is rethrown with the token removed and
     * without the original exception attached.
     *
     * @throws RequestException
     * @throws ConnectionException
     */
    public function sendMessage(string $chatId, string $text): bool
    {
        $token = config('services.telegram.bot_token');

        if (! is_string($token) || $token === '' || $chatId === '') {
            return false;
        }

        try {
            $response = Http::baseUrl('https://api.telegram.org')
                ->connectTimeout(3)
                ->timeout(10)
                ->post('/bot'.$token.'/sendMessage', [
                    'chat_id' => $chatId,
                    'text' => $text,
                ]);
        } catch (ConnectionException $exception) {
            throw new ConnectionException(str_replace($token, '[bot-token]', $exception->getMessage()));
        }

        $response->throw();

        return true;
    }
}
