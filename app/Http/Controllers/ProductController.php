<?php

namespace App\Http\Controllers;

use App\Actions\Catalog\PublishProduct;
use App\Actions\Catalog\SaveProduct;
use App\Http\Requests\SaveProductRequest;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function store(SaveProductRequest $request, SaveProduct $action): RedirectResponse
    {
        $store = $this->ownedStore($request->user());
        $product = $action->handle(
            $store,
            $request->productAttributes(),
            $this->images($request),
        );

        return redirect()->route('stores.products.show', [
            'store' => $product->store,
            'productSlug' => $product->slug,
        ]);
    }

    public function update(SaveProductRequest $request, Product $product, SaveProduct $action): RedirectResponse
    {
        $product = $action->handle(
            $product->store,
            $request->productAttributes(),
            $this->images($request),
            $product,
        );

        return redirect()->route('stores.products.show', [
            'store' => $product->store,
            'productSlug' => $product->slug,
        ]);
    }

    public function publish(Product $product, PublishProduct $action): RedirectResponse
    {
        $this->authorize('update', $product);
        $action->handle($product);

        return back();
    }

    public function show(Store $store, string $productSlug): Response
    {
        abort_if($store->isSuspended(), 404);

        $product = $store->products()
            ->published()
            ->where('slug', $productSlug)
            ->firstOrFail();

        $product->load('images');

        return Inertia::render('stores/product', [
            'store' => [
                'name' => $store->name,
                'slug' => $store->slug,
                'url' => route('stores.show', $store),
            ],
            'embedded' => request()->session()->get('mini_app') === true,
            'product' => [
                'id' => $product->id,
                'slug' => $product->slug,
                'name' => $product->name,
                'description' => $product->description,
                'price_cents' => $product->price_cents,
                'sold_out' => $product->isSoldOut(),
                'image' => $product->images->first() !== null ? Storage::disk('public')->url($product->images->first()->path) : null,
            ],
        ]);
    }

    private function ownedStore(mixed $user): Store
    {
        abort_unless($user instanceof User, 403);
        $store = $user->store;
        abort_if($store === null, 403);

        return $store;
    }

    /**
     * @return array<int, UploadedFile>
     */
    private function images(Request $request): array
    {
        /** @var mixed $images */
        $images = $request->file('images', []);

        if ($images instanceof UploadedFile) {
            return [$images];
        }

        if (! is_array($images)) {
            return [];
        }

        return array_values(array_filter(
            $images,
            fn (mixed $image): bool => $image instanceof UploadedFile,
        ));
    }
}
