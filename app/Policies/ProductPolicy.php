<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function create(User $user): bool
    {
        $store = $user->store;

        return $store !== null && ! $store->isSuspended();
    }

    public function update(User $user, Product $product): bool
    {
        $product->loadMissing('store');

        return $product->store->user_id === $user->id && ! $product->store->isSuspended();
    }
}
