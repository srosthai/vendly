<?php

namespace App\Http\Controllers;

use App\Actions\Cart\CartSummary;
use App\Actions\Stores\CreateStore;
use App\Http\Requests\CreateStoreRequest;
use App\Models\PlatformSetting;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StoreController extends Controller
{
    /**
     * The orders a customer can sort a storefront by.
     *
     * @var list<string>
     */
    private const Sorts = ['newest', 'price-low', 'price-high'];

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
        $brand = $request->string('brand')->toString();
        $sort = in_array($request->query('sort'), self::Sorts, true) ? (string) $request->query('sort') : 'newest';
        $settings = PlatformSetting::current();

        $products = $store->products()
            ->published()
            ->with('coverImage')
            ->when($category !== '', fn ($query) => $query->whereHas('category', fn ($categories) => $categories->where('slug', $category)))
            ->when($brand !== '', fn ($query) => $query->whereHas('brand', fn ($brands) => $brands->where('slug', $brand)));

        match ($sort) {
            'price-low' => $products->orderBy('price_cents')->orderBy('id'),
            'price-high' => $products->orderByDesc('price_cents')->orderByDesc('id'),
            default => $products->orderByDesc('created_at')->orderByDesc('id'),
        };

        return Inertia::render('stores/show', [
            'store' => [
                'name' => $store->name,
                'slug' => $store->slug,
                'description' => $store->description,
                'logo' => $store->logoUrl(),
                'telegram_url' => $settings->miniAppLink($store->slug),
                'products_count' => $store->products()->published()->count(),
                'joined_at' => $store->created_at?->toIso8601String(),
            ],
            'categories' => $store->categories()
                ->whereHas('products', fn ($products) => $products->published())
                ->orderBy('sort')->orderBy('id')->get(['name', 'slug']),
            'brands' => $store->brands()
                ->whereHas('products', fn ($products) => $products->published())
                ->orderBy('name')->get(['name', 'slug']),
            'filters' => ['category' => $category, 'brand' => $brand, 'sort' => $sort],
            'embedded' => $request->session()->get('mini_app') === true,
            'authenticated' => $request->user() !== null,
            'status' => $request->session()->get('status'),
            'cart' => $cart->for($request, $store),
            'products' => Inertia::scroll(fn () => $products->paginate(24)->withQueryString()->through(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'price_cents' => $product->price_cents,
                'sold_out' => $product->isSoldOut(),
                'image' => $product->coverImage?->url(),
                'url' => route('stores.products.show', ['store' => $store, 'productSlug' => $product->slug]),
                'telegram_url' => $settings->miniAppProductLink($product->id),
            ])),
        ]);
    }
}
