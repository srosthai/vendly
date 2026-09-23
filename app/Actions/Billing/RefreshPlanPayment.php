<?php

namespace App\Actions\Billing;

use App\Enums\PaymentStatus;
use App\Exceptions\CutluyRequestException;
use App\Models\SubscriptionPayment;
use App\Services\Cutluy\CutluyClient;
use Illuminate\Support\Facades\Log;

class RefreshPlanPayment
{
    /** @var array<string, string> */
    private const Events = [
        'scanned' => 'payment.scanned',
        'paid' => 'payment.completed',
        'expired' => 'payment.expired',
        'failed' => 'payment.failed',
    ];

    public function __construct(
        private CutluyClient $cutluy,
        private ApplyCutluyEvent $applyEvent,
    ) {}

    /**
     * Read the payment from CutLuy once and apply what it reports through the
     * same path as the webhook, so a later delivery of that event is ignored.
     *
     * @return string|null A message for the vendor when the read could not run.
     */
    public function handle(SubscriptionPayment $payment): ?string
    {
        if ($payment->cutluy_id === null || in_array($payment->status, [PaymentStatus::Paid, PaymentStatus::Expired, PaymentStatus::Failed], true)) {
            return null;
        }

        try {
            $remote = $this->cutluy->findPayment($payment->cutluy_id);
        } catch (CutluyRequestException $exception) {
            if ($exception->isRateLimited()) {
                return 'CutLuy is busy. Try again in '.$exception->retryAfter.' seconds.';
            }

            Log::warning('CutLuy payment status could not be read', [
                'status' => $exception->status,
                'error' => $exception->error,
                'payment' => $payment->public_id,
            ]);

            return 'The payment status is unavailable right now.';
        }

        $event = self::Events[(string) ($remote['status'] ?? '')] ?? null;

        if ($event !== null) {
            $this->applyEvent->handle($event, ['data' => ['payment' => $remote]]);
        }

        return null;
    }
}
