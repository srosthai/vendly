<?php

namespace App\Actions\Billing;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\CutluyEvent;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Services\Telegram\TelegramNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApplyCutluyEvent
{
    public function __construct(private TelegramNotifier $telegram) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(string $event, array $payload): void
    {
        $cutluyId = (string) ($payload['id'] ?? '');

        if ($cutluyId === '') {
            Log::warning('CutLuy event is missing a payment id', ['event' => $event]);

            return;
        }

        DB::transaction(function () use ($event, $cutluyId): void {
            $record = CutluyEvent::query()->firstOrCreate([
                'cutluy_payment_id' => $cutluyId,
                'event' => $event,
            ]);

            if (! $record->wasRecentlyCreated) {
                return;
            }

            $payment = SubscriptionPayment::query()
                ->where('cutluy_id', $cutluyId)
                ->lockForUpdate()
                ->first();

            if ($payment === null) {
                Log::warning('CutLuy event did not match a payment', [
                    'event' => $event,
                    'cutluy_id' => $cutluyId,
                ]);

                return;
            }

            match ($event) {
                'payment.scanned' => $this->mark($payment, PaymentStatus::Scanned),
                'payment.completed' => $this->complete($payment),
                'payment.expired' => $this->mark($payment, PaymentStatus::Expired),
                'payment.failed' => $this->mark($payment, PaymentStatus::Failed),
                default => Log::info('Ignoring CutLuy event', ['event' => $event]),
            };
        });
    }

    private function mark(SubscriptionPayment $payment, PaymentStatus $status): void
    {
        if ($payment->status === PaymentStatus::Paid) {
            return;
        }

        $payment->status = $status;
        $payment->save();
    }

    private function complete(SubscriptionPayment $payment): void
    {
        if ($payment->status === PaymentStatus::Paid) {
            return;
        }

        $payment->status = PaymentStatus::Paid;
        $payment->paid_at = now();
        $payment->save();

        $subscription = Subscription::query()
            ->where('store_id', $payment->store_id)
            ->lockForUpdate()
            ->firstOrFail();

        $base = $subscription->ends_at !== null && $subscription->ends_at->greaterThan(now())
            ? $subscription->ends_at
            : now();

        $subscription->plan_id = $payment->plan_id;
        $subscription->status = SubscriptionStatus::Active;
        $subscription->ends_at = $base->addMonth();
        $subscription->save();

        $this->telegram->planPaid($subscription->store()->firstOrFail());
    }
}
