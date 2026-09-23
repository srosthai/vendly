<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Services\Telegram\TelegramClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class TelegramTestController extends Controller
{
    /**
     * Send one message to the admin chat right now, so the admin sees at
     * once whether the bot token and chat id work, and Telegram's reason
     * when they do not.
     */
    public function __invoke(TelegramClient $telegram): RedirectResponse
    {
        $chatId = PlatformSetting::current()->adminChatId();

        if (blank(config('services.telegram.bot_token'))) {
            return $this->result('error', 'Add TELEGRAM_BOT_TOKEN to the server environment, then send the test again.');
        }

        if ($chatId === '') {
            return $this->result('error', 'Enter the admin chat id and save the settings first.');
        }

        try {
            $telegram->sendMessage($chatId, 'Vendly test message. If you can read this, buy requests will reach this chat.');
        } catch (RequestException $exception) {
            $reason = (string) $exception->response->json('description', 'Telegram refused the message.');

            return $this->result('error', 'Telegram refused the test: '.$reason);
        } catch (ConnectionException) {
            return $this->result('error', 'Telegram could not be reached. Try again in a moment.');
        }

        return $this->result('success', 'Test message sent. Check the admin chat.');
    }

    private function result(string $type, string $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => $type, 'message' => $message]);

        return back()->with('telegram_test', ['type' => $type, 'message' => $message]);
    }
}
