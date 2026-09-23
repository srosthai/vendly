<?php

namespace App\Actions\Catalog;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaveProduct
{
    /**
     * @param  array{name: string, description?: string|null, price_cents: int, stock?: int|null, category_id?: int|null, brand_id?: int|null}  $attributes
     * @param  array<int, UploadedFile>  $images
     */
    public function handle(Store $store, array $attributes, array $images = [], ?Product $product = null): Product
    {
        return DB::transaction(function () use ($store, $attributes, $images, $product): Product {
            $name = strip_tags($attributes['name']);
            $slug = Str::slug($name);

            if ($slug === '') {
                throw ValidationException::withMessages([
                    'name' => 'Use a product name that can appear in a link.',
                ]);
            }

            $slugTaken = $store->products()
                ->when($product, fn ($query) => $query->whereKeyNot($product->id))
                ->where('slug', $slug)
                ->exists();

            if ($slugTaken) {
                throw ValidationException::withMessages([
                    'name' => 'Choose a different product name.',
                ]);
            }

            $product ??= new Product([
                'store_id' => $store->id,
                'status' => ProductStatus::Draft,
            ]);

            $product->fill([
                'name' => $name,
                'slug' => $slug,
                'description' => isset($attributes['description']) ? strip_tags((string) $attributes['description']) : $product->description,
                'price_cents' => $attributes['price_cents'],
                'stock' => $attributes['stock'] ?? $product->stock,
                'category_id' => $attributes['category_id'] ?? null,
                'brand_id' => $attributes['brand_id'] ?? null,
            ]);
            $product->store_id = $store->id;
            $product->save();

            $sort = (int) $product->images()->max('sort');

            foreach ($images as $image) {
                $sort++;
                $product->images()->create([
                    'path' => $image->store('products', 'public'),
                    'sort' => $sort,
                ]);
            }

            return $product->load(['images', 'category', 'brand']);
        });
    }
}
