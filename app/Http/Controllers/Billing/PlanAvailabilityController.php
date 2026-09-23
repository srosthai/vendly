<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PlanAvailabilityController extends Controller
{
    /**
     * Show or hide a plan for vendors. The default plan is what every new
     * store starts on, so it can never be hidden.
     */
    public function __invoke(Request $request, Plan $plan): RedirectResponse
    {
        $this->authorize('create', Plan::class);
        $available = $request->validate(['is_active' => ['required', 'boolean']])['is_active'];

        if (! $available && $plan->is_default) {
            throw ValidationException::withMessages([
                'is_active' => 'The default plan stays available. Make another free plan the default first.',
            ]);
        }

        $plan->update(['is_active' => (bool) $available]);
        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $plan->name.($available ? ' is available to vendors.' : ' is hidden from vendors.'),
        ]);

        return back();
    }
}
