<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VerifyEmailCode
{
    public function __construct(private SendEmailCode $codes) {}

    public function handle(string $email, string $code, ?string $name): User
    {
        $email = Str::lower($email);
        $key = $this->codes->key($email);
        $cached = Cache::get($key);

        if (! is_array($cached) || ! isset($cached['hash'])) {
            throw ValidationException::withMessages([
                'code' => 'That code is invalid or has expired.',
            ]);
        }

        $attempts = (int) ($cached['attempts'] ?? 0);

        if ($attempts >= 5 || ! Hash::check($code, (string) $cached['hash'])) {
            Cache::put($key, [
                'hash' => $cached['hash'],
                'attempts' => $attempts + 1,
            ], now()->addMinutes(10));

            throw ValidationException::withMessages([
                'code' => 'That code is invalid or has expired.',
            ]);
        }

        Cache::forget($key);

        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $user = new User;
            $user->name = $name !== null && $name !== '' ? $name : Str::before($email, '@');
            $user->email = $email;
            $user->password = null;
        }

        $user->email_verified_at ??= now();
        $user->save();

        return $user;
    }
}
