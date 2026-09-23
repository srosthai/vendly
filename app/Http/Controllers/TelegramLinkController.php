<?php

namespace App\Http\Controllers;

use App\Actions\Telegram\LinkStoreTelegram;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TelegramLinkController extends Controller
{
    public function store(Request $request, LinkStoreTelegram $links): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $store = $user->store;
        abort_if($store === null || $store->isSuspended(), 403);
        abort_if(! is_string(config('services.telegram.bot_username')) || config('services.telegram.bot_username') === '', 422);

        $url = $links->start($store);

        if ($request->expectsJson()) {
            return response()->json(['url' => $url]);
        }

        return back()->with('telegram_link', $url);
    }
}
