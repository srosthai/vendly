<?php

namespace App\Http\Controllers;

use App\Actions\Cart\CartSummary;
use App\Actions\Stores\CreateStore;
use App\Http\Requests\CreateStoreRequest;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StoreController extends Controller
{
    public function store(CreateStoreRequest $request, CreateStore $action): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $store = $action->handle(
            $user,
            $request->string('name')->toString(),
            $request->string('description')->toString() ?: null,
            $request->string('slug')->toString() ?: null,
        );

        return redirect()->route('stores.show', $store);
    }

    public function show(Request $request, Store $store, CartSummary $cart): Response
    {
        abort_if($store->isSuspended(), 404);

        $category = $request->string('category')->toString();
        $products = $store->products()
            ->published()
            ->with('coverImage')
            ->when($category !== '', fn ($query) => $query->whereHas('category', fn ($categories) => $categories->where('slug', $category)))
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        return Inertia::render('stores/show', [
            'store' => [
                'name' => $store->name,
                'slug' => $store->slug,
                'description' => $store->description,
            ],
            'categories' => $store->categories()->orderBy('sort')->orderBy('id')->get(['name', 'slug']),
            'activeCategory' => $category,
            'embedded' => $request->session()->get('mini_app') === true,
            'authenticated' => $request->user() !== null,
            'cart' => $cart->for($request, $store),
            'products' => Inertia::scroll(fn () => $products->paginate(24)->withQueryString()->through(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'price_cents' => $product->price_cents,
                'sold_out' => $product->isSoldOut(),
                'image' => $product->coverImage?->url(),
                'url' => route('stores.products.show', ['store' => $store, 'productSlug' => $product->slug]),
            ])),
        ]);
    }
}
