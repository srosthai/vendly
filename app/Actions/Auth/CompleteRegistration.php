<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CompleteRegistration
{
    public function __construct(private StartRegistration $registration) {}

    public function handle(string $email, string $code): User
    {
        $email = Str::lower($email);
        $key = $this->registration->key($email);
        $cached = Cache::get($key);

        if (! is_array($cached) || ! isset($cached['hash'], $cached['password'], $cached['name'])) {
            throw ValidationException::withMessages([
                'code' => 'That code is invalid or has expired.',
            ]);
        }

        $attempts = (int) ($cached['attempts'] ?? 0);

        if ($attempts >= 5 || ! Hash::check($code, (string) $cached['hash'])) {
            Cache::put($key, [
                'name' => $cached['name'],
                'password' => $cached['password'],
                'hash' => $cached['hash'],
                'attempts' => $attempts + 1,
            ], now()->addMinutes(10));

            throw ValidationException::withMessages([
                'code' => 'That code is invalid or has expired.',
            ]);
        }

        if (User::query()->where('email', $email)->exists()) {
            Cache::forget($key);

            throw ValidationException::withMessages([
                'email' => 'An account with this email already exists. Log in instead.',
            ]);
        }

        Cache::forget($key);

        $user = new User;
        $user->name = (string) $cached['name'];
        $user->email = $email;
        $user->password = (string) $cached['password'];
        $user->email_verified_at = now();
        $user->save();

        return $user;
    }
}
