<?php

namespace App\Http\Controllers;

use App\Actions\Cart\AddToCart;
use App\Http\Requests\AddCartItemRequest;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class CartController extends Controller
{
    public function store(AddCartItemRequest $request, Store $store, Product $product, AddToCart $action): RedirectResponse
    {
        abort_unless($product->store_id === $store->id, 404);

        $user = $request->user();

        $action->handle(
            $store,
            $product,
            $request->integer('quantity', 1),
            $user instanceof User ? $user : null,
        );

        return back();
    }
}
