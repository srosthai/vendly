<?php

namespace App\Http\Requests;

use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The vendor's store profile: identity, contact, and social links. Every
 * detail beyond the name is optional; an empty field removes it.
 */
class UpdateStoreRequest extends FormRequest
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
        $socials = collect(Store::SocialNetworks)
            ->mapWithKeys(fn (string $network): array => ["social_links.{$network}" => ['nullable', 'url:https', 'max:255']])
            ->all();

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'],
            'remove_logo' => ['sometimes', 'boolean'],
            'accent' => ['nullable', Rule::in(Store::Accents)],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^\+?[0-9 ()\-]{6,}$/'],
            'address' => ['nullable', 'string', 'max:255'],
            'hours' => ['nullable', 'string', 'max:120'],
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
            'phone.regex' => 'Use digits, spaces, and an optional + at the start, such as +855 12 345 678.',
            'social_links.*.url' => 'Use a full link that starts with https://.',
        ];
    }

    /**
     * The profile fields to save, with tags stripped and empty values as
     * null.
     *
     * @return array{name: string, description: string|null, accent: string|null, phone: string|null, address: string|null, hours: string|null, social_links: array<string, string>}
     */
    public function profile(): array
    {
        $validated = $this->validated();
        $text = fn (string $key): ?string => filled($validated[$key] ?? null) ? trim(strip_tags((string) $validated[$key])) : null;

        return [
            'name' => (string) $text('name'),
            'description' => $text('description'),
            'accent' => $validated['accent'] ?? null,
            'phone' => $text('phone'),
            'address' => $text('address'),
            'hours' => $text('hours'),
            'social_links' => array_filter(
                array_intersect_key($validated['social_links'] ?? [], array_flip(Store::SocialNetworks)),
                fn (mixed $link): bool => is_string($link) && $link !== '',
            ),
        ];
    }
}
