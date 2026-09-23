<?php

namespace App\Http\Controllers\Billing;

use App\Actions\Billing\CreatePlanPayment;
use App\Http\Controllers\Concerns\ResolvesVendorStore;
use App\Http\Controllers\Controller;
use App\Models\Plan;
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

        $payload = [
            'public_id' => $payment->public_id,
            'status' => $payment->status->value,
            'checkout_url' => $payment->checkout_url,
            'qr_string' => $payment->qr_string,
            'amount_cents' => $payment->amount_cents,
        ];

        if ($request->expectsJson()) {
            return response()->json($payload);
        }

        return back()->with('payment', $payload);
    }
}
