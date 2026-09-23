<?php

namespace App\Listeners;

use App\Actions\Cart\GuestCart;
use App\Models\Cart;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Auth\Events\Login;

class MergeSessionCart
{
    public function __construct(private GuestCart $guestCart) {}

    public function handle(Login $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        foreach ($this->guestCart->pullAll() as $storeId => $items) {
            $store = Store::query()->find((int) $storeId);

            if ($store === null || $store->isSuspended()) {
                continue;
            }

            $cart = Cart::query()->firstOrCreate([
                'user_id' => $user->id,
                'store_id' => $store->id,
            ]);

            foreach ($items as $productId => $quantity) {
                $product = Product::query()
                    ->whereKey((int) $productId)
                    ->where('store_id', $store->id)
                    ->first();

                if ($product === null || $product->isSoldOut()) {
                    continue;
                }

                $item = $cart->items()->firstOrNew([
                    'product_id' => $product->id,
                ]);
                $item->quantity = min(99, (int) $item->quantity + (int) $quantity);
                $item->save();
            }
        }
    }
}
