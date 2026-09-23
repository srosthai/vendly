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
     * @return array{count: int, total_cents: int, items: list<array{id: int, name: string, quantity: int, price_cents: int, image: string|null}>}
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
     * @return list<array{id: int, name: string, quantity: int, price_cents: int, image: string|null}>
     */
    private function accountLines(User $user, Store $store): array
    {
        $cart = Cart::query()
            ->whereBelongsTo($user)
            ->whereBelongsTo($store)
            ->with('items.product.coverImage')
            ->first();

        if ($cart === null) {
            return [];
        }

        $lines = [];

        foreach ($cart->items as $item) {
            if ($item->product === null || ! $item->product->isPublished()) {
                continue;
            }

            $lines[] = $this->line($item->product, $item->quantity);
        }

        return $lines;
    }

    /**
     * @return list<array{id: int, name: string, quantity: int, price_cents: int, image: string|null}>
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
            ->with('coverImage')
            ->get()
            ->keyBy('id');

        $lines = [];

        foreach ($items as $productId => $quantity) {
            $product = $products->get((int) $productId);

            if (! $product instanceof Product) {
                continue;
            }

            $lines[] = $this->line($product, (int) $quantity);
        }

        return $lines;
    }

    /**
     * @return array{id: int, name: string, quantity: int, price_cents: int, image: string|null}
     */
    private function line(Product $product, int $quantity): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'quantity' => $quantity,
            'price_cents' => $product->price_cents,
            'image' => $product->coverImage?->url(),
        ];
    }
}
