<?php

namespace App\Jobs;

use App\Actions\Billing\CreatePlanPayment;
use App\Enums\PaymentStatus;
use App\Exceptions\CutluyRequestException;
use App\Models\SubscriptionPayment;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The single delayed retry after CutLuy answered 429. It runs once and never
 * queues another retry.
 */
class RetryCutluyPayment implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 20;

    public function __construct(public int $paymentId) {}

    public function uniqueId(): string
    {
        return (string) $this->paymentId;
    }

    public function handle(CreatePlanPayment $action): void
    {
        $payment = SubscriptionPayment::query()->find($this->paymentId);

        if ($payment === null || $payment->status !== PaymentStatus::Pending || $payment->cutluy_id !== null) {
            return;
        }

        try {
            $action->send($payment);
        } catch (CutluyRequestException $exception) {
            if ($exception->isRateLimited()) {
                Log::warning('CutLuy is still rate limiting; the payment retry stopped', [
                    'payment' => $payment->public_id,
                ]);

                return;
            }

            $action->reportUnavailable($payment, $exception);
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('CutLuy payment retry failed', [
            'payment_id' => $this->paymentId,
            'error' => $exception === null ? null : $exception::class,
        ]);
    }
}
