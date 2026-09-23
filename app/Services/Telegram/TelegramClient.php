<?php

namespace App\Services\Telegram;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class TelegramClient
{
    /**
     * @throws RequestException
     */
    public function sendMessage(string $chatId, string $text): bool
    {
        $token = config('services.telegram.bot_token');

        if (! is_string($token) || $token === '' || $chatId === '') {
            return false;
        }

        $response = Http::baseUrl('https://api.telegram.org')
            ->connectTimeout(3)
            ->timeout(10)
            ->post('/bot'.$token.'/sendMessage', [
                'chat_id' => $chatId,
                'text' => $text,
            ]);

        $response->throw();

        return true;
    }
}
