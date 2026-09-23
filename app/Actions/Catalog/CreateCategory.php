<?php

namespace App\Actions\Catalog;

use App\Models\Category;
use App\Models\Store;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateCategory
{
    public function handle(Store $store, string $name): Category
    {
        $slug = Str::slug($name);

        if ($slug === '' || $store->categories()->where('slug', $slug)->exists()) {
            throw ValidationException::withMessages([
                'name' => 'Choose a different category name.',
            ]);
        }

        $sort = (int) $store->categories()->max('sort') + 1;

        return $store->categories()->create([
            'name' => strip_tags($name),
            'slug' => $slug,
            'sort' => $sort,
        ]);
    }
}
