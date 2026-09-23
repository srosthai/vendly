<?php

namespace App\Http\Controllers\Billing;

use App\Actions\Billing\SavePlan;
use App\Http\Controllers\Controller;
use App\Http\Requests\SavePlanRequest;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;

class PlanController extends Controller
{
    public function store(SavePlanRequest $request, SavePlan $action): RedirectResponse
    {
        $action->handle($request->planAttributes());

        return back();
    }

    public function update(SavePlanRequest $request, Plan $plan, SavePlan $action): RedirectResponse
    {
        $action->handle($request->planAttributes(), $plan);

        return back();
    }
}
