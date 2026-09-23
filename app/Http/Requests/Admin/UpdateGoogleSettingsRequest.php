<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Google sign-in from Site settings. The client id is shown as it is; the
 * secret is write-only, so an empty field keeps the saved one.
 */
class UpdateGoogleSettingsRequest extends FormRequest
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
            'enabled' => ['required', 'boolean'],
            'client_id' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9\-_.]+$/'],
            'client_secret' => ['nullable', 'string', 'min:8', 'max:255', 'not_regex:/\s/'],
            'clear_client_secret' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'client_id.regex' => 'Copy the client ID exactly as Google shows it, such as 1234-abc.apps.googleusercontent.com.',
            'client_secret.not_regex' => 'The client secret cannot contain spaces.',
        ];
    }
}
