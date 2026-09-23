<?php

namespace App\Http\Controllers;

use App\Actions\Telegram\LinkStoreTelegram;
use App\Http\Controllers\Concerns\ResolvesVendorStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TelegramLinkController extends Controller
{
    use ResolvesVendorStore;

    public function store(Request $request, LinkStoreTelegram $links): JsonResponse|RedirectResponse
    {
        $store = $this->vendorStore($request);
        abort_if(! is_string(config('services.telegram.bot_username')) || config('services.telegram.bot_username') === '', 422);

        $url = $links->start($store);

        if ($request->expectsJson()) {
            return response()->json(['url' => $url]);
        }

        return back()->with('telegram_link', $url);
    }
}
