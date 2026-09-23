<?php

namespace App\Actions\Stores;

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Store;
use App\Models\User;
use App\Services\Telegram\TelegramNotifier;
use App\Support\Slug;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateStore
{
    public function __construct(private TelegramNotifier $telegram) {}

    /**
     * With no slug, one is made from the name. A name with no Latin letters,
     * such as Khmer, gets a short random slug, so any name can open a store.
     */
    public function handle(User $user, string $name, ?string $description = null, ?string $slug = null): Store
    {
        try {
            $store = DB::transaction(fn (): Store => $this->create($user, $name, $description, $slug));
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'slug' => 'Another store already uses that link.',
            ]);
        }

        $this->telegram->newStore($store);

        return $store;
    }

    private function create(User $user, string $name, ?string $description, ?string $slug): Store
    {
        $owner = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

        if ($owner->store()->exists()) {
            throw ValidationException::withMessages([
                'name' => 'You already have a store.',
            ]);
        }

        if ($slug !== null && Store::query()->where('slug', $slug)->exists()) {
            throw ValidationException::withMessages([
                'slug' => 'Another store already uses that link.',
            ]);
        }

        if ($slug === null) {
            $slug = Slug::unique($name, Store::query(), 'store', Store::MaxSlugLength - 4);

            if (in_array($slug, Store::ReservedSlugs, true)) {
                $slug = Slug::unique($slug.'-shop', Store::query(), 'store', Store::MaxSlugLength - 4);
            }
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
    }
}
