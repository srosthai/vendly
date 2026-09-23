<?php

namespace App\Http\Controllers;

use App\Actions\Cart\AddToCart;
use App\Actions\Cart\UpdateCartLine;
use App\Http\Requests\AddCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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

    public function update(UpdateCartItemRequest $request, Store $store, Product $product, UpdateCartLine $action): RedirectResponse
    {
        abort_unless($product->store_id === $store->id, 404);

        $user = $request->user();
        $action->set($store, $product, $request->integer('quantity'), $user instanceof User ? $user : null);

        return back();
    }

    public function destroy(Request $request, Store $store, Product $product, UpdateCartLine $action): RedirectResponse
    {
        abort_unless($product->store_id === $store->id, 404);

        $user = $request->user();
        $action->remove($store, $product, $user instanceof User ? $user : null);

        return back();
    }
}
