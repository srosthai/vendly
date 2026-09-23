<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\AuthenticateGoogleUser;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(): SymfonyRedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(AuthenticateGoogleUser $action): RedirectResponse
    {
        try {
            $user = $action->handle(Socialite::driver('google')->user());
        } catch (Throwable) {
            return redirect()->route('login')->with('status', 'Google sign-in was cancelled.');
        }

        Auth::login($user);
        request()->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
