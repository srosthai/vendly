<?php

namespace App\Actions\Inquiries;

use App\Models\Cart;
use App\Models\Inquiry;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\Telegram\TelegramNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SendInquiry
{
    public function __construct(private TelegramNotifier $telegram) {}

    public function forProduct(Store $store, Product $product, User $customer): Inquiry
    {
        if ($product->store_id !== $store->id || $product->isSoldOut()) {
            throw ValidationException::withMessages([
                'product' => 'This product cannot be requested.',
            ]);
        }

        return $this->send($store, $customer, [[
            'product_id' => $product->id,
            'name' => $product->name,
            'price_cents' => $product->price_cents,
            'quantity' => 1,
        ]]);
    }

    public function forCart(Store $store, User $customer): Inquiry
    {
        $cart = Cart::query()
            ->whereBelongsTo($customer)
            ->whereBelongsTo($store)
            ->with('items.product')
            ->first();

        if ($cart === null || $cart->items->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'Add a product before sending.',
            ]);
        }

        $lines = [];

        foreach ($cart->items as $item) {
            $product = $item->product;

            if ($product === null || $product->isSoldOut()) {
                continue;
            }

            $lines[] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'price_cents' => $product->price_cents,
                'quantity' => $item->quantity,
            ];
        }

        if ($lines === []) {
            throw ValidationException::withMessages([
                'cart' => 'Add a product before sending.',
            ]);
        }

        $inquiry = $this->send($store, $customer, $lines);
        $cart->items()->delete();

        return $inquiry;
    }

    /**
     * @param  list<array{product_id: int, name: string, price_cents: int, quantity: int}>  $lines
     */
    private function send(Store $store, User $customer, array $lines): Inquiry
    {
        $contact = $customer->email ?? ($customer->telegram_username !== null ? '@'.$customer->telegram_username : null);

        $inquiry = DB::transaction(function () use ($store, $customer, $lines, $contact): Inquiry {
            $inquiry = Inquiry::query()->create([
                'public_id' => (string) Str::ulid(),
                'store_id' => $store->id,
                'user_id' => $customer->id,
                'customer_name' => $customer->name,
                'contact' => $contact,
            ]);

            foreach ($lines as $line) {
                $inquiry->items()->create($line);
            }

            return $inquiry->load(['items.product', 'store']);
        });

        $this->telegram->inquiry($inquiry);

        return $inquiry;
    }
}
