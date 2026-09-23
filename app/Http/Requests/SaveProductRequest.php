<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Rules\UsdAmount;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        $product = $this->route('product');

        if ($product instanceof Product) {
            return $this->user()?->can('update', $product) ?? false;
        }

        return $this->user()?->can('create', Product::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $storeId = $this->user()?->store?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', new UsdAmount],
            'stock' => ['nullable', 'integer', 'min:0'],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('store_id', $storeId)],
            'brand_id' => ['nullable', 'integer', Rule::exists('brands', 'id')->where('store_id', $storeId)],
            'images' => ['nullable', 'array', 'max:8'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    /**
     * @return array{name: string, description: string|null, price_cents: int, stock: int|null, category_id: int|null, brand_id: int|null}
     */
    public function productAttributes(): array
    {
        $validated = $this->validated();

        return [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price_cents' => Money::toCents((string) $validated['price']),
            'stock' => $validated['stock'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
            'brand_id' => $validated['brand_id'] ?? null,
        ];
    }
}
