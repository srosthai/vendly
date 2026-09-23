<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UsdAmount implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) && ! is_numeric($value)) {
            $fail('The :attribute must be a USD amount.');

            return;
        }

        $amount = is_string($value) ? $value : (string) $value;

        if (! preg_match('/^\d+(\.\d{1,2})?$/', $amount)) {
            $fail('The :attribute must be at least $0.01 when it is a paid amount, with at most two decimal places.');
        }
    }
}
