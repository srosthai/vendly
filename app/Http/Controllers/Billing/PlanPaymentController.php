<?php

namespace App\Http\Controllers\Billing;

use App\Actions\Billing\CreatePlanPayment;
use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PlanPaymentController extends Controller
{
    public function store(Request $request, Plan $plan, CreatePlanPayment $action): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $store = $user->store;
        abort_if($store === null || $store->isSuspended(), 403);

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
