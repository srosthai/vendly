<?php

namespace App\Actions\Catalog;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PublishProduct
{
    public function handle(Product $product): Product
    {
        return DB::transaction(function () use ($product): Product {
            $store = Store::query()->whereKey($product->store_id)->lockForUpdate()->firstOrFail();
            $product = Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();

            if ($product->status === ProductStatus::Published) {
                return $product;
            }

            $subscription = $store->subscription()->lockForUpdate()->firstOrFail();
            $subscription->load('plan');

            if (! $subscription->allowsPublishing()) {
                throw ValidationException::withMessages([
                    'status' => 'This plan has expired.',
                ]);
            }

            $published = $store->products()->published()->count();

            if ($published >= $subscription->plan->product_limit) {
                throw ValidationException::withMessages([
                    'status' => 'This plan is full.',
                ]);
            }

            $product->status = ProductStatus::Published;
            $product->save();

            return $product;
        });
    }
}
