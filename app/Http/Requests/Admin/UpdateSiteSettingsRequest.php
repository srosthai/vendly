<?php

namespace App\Http\Requests\Admin;

use App\Models\PlatformSetting;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSiteSettingsRequest extends FormRequest
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
        $socials = collect(PlatformSetting::SocialNetworks)
            ->mapWithKeys(fn (string $network): array => ["social_links.{$network}" => ['nullable', 'url:https', 'max:255']])
            ->all();

        return [
            'company_name' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^\+?[0-9 ()\-]{6,}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'footer_text' => ['nullable', 'string', 'max:300'],
            'social_links' => ['nullable', 'array'],
            ...$socials,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Use digits, spaces, and an optional + at the start.',
            'social_links.*.url' => 'Use a full link that starts with https://.',
        ];
    }
}
