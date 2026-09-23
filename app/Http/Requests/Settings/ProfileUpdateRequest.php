<?php

namespace App\Http\Requests\Settings;

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    use ProfileValidationRules;

    /**
     * A Telegram account may keep its email empty.
     *
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = $this->profileRules($this->user()->id);

        if ($this->user()->telegram_id !== null) {
            $rules['email'] = ['nullable', ...array_slice($rules['email'], 1)];
        }

        return [
            ...$rules,
            'phone' => ['nullable', 'string', 'max:32', 'regex:/^\+?[0-9 ()\-]{6,}$/'],
            'telegram_username' => ['nullable', 'string', 'max:32', 'regex:/^@?[A-Za-z0-9_]{5,32}$/'],
            'bio' => ['nullable', 'string', 'max:500'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'],
            'remove_avatar' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Use digits, spaces, and an optional + at the start.',
            'telegram_username.regex' => 'A Telegram username is 5 to 32 letters, numbers, or underscores.',
        ];
    }
}
