<?php

namespace App\Actions\Cart;

use App\Models\Cart;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;

class CartSummary
{
    /**
     * @return array{count: int, total_cents: int, items: list<array{id: int, name: string, quantity: int, price_cents: int}>}
     */
    public function for(Request $request, Store $store): array
    {
        $user = $request->user();
        $lines = $user instanceof User
            ? $this->accountLines($user, $store)
            : $this->guestLines($request, $store);

        return [
            'count' => array_sum(array_column($lines, 'quantity')),
            'total_cents' => array_sum(array_map(fn (array $line): int => $line['price_cents'] * $line['quantity'], $lines)),
            'items' => $lines,
        ];
    }

    /**
     * @return list<array{id: int, name: string, quantity: int, price_cents: int}>
     */
    private function accountLines(User $user, Store $store): array
    {
        $cart = Cart::query()
            ->whereBelongsTo($user)
            ->whereBelongsTo($store)
            ->with('items.product')
            ->first();

        if ($cart === null) {
            return [];
        }

        $lines = [];

        foreach ($cart->items as $item) {
            if ($item->product === null || ! $item->product->isPublished()) {
                continue;
            }

            $lines[] = [
                'id' => $item->product->id,
                'name' => $item->product->name,
                'quantity' => $item->quantity,
                'price_cents' => $item->product->price_cents,
            ];
        }

        return $lines;
    }

    /**
     * @return list<array{id: int, name: string, quantity: int, price_cents: int}>
     */
    private function guestLines(Request $request, Store $store): array
    {
        $items = $request->session()->get('guest-carts', [])[(string) $store->id] ?? [];

        if (! is_array($items)) {
            return [];
        }

        $products = Product::query()
            ->where('store_id', $store->id)
            ->published()
            ->whereIn('id', array_map('intval', array_keys($items)))
            ->get()
            ->keyBy('id');

        $lines = [];

        foreach ($items as $productId => $quantity) {
            $product = $products->get((int) $productId);

            if (! $product instanceof Product) {
                continue;
            }

            $lines[] = [
                'id' => $product->id,
                'name' => $product->name,
                'quantity' => (int) $quantity,
                'price_cents' => $product->price_cents,
            ];
        }

        return $lines;
    }
}
