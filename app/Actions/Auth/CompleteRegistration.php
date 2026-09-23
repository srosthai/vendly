<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CompleteRegistration
{
    private const MaxAttempts = 5;

    public function __construct(
        private StartRegistration $registration,
        private ReleaseUnverifiedEmail $releaseUnverifiedEmail,
    ) {}

    public function handle(string $email, string $code): User
    {
        $email = Str::lower($email);
        $key = $this->registration->key($email);
        $attemptsKey = $this->registration->attemptsKey($email);
        $cached = Cache::get($key);

        if (! is_array($cached) || ! isset($cached['hash'], $cached['password'], $cached['name'])) {
            throw ValidationException::withMessages([
                'code' => 'That code is invalid or has expired.',
            ]);
        }

        if (RateLimiter::hit($attemptsKey, 600) > self::MaxAttempts) {
            throw ValidationException::withMessages([
                'code' => 'Too many wrong codes. Try again in '.ceil(RateLimiter::availableIn($attemptsKey) / 60).' minutes.',
            ]);
        }

        if (! Hash::check($code, (string) $cached['hash'])) {
            throw ValidationException::withMessages([
                'code' => 'That code is invalid or has expired.',
            ]);
        }

        Cache::forget($key);
        RateLimiter::clear($attemptsKey);

        if ($this->releaseUnverifiedEmail->handle($email) !== null) {
            throw ValidationException::withMessages([
                'email' => 'An account with this email already exists. Log in instead.',
            ]);
        }

        $user = new User;
        $user->name = (string) $cached['name'];
        $user->email = $email;
        $user->password = (string) $cached['password'];
        $user->email_verified_at = now();
        $user->save();

        return $user;
    }
}
