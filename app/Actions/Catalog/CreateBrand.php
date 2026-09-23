<?php

namespace App\Actions\Catalog;

use App\Models\Brand;
use App\Models\Store;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateBrand
{
    public function handle(Store $store, string $name): Brand
    {
        $slug = Str::slug($name);

        if ($slug === '' || $store->brands()->where('slug', $slug)->exists()) {
            throw ValidationException::withMessages([
                'name' => 'Choose a different brand name.',
            ]);
        }

        return $store->brands()->create([
            'name' => strip_tags($name),
            'slug' => $slug,
        ]);
    }
}
