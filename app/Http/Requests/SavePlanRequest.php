<?php

namespace App\Http\Requests;

use App\Models\Plan;
use App\Rules\UsdAmount;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;

class SavePlanRequest extends FormRequest
{
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
            'product_limit' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
            'is_default' => ['required', 'boolean'],
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
