<?php

namespace App\Actions\Stores;

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Store;
use App\Models\User;
use App\Services\Telegram\TelegramNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateStore
{
    public function __construct(private TelegramNotifier $telegram) {}

    public function handle(User $user, string $name, ?string $description = null): Store
    {
        $slug = Str::slug($name);

        if ($slug === '') {
            throw ValidationException::withMessages([
                'name' => 'Use a store name that can appear in a link.',
            ]);
        }

        $store = DB::transaction(function () use ($user, $name, $slug, $description): Store {
            $owner = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            if ($owner->store()->exists() || Store::query()->where('slug', $slug)->exists()) {
                throw ValidationException::withMessages([
                    'name' => 'Choose a different store name.',
                ]);
            }

            $plan = Plan::query()
                ->where('is_default', true)
                ->where('is_active', true)
                ->orderBy('id')
                ->firstOrFail();

            $store = Store::query()->create([
                'user_id' => $owner->id,
                'name' => strip_tags($name),
                'slug' => $slug,
                'description' => $description === null ? null : strip_tags($description),
                'currency' => 'USD',
            ]);

            $store->subscription()->create([
                'plan_id' => $plan->id,
                'status' => SubscriptionStatus::Active,
                'starts_at' => now(),
                'ends_at' => null,
            ]);

            return $store;
        });

        $this->telegram->newStore($store);

        return $store;
    }
}
