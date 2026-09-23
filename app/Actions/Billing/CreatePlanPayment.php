<?php

namespace App\Actions\Billing;

use App\Enums\PaymentStatus;
use App\Exceptions\CutluyRequestException;
use App\Jobs\RetryCutluyPayment;
use App\Models\Plan;
use App\Models\Store;
use App\Models\SubscriptionPayment;
use App\Services\Cutluy\CutluyClient;
use App\Services\Telegram\TelegramNotifier;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreatePlanPayment
{
    public function __construct(
        private CutluyClient $cutluy,
        private TelegramNotifier $telegram,
    ) {}

    public function handle(Store $store, Plan $plan): SubscriptionPayment
    {
        if (! $plan->is_active || $plan->isFree()) {
            throw ValidationException::withMessages([
                'plan' => 'Choose a paid plan.',
            ]);
        }

        $payment = $this->pendingPayment($store, $plan);

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
            ],
            $payment->public_id,
        );

        $payment->cutluy_id = $created['id'];
        $payment->checkout_url = $created['checkout_url'];
        $payment->qr_string = $created['qr_string'];
        $payment->save();
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
     * so a double click shares one local payment and one idempotency key.
     */
    private function pendingPayment(Store $store, Plan $plan): SubscriptionPayment
    {
        return DB::transaction(function () use ($store, $plan): SubscriptionPayment {
            Store::query()->whereKey($store->id)->lockForUpdate()->first();

            $payment = SubscriptionPayment::query()
                ->where('store_id', $store->id)
                ->where('plan_id', $plan->id)
                ->where('status', PaymentStatus::Pending)
                ->latest('id')
                ->first();

            return $payment ?? SubscriptionPayment::query()->create([
                'public_id' => 'subpay_'.Str::lower((string) Str::ulid()),
                'store_id' => $store->id,
                'plan_id' => $plan->id,
                'amount_cents' => $plan->price_cents,
                'status' => PaymentStatus::Pending,
            ]);
        });
    }
}
