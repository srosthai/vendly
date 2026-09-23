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
    /** @var list<string> */
    public const Events = ['payment.scanned', 'payment.completed', 'payment.expired', 'payment.failed'];

    public function __construct(private TelegramNotifier $telegram) {}

    /**
     * The delivery is `{id, type, created, data: {payment: {...}}}`. Its own
     * `id` names the event; the payment we track is `data.payment.id`.
     *
     * @param  array<string, mixed>  $payload
     */
    public function handle(string $event, array $payload): void
    {
        $remote = data_get($payload, 'data.payment');
        $cutluyId = is_array($remote) ? (string) ($remote['id'] ?? '') : '';

        if ($cutluyId === '') {
            Log::warning('CutLuy event is missing a payment id', ['event' => $event]);

            return;
        }

        DB::transaction(function () use ($event, $cutluyId, $remote): void {
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
                'payment.completed' => $this->complete($payment, $remote),
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

    /**
     * @param  array<string, mixed>  $remote
     */
    private function complete(SubscriptionPayment $payment, array $remote): void
    {
        if ($payment->status === PaymentStatus::Paid) {
            return;
        }

        if (! $this->matches($payment, $remote)) {
            Log::warning('CutLuy payment does not match the local payment', [
                'cutluy_id' => $payment->cutluy_id,
                'reference_id' => $remote['reference_id'] ?? null,
                'amount' => $remote['amount'] ?? null,
            ]);

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

    /**
     * A completed payment only extends a plan when CutLuy confirms the same
     * reference, amount, and currency that Vendly asked for.
     *
     * @param  array<string, mixed>  $remote
     */
    private function matches(SubscriptionPayment $payment, array $remote): bool
    {
        $amount = $remote['amount'] ?? null;

        return ($remote['reference_id'] ?? null) === $payment->public_id
            && is_numeric($amount)
            && (int) round(((float) $amount) * 100) === $payment->amount_cents
            && strtoupper((string) ($remote['currency'] ?? 'USD')) === 'USD';
    }
}
