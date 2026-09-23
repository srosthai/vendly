<?php

namespace App\Actions\Catalog;

use App\Models\Brand;
use App\Models\Store;
use App\Support\Slug;
use Illuminate\Validation\ValidationException;

class SaveBrand
{
    /**
     * Create or rename a brand. A rename keeps the slug, so links that filter
     * by it keep working.
     */
    public function handle(Store $store, string $name, ?Brand $brand = null): Brand
    {
        $name = trim(strip_tags($name));

        $taken = $store->brands()
            ->when($brand, fn ($query) => $query->whereKeyNot($brand->id))
            ->whereRaw('lower(name) = ?', [mb_strtolower($name)])
            ->exists();

        if ($name === '' || $taken) {
            throw ValidationException::withMessages([
                'name' => 'Choose a different brand name.',
            ]);
        }

        if ($brand !== null) {
            $brand->update(['name' => $name]);

            return $brand;
        }

        return $store->brands()->create([
            'name' => $name,
            'slug' => Slug::unique($name, $store->brands()->getQuery(), 'brand'),
        ]);
    }
}
