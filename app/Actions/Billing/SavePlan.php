<?php

namespace App\Actions\Billing;

use App\Models\Plan;
use Illuminate\Support\Facades\DB;

class SavePlan
{
    /**
     * @param  array{name: string, price_cents: int, product_limit: int, is_active: bool, is_default: bool}  $attributes
     */
    public function handle(array $attributes, ?Plan $plan = null): Plan
    {
        return DB::transaction(function () use ($attributes, $plan): Plan {
            if ($attributes['is_default']) {
                Plan::query()
                    ->when($plan, fn ($query) => $query->whereKeyNot($plan->id))
                    ->update(['is_default' => false]);
            }

            $plan ??= new Plan;
            $plan->fill($attributes);
            $plan->save();

            return $plan;
        });
    }
}
