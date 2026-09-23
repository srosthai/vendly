<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * CutLuy credentials from Site settings. The key and secret are write-only:
 * an empty field keeps what is saved, and the clear flags remove it.
 */
class UpdateCutluySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_admin === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'api_key' => ['nullable', 'string', 'min:8', 'max:255', 'not_regex:/\s/'],
            'webhook_secret' => ['nullable', 'string', 'min:8', 'max:255', 'not_regex:/\s/'],
            'base_url' => ['nullable', 'url:https', 'max:255'],
            'clear_api_key' => ['boolean'],
            'clear_webhook_secret' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'api_key.not_regex' => 'The API key cannot contain spaces.',
            'webhook_secret.not_regex' => 'The webhook secret cannot contain spaces.',
            'base_url.url' => 'Use a full https:// address.',
        ];
    }
}
