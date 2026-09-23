<?php

namespace App\Http\Controllers\Billing;

use App\Actions\Billing\CreatePlanPayment;
use App\Actions\Billing\RefreshPlanPayment;
use App\Http\Controllers\Concerns\ResolvesVendorStore;
use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\SubscriptionPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PlanPaymentController extends Controller
{
    use ResolvesVendorStore;

    public function store(Request $request, Plan $plan, CreatePlanPayment $action): JsonResponse|RedirectResponse
    {
        $store = $this->vendorStore($request);

        $payment = $action->handle($store, $plan);

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
        $payment = SubscriptionPayment::query()
            ->where('store_id', $this->vendorStore($request)->id)
            ->where('public_id', $publicId)
            ->firstOrFail();

        $notice = $request->boolean('refresh') ? $refresh->handle($payment) : null;

        return response()->json([
            ...$this->payload($payment->refresh()),
            'notice' => $notice,
        ]);
    }

    /**
     * @return array{public_id: string, status: string, checkout_url: string|null, qr_string: string|null, amount_cents: int}
     */
    private function payload(SubscriptionPayment $payment): array
    {
        return [
            'public_id' => $payment->public_id,
            'status' => $payment->status->value,
            'checkout_url' => $payment->checkout_url,
            'qr_string' => $payment->qr_string,
            'amount_cents' => $payment->amount_cents,
        ];
    }
}
