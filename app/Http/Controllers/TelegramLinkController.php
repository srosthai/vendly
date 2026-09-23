<?php

namespace App\Http\Controllers;

use App\Actions\Telegram\LinkStoreTelegram;
use App\Http\Controllers\Concerns\ResolvesVendorStore;
use App\Models\PlatformSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

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
}
