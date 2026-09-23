<?php

namespace App\Actions\Billing;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Services\Telegram\TelegramNotifier;

class ExpireSubscriptions
{
    public function __construct(private TelegramNotifier $telegram) {}

    public function handle(): int
    {
        $expired = 0;

        Subscription::query()
            ->where('status', SubscriptionStatus::Active)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', now())
            ->orderBy('id')
            ->lazyById()
            ->each(function (Subscription $subscription) use (&$expired): void {
                $subscription->status = SubscriptionStatus::Expired;
                $subscription->save();
                $this->telegram->planExpired($subscription->store()->firstOrFail());
                $expired++;
            });

        return $expired;
    }
}
