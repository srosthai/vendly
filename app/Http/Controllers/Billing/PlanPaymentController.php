<?php

namespace App\Http\Controllers\Billing;

use App\Actions\Billing\CreatePlanPayment;
use App\Actions\Billing\RefreshPlanPayment;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Concerns\ResolvesVendorStore;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreatePlanPaymentRequest;
use App\Models\Plan;
use App\Models\SubscriptionPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PlanPaymentController extends Controller
{
    use ResolvesVendorStore;

    public function store(CreatePlanPaymentRequest $request, Plan $plan, CreatePlanPayment $action): JsonResponse|RedirectResponse
    {
        $store = $this->vendorStore($request);

        $payment = $action->handle($store, $plan, $request->period());

        $payload = $this->payload($payment);

        if ($request->expectsJson()) {
            return response()->json($payload);
        }

        return back()->with('payment', $payload);
    }

    /**
     * The QR dialog's status. `refresh=1` performs one CutLuy read first.
     */
    public function show(Request $request, string $publicId, RefreshPlanPayment $refresh): JsonResponse
    {
        $payment = $this->vendorPayment($request, $publicId);

        $notice = $request->boolean('refresh') ? $refresh->handle($payment) : null;
        $payment->refresh();

        if ($payment->hasLapsed()) {
            $payment->update(['status' => PaymentStatus::Expired]);
        }

        return response()->json([
            ...$this->payload($payment),
            'notice' => $notice,
        ]);
    }

    /**
     * The vendor closed the QR. CutLuy has no cancel, so the QR stays
     * payable until it expires; a payment that still arrives activates the
     * plan.
     */
    public function cancel(Request $request, string $publicId): JsonResponse
    {
        $payment = $this->vendorPayment($request, $publicId);

        if ($payment->status->isOpen()) {
            $payment->update(['status' => PaymentStatus::Canceled]);
        }

        return response()->json($this->payload($payment));
    }

    private function vendorPayment(Request $request, string $publicId): SubscriptionPayment
    {
        return SubscriptionPayment::query()
            ->where('store_id', $this->vendorStore($request)->id)
            ->where('public_id', $publicId)
            ->firstOrFail();
    }

    /**
     * `expires_in` is seconds, so the countdown does not depend on the
     * vendor's clock.
     *
     * @return array{public_id: string, status: string, qr_string: string|null, qr_image: string|null, expires_in: int|null, plan_id: int, amount_cents: int, period: string}
     */
    private function payload(SubscriptionPayment $payment): array
    {
        return [
            'public_id' => $payment->public_id,
            'status' => $payment->status->value,
            'qr_string' => $payment->qr_string,
            'qr_image' => $payment->qrImageUrl(),
            'expires_in' => $payment->expires_at === null ? null : max(0, (int) now()->diffInSeconds($payment->expires_at, false)),
            'plan_id' => $payment->plan_id,
            'amount_cents' => $payment->amount_cents,
            'period' => $payment->period->value,
        ];
    }
}
