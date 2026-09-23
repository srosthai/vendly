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

    /**
     * Move the guest's session carts onto the account. Only published, in-stock
     * products of stores that are not suspended come across.
     */
    public function handle(Login $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        $guestCarts = $this->guestCart->pullAll();

        if ($guestCarts === []) {
            return;
        }

        $stores = Store::query()
            ->whereIn('id', array_map('intval', array_keys($guestCarts)))
            ->whereNull('suspended_at')
            ->get()
            ->keyBy('id');

        $productIds = array_merge(...array_map(
            fn (array $items): array => array_map('intval', array_keys($items)),
            array_values($guestCarts),
        ));

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->whereIn('store_id', $stores->keys())
            ->published()
            ->get()
            ->keyBy('id');

        foreach ($guestCarts as $storeId => $items) {
            $store = $stores->get((int) $storeId);

            if (! $store instanceof Store) {
                continue;
            }

            $cart = Cart::query()->firstOrCreate([
                'user_id' => $user->id,
                'store_id' => $store->id,
            ]);

            foreach ($items as $productId => $quantity) {
                $product = $products->get((int) $productId);

                if (! $product instanceof Product || $product->store_id !== $store->id || $product->isSoldOut()) {
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
