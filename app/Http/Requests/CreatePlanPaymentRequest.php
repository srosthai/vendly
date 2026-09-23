<?php

namespace App\Http\Requests;

use App\Enums\BillingPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Starting a plan payment. The vendor's own store is resolved by the
 * controller; this only checks the billing period.
 */
class CreatePlanPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'period' => ['nullable', Rule::enum(BillingPeriod::class)],
        ];
    }

    public function period(): BillingPeriod
    {
        return BillingPeriod::tryFrom((string) $this->input('period')) ?? BillingPeriod::Monthly;
    }
}
