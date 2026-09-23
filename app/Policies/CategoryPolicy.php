<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function create(User $user): bool
    {
        $store = $user->store;

        return $store !== null && ! $store->isSuspended();
    }

    public function update(User $user, Category $category): bool
    {
        $category->loadMissing('store');

        return $category->store->user_id === $user->id && ! $category->store->isSuspended();
    }

    public function delete(User $user, Category $category): bool
    {
        return $this->update($user, $category);
    }
}
