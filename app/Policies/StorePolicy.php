<?php

namespace App\Policies;

use App\Models\Store;
use App\Models\User;

class StorePolicy
{
    public function create(User $user): bool
    {
        return ! $user->store()->exists();
    }

    public function update(User $user, Store $store): bool
    {
        return $store->user_id === $user->id && ! $store->isSuspended();
    }

    public function suspend(User $user, Store $store): bool
    {
        return $user->is_admin;
    }
}
