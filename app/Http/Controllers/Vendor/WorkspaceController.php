<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\PlatformSetting;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceController extends Controller
{
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
        $store = $this->ownedStore($request);
        $settings = PlatformSetting::current();
        $username = $settings->botUsername();
        $short = $settings->mini_app_short_name ?: (string) config('services.telegram.mini_app_short_name');

        return Inertia::render('vendor/store', [
            'store' => [
                'name' => $store->name,
                'description' => $store->description ?? '',
                'web_url' => route('stores.show', $store),
                'telegram_url' => $username !== '' && $short !== ''
                    ? 'https://t.me/'.$username.'/'.$short.'?startapp='.$store->slug
                    : null,
            ],
        ]);
    }

    public function updateStore(Request $request): RedirectResponse
    {
        $store = $this->ownedStore($request);
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
        $store = $this->ownedStore($request);
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

    public function createProduct(): Response
    {
        return Inertia::render('vendor/product-form');
    }

    public function categories(Request $request): Response
    {
        $store = $this->ownedStore($request);

        return Inertia::render('vendor/categories', [
            'categories' => $store->categories()->orderBy('sort')->orderBy('id')->get(['id', 'name']),
        ]);
    }

    public function brands(Request $request): Response
    {
        $store = $this->ownedStore($request);

        return Inertia::render('vendor/brands', [
            'brands' => $store->brands()->orderBy('name')->orderBy('id')->get(['id', 'name']),
        ]);
    }

    public function plan(Request $request): Response
    {
        $store = $this->ownedStore($request);
        $subscription = $store->subscription()->with('plan')->first();

        return Inertia::render('vendor/plan', [
            'usage' => [
                'published' => $store->products()->published()->count(),
                'limit' => (int) ($subscription?->plan->product_limit ?? 0),
                'plan' => $subscription?->plan->name,
            ],
            'plans' => Plan::query()->where('is_active', true)->where('price_cents', '>', 0)->orderBy('price_cents')->get(['id', 'name', 'price_cents', 'product_limit']),
            'payment' => $request->session()->get('payment'),
        ]);
    }

    public function telegram(Request $request): Response
    {
        $store = $this->ownedStore($request);

        return Inertia::render('vendor/telegram', [
            'connected' => filled($store->telegram_chat_id),
            'link' => $request->session()->get('telegram_link'),
        ]);
    }

    private function ownedStore(Request $request): Store
    {
        $store = $this->optionalStore($request);
        abort_if($store === null, 403);

        return $store;
    }

    private function optionalStore(Request $request): ?Store
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return $user->store()->with('subscription.plan')->first();
    }
}
