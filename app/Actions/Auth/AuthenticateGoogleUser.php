<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Two\User as OAuthTwoUser;

class AuthenticateGoogleUser
{
    public function __construct(private ReleaseUnverifiedEmail $releaseUnverifiedEmail) {}

    public function handle(SocialiteUser $googleUser): User
    {
        $email = Str::lower((string) $googleUser->getEmail());

        if ($email === '') {
            throw ValidationException::withMessages([
                'email' => 'Google did not share an email address.',
            ]);
        }

        if (! $this->googleVerified($googleUser)) {
            throw ValidationException::withMessages([
                'email' => 'Google has not verified this email address.',
            ]);
        }

        $user = $this->releaseUnverifiedEmail->handle($email);

        if ($user === null) {
            $user = new User;
            $user->email = $email;
            $user->password = null;
            $user->name = $googleUser->getName() ?: Str::before($email, '@');
            $user->email_verified_at = now();
            $user->save();
        }

        return $user;
    }

    private function googleVerified(SocialiteUser $googleUser): bool
    {
        $raw = $googleUser instanceof OAuthTwoUser ? $googleUser->getRaw() : [];

        return filter_var($raw['email_verified'] ?? $raw['verified_email'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }
}
