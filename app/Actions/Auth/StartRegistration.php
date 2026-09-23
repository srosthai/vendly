<?php

namespace App\Actions\Auth;

use App\Notifications\RegistrationCode;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class StartRegistration
{
    public function handle(string $name, string $email, string $password): void
    {
        $email = Str::lower($email);
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Cache::put($this->key($email), [
            'name' => $name,
            'password' => Hash::make($password),
            'hash' => Hash::make($code),
        ], now()->addMinutes(10));

        Notification::route('mail', $email)->notify(new RegistrationCode($code));
    }

    /**
     * Send a fresh code for a registration that is still waiting. The name
     * and password stay as entered. Returns false once the wait has expired.
     */
    public function resend(string $email): bool
    {
        $email = Str::lower($email);
        $cached = Cache::get($this->key($email));

        if (! is_array($cached) || ! isset($cached['name'], $cached['password'])) {
            return false;
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Cache::put($this->key($email), [
            ...$cached,
            'hash' => Hash::make($code),
        ], now()->addMinutes(10));

        Notification::route('mail', $email)->notify(new RegistrationCode($code));

        return true;
    }

    public function key(string $email): string
    {
        return 'registration:'.hash('sha256', Str::lower($email));
    }

    /**
     * Wrong codes are counted per email for ten minutes. Sending a new code
     * does not reset the count, so a resend never buys more guesses.
     */
    public function attemptsKey(string $email): string
    {
        return 'registration-attempts:'.hash('sha256', Str::lower($email));
    }
}
