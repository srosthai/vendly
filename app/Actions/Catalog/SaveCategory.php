<?php

namespace App\Actions\Catalog;

use App\Models\Category;
use App\Models\Store;
use App\Support\Slug;
use Illuminate\Validation\ValidationException;

class SaveCategory
{
    /**
     * Create or rename a category. A rename keeps the slug, so links that filter
     * by it keep working.
     */
    public function handle(Store $store, string $name, ?Category $category = null): Category
    {
        $name = trim(strip_tags($name));

        $taken = $store->categories()
            ->when($category, fn ($query) => $query->whereKeyNot($category->id))
            ->whereRaw('lower(name) = ?', [mb_strtolower($name)])
            ->exists();

        if ($name === '' || $taken) {
            throw ValidationException::withMessages([
                'name' => 'Choose a different category name.',
            ]);
        }

        if ($category !== null) {
            $category->update(['name' => $name]);

            return $category;
        }

        return $store->categories()->create([
            'name' => $name,
            'slug' => Slug::unique($name, $store->categories()->getQuery(), 'category'),
            'sort' => (int) $store->categories()->max('sort') + 1,
        ]);
    }
}
