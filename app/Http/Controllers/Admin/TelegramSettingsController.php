<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Telegram\ConnectTelegramBot;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateTelegramSettingsRequest;
use App\Models\PlatformSetting;
use App\Services\Telegram\TelegramClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The Vendly bot: its token, the chat that receives every request, the
 * mini app, and the webhook Telegram uses to reach this site.
 */
class TelegramSettingsController extends Controller
{
    public function edit(Request $request, TelegramClient $telegram, ConnectTelegramBot $connect): Response
    {
        $settings = PlatformSetting::current();
        $token = $settings->botToken();

        return Inertia::render('admin/telegram', [
            'settings' => [
                'admin_chat_id' => $settings->admin_chat_id ?? '',
                'bot_username' => $settings->botUsername(),
                'mini_app_short_name' => $settings->mini_app_short_name ?? '',
            ],
            'token' => $this->secretState($settings->telegram_bot_token, (string) config('services.telegram.bot_token')),
            'webhookUrl' => $connect->webhookUrl(),
            'cutluyWebhookUrl' => route('webhooks.cutluy'),
            'cutluyReady' => $settings->cutluyApiKey() !== '' && $settings->cutluyWebhookSecret() !== '',
            'webhook' => Inertia::defer(fn (): ?array => $this->webhookStatus($telegram, $token, $settings, $connect)),
            'testResult' => $request->session()->get('telegram_test'),
            'setupResult' => $request->session()->get('telegram_setup'),
        ]);
    }

    public function update(UpdateTelegramSettingsRequest $request, ConnectTelegramBot $connect): RedirectResponse
    {
        $settings = PlatformSetting::current();
        $settings->admin_chat_id = $request->filled('admin_chat_id') ? $request->string('admin_chat_id')->trim()->toString() : null;
        $settings->mini_app_short_name = $request->filled('mini_app_short_name') ? $request->string('mini_app_short_name')->trim()->toString() : null;
        $settings->save();

        $newToken = $request->filled('bot_token')
            && $request->string('bot_token')->trim()->toString() !== $settings->botToken();

        if ($newToken) {
            $connect->saveToken($settings, $request->string('bot_token')->trim()->toString());

            return $this->setupResult($connect->registerWebhook($settings));
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Telegram settings saved.']);

        return back();
    }

    /**
     * Point Telegram at this site again, for example after its address
     * changed.
     */
    public function registerWebhook(ConnectTelegramBot $connect): RedirectResponse
    {
        return $this->setupResult($connect->registerWebhook(PlatformSetting::current()));
    }

    /**
     * @param  array{ok: bool, message: string}  $result
     */
    private function setupResult(array $result): RedirectResponse
    {
        $type = $result['ok'] ? 'success' : 'error';
        Inertia::flash('toast', ['type' => $type, 'message' => $result['ok'] ? 'The bot is connected.' : $result['message']]);

        return back()->with('telegram_setup', ['type' => $type, 'message' => $result['message']]);
    }

    /**
     * What Telegram reports about the bot's webhook right now, or null when
     * there is no token or Telegram cannot be asked.
     *
     * @return array{url: string, matches: bool, pending: int, last_error: string|null, reachable: bool}|null
     */
    private function webhookStatus(TelegramClient $telegram, string $token, PlatformSetting $settings, ConnectTelegramBot $connect): ?array
    {
        if ($token === '') {
            return null;
        }

        try {
            $info = $telegram->getWebhookInfo($token);
        } catch (RequestException|ConnectionException) {
            return ['url' => '', 'matches' => false, 'pending' => 0, 'last_error' => null, 'reachable' => false];
        }

        return [
            'url' => $info['url'],
            'matches' => $info['url'] === $connect->webhookUrl() && $settings->telegramWebhookSecret() !== '',
            'pending' => $info['pending_update_count'],
            'last_error' => $info['last_error_message'],
            'reachable' => true,
        ];
    }

    /**
     * @return array{source: 'admin'|'env'|null, ends_with: string|null}
     */
    private function secretState(?string $saved, string $environment): array
    {
        if (filled($saved)) {
            return ['source' => 'admin', 'ends_with' => substr((string) $saved, -4)];
        }

        return $environment !== ''
            ? ['source' => 'env', 'ends_with' => substr($environment, -4)]
            : ['source' => null, 'ends_with' => null];
    }
}
