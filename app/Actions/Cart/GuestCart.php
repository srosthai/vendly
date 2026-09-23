<?php

namespace App\Actions\Cart;

use App\Models\Product;
use App\Models\Store;

class GuestCart
{
    public function add(Store $store, Product $product, int $quantity): void
    {
        $carts = session()->get('guest-carts', []);
        $storeKey = (string) $store->id;
        $productKey = (string) $product->id;
        $current = (int) ($carts[$storeKey][$productKey] ?? 0);
        $carts[$storeKey][$productKey] = min(99, $current + $quantity);
        session()->put('guest-carts', $carts);
    }

    /**
     * @return array<string, array<string, int>>
     */
    public function pullAll(): array
    {
        /** @var array<string, array<string, int>> $carts */
        $carts = session()->pull('guest-carts', []);

        return $carts;
    }
}
