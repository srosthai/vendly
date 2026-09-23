<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Concerns\ResolvesVendorStore;
use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\PlatformSetting;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceController extends Controller
{
    use ResolvesVendorStore;

    public function home(Request $request): Response
    {
        $store = $this->optionalStore($request);

        return Inertia::render('dashboard', [
            'store' => $store === null ? null : [
                'name' => $store->name,
                'published' => $store->products()->published()->count(),
                'limit' => $store->subscription?->plan->product_limit,
            ],
        ]);
    }

    public function store(Request $request): Response
    {
        $store = $this->vendorStore($request);

        return Inertia::render('vendor/store', [
            'store' => [
                'name' => $store->name,
                'description' => $store->description ?? '',
                'web_url' => route('stores.show', $store),
                'telegram_url' => PlatformSetting::current()->miniAppLink($store->slug),
            ],
        ]);
    }

    public function updateStore(Request $request): RedirectResponse
    {
        $store = $this->vendorStore($request);
        $this->authorize('update', $store);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $store->name = strip_tags($validated['name']);
        $store->description = isset($validated['description']) ? strip_tags($validated['description']) : null;
        $store->save();

        return back();
    }

    public function products(Request $request): Response
    {
        $store = $this->vendorStore($request);
        $limit = (int) ($store->subscription?->plan->product_limit ?? 0);
        $published = $store->products()->published()->count();

        return Inertia::render('vendor/products', [
            'usage' => ['published' => $published, 'limit' => $limit],
            'products' => $store->products()->orderByDesc('created_at')->orderByDesc('id')->get()->map(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'price_cents' => $product->price_cents,
                'status' => $product->status->value,
            ])->values(),
        ]);
    }

    public function createProduct(Request $request): Response
    {
        return Inertia::render('vendor/product-form', [
            'product' => null,
            ...$this->catalogOptions($this->vendorStore($request)),
        ]);
    }

    public function editProduct(Request $request, Product $product): Response
    {
        $this->authorize('update', $product);
        $product->load('images');

        return Inertia::render('vendor/product-form', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description ?? '',
                'price' => number_format($product->price_cents / 100, 2, '.', ''),
                'stock' => $product->stock,
                'category_id' => $product->category_id,
                'brand_id' => $product->brand_id,
                'status' => $product->status->value,
                'url' => $product->isPublished()
                    ? route('stores.products.show', ['store' => $product->store, 'productSlug' => $product->slug])
                    : null,
                'images' => $product->images->sortBy('sort')->values()->map(fn (ProductImage $image): array => [
                    'id' => $image->id,
                    'url' => $image->url(),
                ])->all(),
            ],
            ...$this->catalogOptions($this->vendorStore($request)),
        ]);
    }

    /**
     * @return array{categories: mixed, brands: mixed}
     */
    private function catalogOptions(Store $store): array
    {
        return [
            'categories' => $store->categories()->orderBy('sort')->orderBy('id')->get(['id', 'name']),
            'brands' => $store->brands()->orderBy('name')->orderBy('id')->get(['id', 'name']),
        ];
    }

    public function categories(Request $request): Response
    {
        $store = $this->vendorStore($request);

        return Inertia::render('vendor/categories', [
            'categories' => $store->categories()->orderBy('sort')->orderBy('id')->get(['id', 'name']),
        ]);
    }

    public function brands(Request $request): Response
    {
        $store = $this->vendorStore($request);

        return Inertia::render('vendor/brands', [
            'brands' => $store->brands()->orderBy('name')->orderBy('id')->get(['id', 'name']),
        ]);
    }

    public function plan(Request $request): Response
    {
        $store = $this->vendorStore($request);
        $subscription = $store->subscription()->with('plan')->first();

        return Inertia::render('vendor/plan', [
            'usage' => [
                'published' => $store->products()->published()->count(),
                'limit' => (int) ($subscription?->plan->product_limit ?? 0),
                'plan' => $subscription?->plan->name,
                'free' => $subscription?->plan->isFree() ?? true,
                'status' => $subscription?->status->value,
                'ends_at' => $subscription?->ends_at?->toIso8601String(),
                'can_publish' => $subscription?->allowsPublishing() ?? false,
            ],
            'plans' => Plan::query()->where('is_active', true)->where('price_cents', '>', 0)->orderBy('price_cents')->orderBy('id')->get(['id', 'name', 'price_cents', 'product_limit']),
            'payment' => $request->session()->get('payment'),
        ]);
    }

    public function telegram(Request $request): Response
    {
        $store = $this->vendorStore($request);

        return Inertia::render('vendor/telegram', [
            'connected' => filled($store->telegram_chat_id),
            'link' => $request->session()->get('telegram_link'),
        ]);
    }

    private function optionalStore(Request $request): ?Store
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return $user->store()->with('subscription.plan')->first();
    }
}
