<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class AuthenticateGoogleUser
{
    public function handle(SocialiteUser $googleUser): User
    {
        $email = Str::lower((string) $googleUser->getEmail());

        if ($email === '') {
            throw ValidationException::withMessages([
                'email' => 'Google did not share an email address.',
            ]);
        }

        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $user = new User;
            $user->email = $email;
            $user->password = null;
            $user->name = $googleUser->getName() ?: Str::before($email, '@');
        }

        $user->email_verified_at ??= now();
        $user->save();

        return $user;
    }
}
