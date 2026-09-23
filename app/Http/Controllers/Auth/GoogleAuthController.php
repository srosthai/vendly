<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\AuthenticateGoogleUser;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(): SymfonyRedirectResponse|RedirectResponse
    {
        if (! $this->configured()) {
            return redirect()->route('login')->with('status', 'Add the Google client id and secret to the environment.');
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(AuthenticateGoogleUser $action): RedirectResponse
    {
        if (! $this->configured()) {
            return redirect()->route('login')->with('status', 'Add the Google client id and secret to the environment.');
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable) {
            return redirect()->route('login')->with('status', 'Google sign-in was cancelled.');
        }

        try {
            $user = $action->handle($googleUser);
        } catch (ValidationException $exception) {
            return redirect()->route('login')->with('status', $exception->validator->errors()->first());
        }

        Auth::login($user);
        request()->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    private function configured(): bool
    {
        return is_string(config('services.google.client_id'))
            && config('services.google.client_id') !== ''
            && is_string(config('services.google.client_secret'))
            && config('services.google.client_secret') !== '';
    }
}
