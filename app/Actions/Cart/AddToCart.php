<?php

namespace App\Actions\Cart;

use App\Models\Cart;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AddToCart
{
    public function __construct(private GuestCart $guestCart) {}

    public function handle(Store $store, Product $product, int $quantity, ?User $user): void
    {
        if ($store->isSuspended() || $product->store_id !== $store->id || $product->isSoldOut()) {
            throw ValidationException::withMessages([
                'product' => 'This product cannot be added.',
            ]);
        }

        if ($user === null) {
            $this->guestCart->add($store, $product, $quantity);

            return;
        }

        $cart = Cart::query()->firstOrCreate([
            'user_id' => $user->id,
            'store_id' => $store->id,
        ]);

        $item = $cart->items()->firstOrNew([
            'product_id' => $product->id,
        ]);
        $item->quantity = min(99, (int) $item->quantity + $quantity);
        $item->save();
    }
}
