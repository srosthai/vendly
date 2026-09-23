<?php

namespace App\Policies;

use App\Models\Brand;
use App\Models\User;

class BrandPolicy
{
    public function create(User $user): bool
    {
        $store = $user->store;

        return $store !== null && ! $store->isSuspended();
    }

    public function update(User $user, Brand $brand): bool
    {
        $brand->loadMissing('store');

        return $brand->store->user_id === $user->id && ! $brand->store->isSuspended();
    }

    public function delete(User $user, Brand $brand): bool
    {
        return $this->update($user, $brand);
    }
}
