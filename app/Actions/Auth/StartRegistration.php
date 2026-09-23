<?php

namespace App\Actions\Auth;

use App\Notifications\EmailSignInCode;
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
            'attempts' => 0,
        ], now()->addMinutes(10));

        Notification::route('mail', $email)->notify(new EmailSignInCode($code, 'registration'));
    }

    public function key(string $email): string
    {
        return 'registration:'.hash('sha256', Str::lower($email));
    }
}
