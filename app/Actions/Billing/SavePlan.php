<?php

namespace App\Actions\Billing;

use App\Models\Plan;
use Illuminate\Support\Facades\DB;

class SavePlan
{
    /**
     * Plans are locked first so two saves cannot both leave a default behind.
     *
     * @param  array{name: string, price_cents: int, yearly_price_cents: int|null, product_limit: int, is_active: bool, is_default: bool}  $attributes
     */
    public function handle(array $attributes, ?Plan $plan = null): Plan
    {
        return DB::transaction(function () use ($attributes, $plan): Plan {
            Plan::query()->lockForUpdate()->get(['id']);

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
