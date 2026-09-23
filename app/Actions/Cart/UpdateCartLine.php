<?php

namespace App\Actions\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;

class UpdateCartLine
{
    public function __construct(private GuestCart $guestCart) {}

    /**
     * Change the quantity of a line already in this store's cart. A product
     * that is not in the cart is left alone.
     */
    public function set(Store $store, Product $product, int $quantity, ?User $user): void
    {
        if ($user === null) {
            $this->guestCart->set($store, $product, $quantity);

            return;
        }

        $this->line($store, $product, $user)?->update(['quantity' => $quantity]);
    }

    public function remove(Store $store, Product $product, ?User $user): void
    {
        if ($user === null) {
            $this->guestCart->remove($store, $product);

            return;
        }

        $this->line($store, $product, $user)?->delete();
    }

    private function line(Store $store, Product $product, User $user): ?CartItem
    {
        $cart = Cart::query()->whereBelongsTo($user)->whereBelongsTo($store)->first();

        return $cart?->items()->where('product_id', $product->id)->first();
    }
}
