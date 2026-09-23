<?php

namespace App\Actions\Auth;

use App\Models\User;

class ReleaseUnverifiedEmail
{
    /**
     * Find the account that owns this email. An account that never proved the
     * email gives it up, so the person who just proved it gets a clean account
     * instead of one whose password or passkeys someone else may hold.
     */
    public function handle(string $email): ?User
    {
        $user = User::query()->where('email', $email)->first();

        if ($user === null || $user->hasVerifiedEmail()) {
            return $user;
        }

        $user->email = null;
        $user->email_verified_at = null;
        $user->save();

        return null;
    }
}
