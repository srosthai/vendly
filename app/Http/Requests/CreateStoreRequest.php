<?php

namespace App\Http\Requests;

use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;

class CreateStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Store::class) ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
