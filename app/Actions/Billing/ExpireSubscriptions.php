<?php

namespace App\Actions\Billing;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Services\Telegram\TelegramNotifier;
use Illuminate\Support\Facades\DB;

class ExpireSubscriptions
{
    public function __construct(private TelegramNotifier $telegram) {}

    /**
     * Each row is re-read under a lock before it is expired, so a renewal
     * that lands between the scan and the update keeps the plan active.
     */
    public function handle(): int
    {
        $expired = 0;

        Subscription::query()
            ->where('status', SubscriptionStatus::Active)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', now())
            ->orderBy('id')
            ->lazyById()
            ->each(function (Subscription $candidate) use (&$expired): void {
                $subscription = DB::transaction(function () use ($candidate): ?Subscription {
                    $subscription = Subscription::query()->whereKey($candidate->id)->lockForUpdate()->first();

                    if ($subscription === null
                        || $subscription->status !== SubscriptionStatus::Active
                        || $subscription->ends_at === null
                        || ! $subscription->ends_at->isPast()) {
                        return null;
                    }

                    $subscription->status = SubscriptionStatus::Expired;
                    $subscription->save();

                    return $subscription;
                });

                if ($subscription !== null) {
                    $this->telegram->planExpired($subscription->store()->firstOrFail());
                    $expired++;
                }
            });

        return $expired;
    }
}
