<?php

namespace App\Actions\Billing;

use App\Enums\BillingPeriod;
use App\Enums\PaymentStatus;
use App\Exceptions\CutluyRequestException;
use App\Jobs\RetryCutluyPayment;
use App\Models\Plan;
use App\Models\Store;
use App\Models\SubscriptionPayment;
use App\Services\Cutluy\CutluyClient;
use App\Services\Telegram\TelegramNotifier;
use App\Support\Money;
use Carbon\CarbonInterface;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreatePlanPayment
{
    private const QrMinutes = 5;

    /**
     * A QR about to run out is not worth showing again.
     */
    private const MinimumSecondsLeft = 30;

    public function __construct(
        private CutluyClient $cutluy,
        private TelegramNotifier $telegram,
    ) {}

    public function handle(Store $store, Plan $plan, BillingPeriod $period = BillingPeriod::Monthly): SubscriptionPayment
    {
        if (! $plan->is_active || $plan->isFree()) {
            throw ValidationException::withMessages([
                'plan' => 'Choose a paid plan.',
            ]);
        }

        $amount = $plan->priceFor($period);

        if ($amount === null) {
            throw ValidationException::withMessages([
                'plan' => $plan->name.' is paid monthly only.',
            ]);
        }

        $payment = $this->pendingPayment($store, $plan, $period, $amount);

        if ($payment->cutluy_id !== null) {
            return $payment;
        }

        try {
            $this->send($payment);
        } catch (CutluyRequestException $exception) {
            if ($exception->isRateLimited()) {
                RetryCutluyPayment::dispatch($payment->id)->delay(now()->addSeconds($exception->retryAfter));

                throw ValidationException::withMessages([
                    'plan' => 'Try again in '.$exception->retryAfter.' seconds.',
                ]);
            }

            $this->reportUnavailable($payment, $exception);

            throw ValidationException::withMessages([
                'plan' => 'Payments are unavailable.',
            ]);
        }

        return $payment;
    }

    /**
     * Create the CutLuy payment for a local row. The local public id is both
     * the reference and the idempotency key, so a repeat returns the same
     * CutLuy payment.
     *
     * @throws CutluyRequestException
     */
    public function send(SubscriptionPayment $payment): void
    {
        $created = $this->cutluy->createPayment(
            Money::dollars($payment->amount_cents),
            $payment->public_id,
            [
                'store_id' => $payment->store_id,
                'plan_id' => $payment->plan_id,
                'period' => $payment->period->value,
            ],
            $payment->public_id,
        );

        $payment->cutluy_id = $created['id'];
        $payment->checkout_url = $created['checkout_url'];
        $payment->qr_string = $created['qr_string'];
        $payment->expires_at = $this->expiresAt($created['expires_at']);
        $payment->save();
    }

    /**
     * CutLuy's KHQR lasts five minutes. Its own time wins; a missing or
     * unreadable one falls back to five minutes from now.
     */
    private function expiresAt(?string $expiresAt): CarbonInterface
    {
        try {
            return $expiresAt === null ? now()->addMinutes(self::QrMinutes) : Carbon::parse($expiresAt);
        } catch (InvalidFormatException) {
            return now()->addMinutes(self::QrMinutes);
        }
    }

    /**
     * The vendor only ever sees "Payments are unavailable". The admin chat
     * hears about it, and the log keeps the status and error code.
     */
    public function reportUnavailable(SubscriptionPayment $payment, CutluyRequestException $exception): void
    {
        Log::warning('CutLuy payment was not created', [
            'status' => $exception->status,
            'error' => $exception->error,
            'payment' => $payment->public_id,
        ]);

        $this->telegram->paymentsUnavailable($exception->error);
    }

    /**
     * The store row is locked while the pending payment is found or created,
     * so a double click shares one local payment and one idempotency key. A
     * pending payment is reused only for the same plan, period, and amount,
     * and only while its QR has time left. One that never reached CutLuy has
     * no time yet, so a retry keeps its idempotency key.
     */
    private function pendingPayment(Store $store, Plan $plan, BillingPeriod $period, int $amount): SubscriptionPayment
    {
        return DB::transaction(function () use ($store, $plan, $period, $amount): SubscriptionPayment {
            Store::query()->whereKey($store->id)->lockForUpdate()->first();

            $payment = SubscriptionPayment::query()
                ->where('store_id', $store->id)
                ->where('plan_id', $plan->id)
                ->where('period', $period)
                ->where('amount_cents', $amount)
                ->where('status', PaymentStatus::Pending)
                ->where(fn ($query) => $query
                    ->where('expires_at', '>', now()->addSeconds(self::MinimumSecondsLeft))
                    ->orWhere(fn ($query) => $query->whereNull('expires_at')->whereNull('cutluy_id')))
                ->latest('id')
                ->first();

            return $payment ?? SubscriptionPayment::query()->create([
                'public_id' => 'subpay_'.Str::lower((string) Str::ulid()),
                'store_id' => $store->id,
                'plan_id' => $plan->id,
                'amount_cents' => $amount,
                'period' => $period,
                'status' => PaymentStatus::Pending,
            ]);
        });
    }
}
