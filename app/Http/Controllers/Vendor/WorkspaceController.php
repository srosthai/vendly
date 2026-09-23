<?php

namespace App\Http\Controllers\Vendor;

use App\Actions\Dashboard\DailyCounts;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Concerns\ResolvesVendorStore;
use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\Plan;
use App\Models\PlatformSetting;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Store;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceController extends Controller
{
    use ResolvesVendorStore;

    /**
     * One dashboard route, three overviews: the admin sees the platform, a
     * vendor sees their store, and a customer sees the requests they sent.
     */
    public function home(Request $request, DailyCounts $daily): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        if ($user->is_admin) {
            return $this->adminOverview($daily);
        }

        $store = $this->optionalStore($request);

        if ($store === null) {
            return Inertia::render('dashboard', [
                'requests' => Inquiry::query()
                    ->whereBelongsTo($user, 'customer')
                    ->with(['store', 'items'])
                    ->latest()
                    ->orderByDesc('id')
                    ->limit(5)
                    ->get()
                    ->map(fn (Inquiry $inquiry): array => [
                        'id' => $inquiry->id,
                        'reference' => $inquiry->reference(),
                        'store' => $inquiry->store->name,
                        'store_url' => $inquiry->store->isSuspended() ? null : route('stores.show', $inquiry->store),
                        'lines' => $inquiry->items->count(),
                        'total' => Money::format($this->inquiryTotal($inquiry)),
                        'sent_at' => $inquiry->created_at?->toIso8601String(),
                    ]),
            ]);
        }

        $subscription = $store->subscription;
        $weekAgo = now()->subDays(6)->startOfDay();
        $recentRequests = $store->inquiries()->where('created_at', '>=', $weekAgo)->pluck('created_at');

        return Inertia::render('vendor/overview', [
            'store' => [
                'name' => $store->name,
                'web_url' => route('stores.show', $store),
                'telegram_url' => PlatformSetting::current()->miniAppLink($store->slug),
                'telegram_connected' => filled($store->telegram_chat_id),
            ],
            'stats' => [
                'published' => $store->products()->published()->count(),
                'drafts' => $store->products()->where('status', ProductStatus::Draft)->count(),
                'limit' => (int) ($subscription?->plan->product_limit ?? 0),
                'plan' => $subscription?->plan->name,
                'free' => $subscription?->plan->isFree() ?? true,
                'ends_at' => $subscription?->ends_at?->toIso8601String(),
                'can_publish' => $subscription?->allowsPublishing() ?? false,
                'requests_this_week' => $recentRequests->count(),
                'requests_trend' => $daily->handle($recentRequests->map(fn ($at): array => ['at' => $at])),
            ],
            'recentRequests' => $store->inquiries()
                ->with('items')
                ->latest()
                ->orderByDesc('id')
                ->limit(5)
                ->get()
                ->map(fn (Inquiry $inquiry): array => [
                    'id' => $inquiry->id,
                    'reference' => $inquiry->reference(),
                    'customer' => $inquiry->customer_name,
                    'contact' => $inquiry->contact,
                    'lines' => $inquiry->items->map(fn ($item): string => $item->quantity.' × '.$item->name)->all(),
                    'total' => Money::format($this->inquiryTotal($inquiry)),
                    'sent_at' => $inquiry->created_at?->toIso8601String(),
                ]),
        ]);
    }

    private function adminOverview(DailyCounts $daily): Response
    {
        $twoWeeksAgo = now()->subDays(6)->startOfDay();
        $paid = SubscriptionPayment::query()
            ->where('status', PaymentStatus::Paid)
            ->where('paid_at', '>=', now()->startOfMonth()->min($twoWeeksAgo))
            ->get(['amount_cents', 'paid_at']);

        return Inertia::render('admin/overview', [
            'stats' => [
                'vendors' => Store::query()->count(),
                'new_vendors' => Store::query()->where('created_at', '>=', $twoWeeksAgo)->count(),
                'suspended' => Store::query()->whereNotNull('suspended_at')->count(),
                'paid_plans' => Subscription::query()
                    ->where('status', SubscriptionStatus::Active)
                    ->whereHas('plan', fn ($plan) => $plan->where('price_cents', '>', 0))
                    ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()))
                    ->count(),
                'revenue_this_month' => Money::format((int) $paid->where('paid_at', '>=', now()->startOfMonth())->sum('amount_cents')),
                'revenue_trend' => $daily->handle($paid->map(fn (SubscriptionPayment $payment): array => [
                    'at' => $payment->paid_at,
                    'amount' => $payment->amount_cents,
                ])),
                'undelivered' => Inquiry::query()->whereNull('admin_notified_at')->count(),
            ],
            'recentPayments' => SubscriptionPayment::query()
                ->with(['store', 'plan'])
                ->latest()
                ->orderByDesc('id')
                ->limit(6)
                ->get()
                ->map(fn (SubscriptionPayment $payment): array => [
                    'id' => $payment->id,
                    'store' => $payment->store?->name,
                    'plan' => $payment->plan?->name,
                    'amount' => Money::format($payment->amount_cents),
                    'status' => $payment->status->value,
                    'created_at' => $payment->created_at?->toIso8601String(),
                ]),
        ]);
    }

    private function inquiryTotal(Inquiry $inquiry): int
    {
        return (int) $inquiry->items->sum(fn ($item): int => $item->price_cents * $item->quantity);
    }

    public function store(Request $request): Response
    {
        $store = $this->vendorStore($request);

        return Inertia::render('vendor/store', [
            'store' => [
                'name' => $store->name,
                'description' => $store->description ?? '',
                'slug' => $store->slug,
                'logo' => $store->logoUrl(),
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
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:1024'],
            'remove_logo' => ['sometimes', 'boolean'],
        ]);

        $store->name = strip_tags($validated['name']);
        $store->description = isset($validated['description']) ? strip_tags($validated['description']) : null;

        $oldLogo = $store->logo_path;

        if ($request->hasFile('logo')) {
            $store->logo_path = $request->file('logo')->store('logos', 'public');
        } elseif ($request->boolean('remove_logo')) {
            $store->logo_path = null;
        }

        $store->save();

        if ($oldLogo !== null && $oldLogo !== $store->logo_path) {
            Storage::disk('public')->delete($oldLogo);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Store saved.']);

        return back();
    }

    public function products(Request $request): Response
    {
        $store = $this->vendorStore($request);
        $limit = (int) ($store->subscription?->plan->product_limit ?? 0);
        $published = $store->products()->published()->count();

        $search = trim($request->string('search')->toString());

        return Inertia::render('vendor/products', [
            'usage' => ['published' => $published, 'limit' => $limit],
            'search' => $search,
            'products' => $store->products()
                ->with('coverImage')
                ->when($search !== '', fn ($query) => $query->whereLike('name', '%'.$search.'%'))
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->paginate(25)
                ->withQueryString()
                ->through(fn (Product $product): array => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price_cents' => $product->price_cents,
                    'status' => $product->status->value,
                    'stock' => $product->stock,
                    'image' => $product->coverImage?->url(),
                ]),
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
                'period' => $subscription?->plan->isFree() === false
                    ? SubscriptionPayment::query()
                        ->where('store_id', $store->id)
                        ->where('status', PaymentStatus::Paid)
                        ->latest('paid_at')
                        ->latest('id')
                        ->first()?->period->value
                    : null,
            ],
            'plans' => Plan::query()->where('is_active', true)->where('price_cents', '>', 0)->orderBy('price_cents')->orderBy('id')->get(['id', 'name', 'price_cents', 'yearly_price_cents', 'product_limit']),
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
