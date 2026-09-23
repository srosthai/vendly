<?php

namespace App\Http\Controllers;

use App\Actions\Stores\CreateStore;
use App\Http\Requests\CreateStoreRequest;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
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
        );

        return redirect()->route('stores.show', $store);
    }

    public function show(Store $store): Response
    {
        abort_if($store->isSuspended(), 404);

        $products = $store->products()
            ->published()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        return Inertia::render('stores/show', [
            'store' => [
                'name' => $store->name,
                'description' => $store->description,
            ],
            'products' => $products->map(fn ($product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'price_cents' => $product->price_cents,
                'url' => route('stores.products.show', ['store' => $store, 'productSlug' => $product->slug]),
            ])->values(),
        ]);
    }
}
