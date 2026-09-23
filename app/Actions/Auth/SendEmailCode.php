<?php

namespace App\Actions\Auth;

use App\Notifications\EmailSignInCode;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class SendEmailCode
{
    public function handle(string $email): void
    {
        $email = Str::lower($email);
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Cache::put($this->key($email), [
            'hash' => Hash::make($code),
            'attempts' => 0,
        ], now()->addMinutes(10));

        Notification::route('mail', $email)->notify(new EmailSignInCode($code));
    }

    public function key(string $email): string
    {
        return 'email-sign-in:'.hash('sha256', Str::lower($email));
    }
}
