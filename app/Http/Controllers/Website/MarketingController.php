<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\PlatformSetting;
use App\Models\Product;
use App\Models\Store;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The public website. Each topic has its own page, title, and description
 * so search engines can find it on its own.
 */
class MarketingController extends Controller
{
    public function home(): Response
    {
        return $this->page('marketing/home', [
            'title' => 'A shop on the web and in Telegram',
            'description' => 'Give your shop one link that opens in any browser and inside Telegram. Customers send what they want to buy straight to your chat.',
        ], [
            'recentStores' => $this->recentStores(),
            'freePlan' => Plan::query()->where('is_default', true)->first(['name', 'product_limit']),
            'testimonials' => Testimonial::query()->published()->limit(3)->get(['id', 'name', 'role', 'quote']),
        ]);
    }

    public function features(): Response
    {
        return $this->page('marketing/features', [
            'title' => 'Features',
            'description' => 'Everything a small shop needs to sell from a link: a web store and Telegram mini app, a catalog with photos, buy requests in Telegram, and simple plans.',
        ]);
    }

    public function howItWorks(): Response
    {
        return $this->page('marketing/how-it-works', [
            'title' => 'How it works',
            'description' => 'Open a store, share one link, and read buy requests in Telegram. See each step for sellers and customers, and answers to common questions.',
        ]);
    }

    public function pricing(): Response
    {
        return $this->page('marketing/pricing', [
            'title' => 'Pricing',
            'description' => 'Start free and pay only for more published products. Plans are paid monthly by Cambodia QR, and Vendly takes nothing from your sales.',
        ], [
            'plans' => Plan::query()
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('price_cents')
                ->orderBy('id')
                ->get(['id', 'name', 'price_cents', 'yearly_price_cents', 'product_limit', 'is_default']),
        ]);
    }

    /**
     * Every store with something to sell, newest first, searchable by name.
     */
    public function stores(Request $request): Response
    {
        $search = trim($request->string('search')->toString());

        $stores = Store::query()
            ->whereNull('suspended_at')
            ->whereHas('products', fn ($products) => $products->published())
            ->when($search !== '', fn ($query) => $query->whereLike('name', '%'.$search.'%'))
            ->withCount(['products as published_products_count' => fn ($products) => $products->published()])
            ->with(['products' => fn ($products) => $products->published()->with('coverImage')->latest()->orderByDesc('id')->limit(3)])
            ->latest()
            ->orderByDesc('id');

        return $this->page('marketing/stores', [
            'title' => 'Stores',
            'description' => 'Browse the shops selling on Vendly. Open a store to see its products, then buy through Telegram.',
        ], [
            'search' => $search,
            'stores' => Inertia::scroll(fn () => $stores->paginate(18)->withQueryString()->through(fn (Store $store): array => [
                'name' => $store->name,
                'description' => $store->description,
                'logo' => $store->logoUrl(),
                'accent' => $store->accent,
                'url' => route('stores.show', $store),
                'products_count' => $store->published_products_count ?? 0,
                'joined_at' => $store->created_at?->toIso8601String(),
                'previews' => $store->products
                    ->map(fn (Product $product): ?string => $product->coverImage?->url())
                    ->filter()
                    ->values()
                    ->all(),
            ])),
        ]);
    }

    public function testimonials(): Response
    {
        $testimonials = Testimonial::query()->published()->get(['id', 'name', 'role', 'quote']);
        abort_if($testimonials->isEmpty(), 404);

        return $this->page('marketing/testimonials', [
            'title' => 'Testimonials',
            'description' => 'What sellers and customers say about selling and shopping with Vendly.',
        ], ['testimonials' => $testimonials]);
    }

    /**
     * @param  array{title: string, description: string}  $meta
     * @param  array<string, mixed>  $props
     */
    private function page(string $component, array $meta, array $props = []): Response
    {
        return Inertia::render($component, [
            ...$props,
            'meta' => [...$meta, 'url' => url()->current()],
            'showTestimonials' => Testimonial::query()->whereNotNull('published_at')->exists(),
            'site' => PlatformSetting::current()->footer(),
        ]);
    }

    /**
     * The newest live stores for the "Recently joined" rail.
     *
     * @return Collection<int, array{name: string, url: string, joined_at: string|null}>
     */
    private function recentStores(): Collection
    {
        return Store::query()
            ->whereNull('suspended_at')
            ->latest()
            ->orderByDesc('id')
            ->limit(12)
            ->get(['id', 'name', 'slug', 'created_at'])
            ->map(fn (Store $store): array => [
                'name' => $store->name,
                'url' => route('stores.show', $store),
                'joined_at' => $store->created_at?->toIso8601String(),
            ]);
    }
}
