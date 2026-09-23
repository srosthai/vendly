<?php

namespace App\Actions\Billing;

use App\Enums\PaymentStatus;
use App\Exceptions\CutluyRequestException;
use App\Models\Plan;
use App\Models\Store;
use App\Models\SubscriptionPayment;
use App\Services\Cutluy\CutluyClient;
use App\Services\Telegram\TelegramNotifier;
use App\Support\Money;
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

        $payment = SubscriptionPayment::query()
            ->where('store_id', $store->id)
            ->where('plan_id', $plan->id)
            ->where('status', PaymentStatus::Pending)
            ->latest('id')
            ->first();

        if ($payment?->cutluy_id !== null) {
            return $payment;
        }

        $payment ??= SubscriptionPayment::query()->create([
            'public_id' => (string) Str::ulid(),
            'store_id' => $store->id,
            'plan_id' => $plan->id,
            'amount_cents' => $plan->price_cents,
            'status' => PaymentStatus::Pending,
        ]);

        try {
            $created = $this->cutluy->createPayment(
                Money::dollars($payment->amount_cents),
                $payment->public_id,
                [
                    'store_id' => $store->id,
                    'plan_id' => $plan->id,
                ],
                $payment->public_id,
            );
        } catch (CutluyRequestException $exception) {
            Log::warning('CutLuy payment was not created', [
                'status' => $exception->status,
                'error' => $exception->error,
                'payment' => $payment->public_id,
            ]);
            $this->telegram->paymentsUnavailable($exception->error);

            throw ValidationException::withMessages([
                'plan' => 'Payments are unavailable.',
            ]);
        }

        $payment->cutluy_id = $created['id'];
        $payment->checkout_url = $created['checkout_url'];
        $payment->qr_string = $created['qr_string'];
        $payment->save();

        return $payment;
    }
}
