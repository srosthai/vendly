<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\AuthenticateTelegramUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\TelegramAuthRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class TelegramAuthController extends Controller
{
    public function store(TelegramAuthRequest $request, AuthenticateTelegramUser $action): RedirectResponse
    {
        $user = $action->handle($request->string('init_data')->toString());

        Auth::login($user);
        $request->session()->regenerate();

        $back = $request->string('redirect')->toString();

        if (str_starts_with($back, '/s/')) {
            return redirect($back);
        }

        return redirect()->intended(route('dashboard'));
    }
}
