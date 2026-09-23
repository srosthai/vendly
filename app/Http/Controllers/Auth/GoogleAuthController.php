<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\AuthenticateGoogleUser;
use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
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
            return redirect()->route('login')->with('status', 'Google sign-in is not available right now.');
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback(AuthenticateGoogleUser $action): RedirectResponse
    {
        if (! $this->configured()) {
            return redirect()->route('login')->with('status', 'Google sign-in is not available right now.');
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

    /**
     * Loads the Google credentials from Site settings (or the environment)
     * into Socialite's config for this request, and says whether sign-in is
     * on.
     */
    private function configured(): bool
    {
        $settings = PlatformSetting::current();

        if (! $settings->googleSignInReady()) {
            return false;
        }

        config([
            'services.google.client_id' => $settings->googleClientId(),
            'services.google.client_secret' => $settings->googleClientSecret(),
            'services.google.redirect' => $settings->googleRedirectUrl(),
        ]);

        return true;
    }
}
