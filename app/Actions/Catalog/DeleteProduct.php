<?php

namespace App\Actions\Catalog;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class DeleteProduct
{
    /**
     * Images are deleted one by one so each file leaves storage with its row.
     */
    public function handle(Product $product): void
    {
        DB::transaction(function () use ($product): void {
            $product->images()->get()->each->delete();
            $product->delete();
        });
    }
}
