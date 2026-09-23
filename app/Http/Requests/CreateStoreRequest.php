<?php

namespace App\Http\Requests;

use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Store::class) ?? false;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.regex' => 'Use lowercase letters, numbers, and single dashes.',
            'slug.not_in' => 'That link is reserved. Choose another.',
            'slug.unique' => 'Another store already uses that link.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'min:3',
                'max:'.Store::MaxSlugLength,
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::notIn(Store::ReservedSlugs),
                Rule::unique('stores', 'slug'),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
