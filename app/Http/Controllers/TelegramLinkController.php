<?php

namespace App\Http\Controllers;

use App\Actions\Telegram\LinkStoreTelegram;
use App\Http\Controllers\Concerns\ResolvesVendorStore;
use App\Models\PlatformSetting;
use App\Services\Telegram\TelegramClient;
use App\Services\Telegram\TelegramFailure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class TelegramLinkController extends Controller
{
    use ResolvesVendorStore;

    public function store(Request $request, LinkStoreTelegram $links): JsonResponse|RedirectResponse
    {
        $store = $this->vendorStore($request);

        if (PlatformSetting::current()->botUsername() === '') {
            throw ValidationException::withMessages([
                'telegram' => 'The Vendly bot is not set up yet. Ask the Vendly admin to add the bot username.',
            ]);
        }

        $url = $links->start($store);

        if ($request->expectsJson()) {
            return response()->json(['url' => $url]);
        }

        return back()->with('telegram_link', $url);
    }

    /**
     * Send one message to the store's connected chat right now, so the
     * vendor sees at once that requests will arrive, or what to fix.
     */
    public function test(Request $request, TelegramClient $telegram): RedirectResponse
    {
        $store = $this->vendorStore($request);

        if (blank($store->telegram_chat_id)) {
            return $this->testResult('error', 'Connect your Telegram chat first.');
        }

        if (blank(config('services.telegram.bot_token'))) {
            return $this->testResult('error', 'The Vendly bot is not set up yet. Ask the Vendly admin to finish the bot setup.');
        }

        try {
            $telegram->sendMessage((string) $store->telegram_chat_id, 'Vendly test for '.$store->name.'. Buy requests from your store will arrive in this chat.');
        } catch (RequestException $exception) {
            return $this->testResult('error', TelegramFailure::explain((string) $exception->response->json('description', 'Telegram refused the message.')));
        } catch (ConnectionException) {
            return $this->testResult('error', 'Telegram could not be reached. Try again in a moment.');
        }

        return $this->testResult('success', 'Test message sent. Check your Telegram chat.');
    }

    private function testResult(string $type, string $message): RedirectResponse
    {
        Inertia::flash('toast', ['type' => $type, 'message' => $message]);

        return back()->with('telegram_test', ['type' => $type, 'message' => $message]);
    }
}
