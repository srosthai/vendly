<?php

namespace App\Http\Requests;

use App\Models\Plan;
use App\Rules\UsdAmount;
use App\Support\Money;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SavePlanRequest extends FormRequest
{
    private const MaxPriceCents = 1_000_000;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Plan::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', new UsdAmount],
            'product_limit' => ['required', 'integer', 'min:0', 'max:100000'],
            'is_active' => ['required', 'boolean'],
            'is_default' => ['required', 'boolean'],
        ];
    }

    /**
     * The default plan is what every new store gets for free, so it must be
     * free and active, and there must always be one.
     *
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $priceCents = Money::toCents((string) $this->input('price'));

                if ($priceCents > self::MaxPriceCents) {
                    $validator->errors()->add('price', 'The price can be at most $10,000.00.');
                }

                $makingDefault = $this->boolean('is_default');

                if ($makingDefault && $priceCents !== 0) {
                    $validator->errors()->add('is_default', 'Only a free plan can be the default.');
                }

                if ($makingDefault && ! $this->boolean('is_active')) {
                    $validator->errors()->add('is_active', 'The default plan must stay active.');
                }

                $plan = $this->route('plan');

                if ($plan instanceof Plan && $plan->is_default && ! $makingDefault) {
                    $validator->errors()->add('is_default', 'Make another free plan the default first.');
                }
            },
        ];
    }

    /**
     * @return array{name: string, price_cents: int, product_limit: int, is_active: bool, is_default: bool}
     */
    public function planAttributes(): array
    {
        $validated = $this->validated();

        return [
            'name' => $validated['name'],
            'price_cents' => Money::toCents((string) $validated['price']),
            'product_limit' => (int) $validated['product_limit'],
            'is_active' => (bool) $validated['is_active'],
            'is_default' => (bool) $validated['is_default'],
        ];
    }
}
