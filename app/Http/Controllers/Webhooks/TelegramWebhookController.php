<?php

namespace App\Http\Controllers\Webhooks;

use App\Actions\Telegram\LinkStoreTelegram;
use App\Http\Controllers\Controller;
use App\Services\Telegram\TelegramNotifier;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, LinkStoreTelegram $links, TelegramNotifier $telegram): Response
    {
        $secret = config('services.telegram.webhook_secret');

        if (! is_string($secret) || $secret === '') {
            Log::error('Telegram webhook refused: TELEGRAM_WEBHOOK_SECRET is not set.');

            abort(401);
        }

        if (! hash_equals($secret, (string) $request->header('X-Telegram-Bot-Api-Secret-Token', ''))) {
            abort(401);
        }

        $text = $request->input('message.text');
        $chatId = $request->input('message.chat.id');

        if (is_string($text) && preg_match('/^\/start link_([A-Za-z0-9]+)$/', $text, $matches) && (is_string($chatId) || is_int($chatId))) {
            $store = $links->complete($matches[1], (string) $chatId, $this->chatName($request));

            if ($store !== null) {
                $telegram->storeConnected($store);
            }
        }

        return response()->noContent();
    }

    /**
     * What to call the chat: a group's title, or the person's name, or
     * their username.
     */
    private function chatName(Request $request): ?string
    {
        $title = $request->input('message.chat.title');

        if (is_string($title) && $title !== '') {
            return $title;
        }

        $name = trim(implode(' ', array_filter([
            $request->input('message.chat.first_name'),
            $request->input('message.chat.last_name'),
        ], 'is_string')));

        if ($name !== '') {
            return $name;
        }

        $username = $request->input('message.chat.username');

        return is_string($username) && $username !== '' ? '@'.$username : null;
    }
}
