<?php

namespace App\Http\Controllers\Vendor;

use App\Actions\Dashboard\DailyCounts;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Concerns\ResolvesVendorStore;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateStoreRequest;
use App\Http\Requests\Vendor\NamedListRequest;
use App\Http\Requests\Vendor\ProductListRequest;
use App\Models\Brand;
use App\Models\Category;
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
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
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
                'plan_id' => $subscription?->plan_id,
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
                'accent' => $store->accent,
                'phone' => $store->phone ?? '',
                'address' => $store->address ?? '',
                'hours' => $store->hours ?? '',
                'social_links' => collect(Store::SocialNetworks)
                    ->mapWithKeys(fn (string $network): array => [$network => $store->social_links[$network] ?? ''])
                    ->all(),
            ],
            'accents' => Store::Accents,
        ]);
    }

    public function updateStore(UpdateStoreRequest $request): RedirectResponse
    {
        $store = $this->vendorStore($request);
        $this->authorize('update', $store);

        $store->fill($request->profile());

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

    public function products(ProductListRequest $request): Response
    {
        $store = $this->vendorStore($request);
        $limit = (int) ($store->subscription?->plan->product_limit ?? 0);
        $published = $store->products()->published()->count();
        $filters = $request->filters();

        $products = $store->products()
            ->with(['coverImage', 'category', 'brand'])
            ->when($filters['search'] !== '', fn ($query) => $query->whereLike('name', $request->searchPattern()))
            ->when($filters['status'] === 'published', fn ($query) => $query->published())
            ->when($filters['status'] === 'draft', fn ($query) => $query->where('status', ProductStatus::Draft))
            ->when($filters['status'] === 'sold_out', fn ($query) => $query->where('stock', 0))
            ->when($filters['category'] !== 'all', fn ($query) => $query->where('category_id', (int) $filters['category']))
            ->when($filters['brand'] !== 'all', fn ($query) => $query->where('brand_id', (int) $filters['brand']));

        match ($filters['sort']) {
            'name' => $products->orderBy('name')->orderBy('id'),
            'price_low' => $products->orderBy('price_cents')->orderBy('id'),
            'price_high' => $products->orderByDesc('price_cents')->orderByDesc('id'),
            'stock' => $products->orderByRaw('stock is null')->orderBy('stock')->orderBy('id'),
            default => $products->orderByDesc('created_at')->orderByDesc('id'),
        };

        return Inertia::render('vendor/products', [
            'usage' => ['published' => $published, 'limit' => $limit],
            'filters' => $filters,
            ...$this->catalogOptions($store),
            'products' => $products
                ->paginate(ProductListRequest::PerPage)
                ->withQueryString()
                ->through(fn (Product $product): array => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price_cents' => $product->price_cents,
                    'status' => $product->status->value,
                    'stock' => $product->stock,
                    'image' => $product->coverImage?->url(),
                    'category' => $product->category?->name,
                    'brand' => $product->brand?->name,
                ]),
            'creating' => $request->boolean('create'),
            'editing' => $this->editableProduct($store, $request->query('edit')),
        ]);
    }

    /**
     * The old create page now opens the products list with the sheet open.
     */
    public function createProduct(): RedirectResponse
    {
        return redirect()->route('vendor.products', ['create' => 1]);
    }

    /**
     * The old edit page now opens the products list with this product's
     * sheet open.
     */
    public function editProduct(Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        return redirect()->route('vendor.products', ['edit' => $product->id]);
    }

    /**
     * Everything the product sheet needs to edit one of the store's
     * products, or null when the id is missing or not the store's.
     *
     * @return array<string, mixed>|null
     */
    private function editableProduct(Store $store, mixed $id): ?array
    {
        if (! is_string($id) || ! ctype_digit($id)) {
            return null;
        }

        $product = $store->products()->with('images')->find((int) $id);

        if (! $product instanceof Product) {
            return null;
        }

        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description ?? '',
            'price' => number_format($product->price_cents / 100, 2, '.', ''),
            'stock' => $product->stock,
            'category_id' => $product->category_id,
            'brand_id' => $product->brand_id,
            'status' => $product->status->value,
            'url' => $product->isPublished()
                ? route('stores.products.show', ['store' => $store, 'productSlug' => $product->slug])
                : null,
            'images' => $product->images->sortBy('sort')->values()->map(fn (ProductImage $image): array => [
                'id' => $image->id,
                'url' => $image->url(),
            ])->all(),
        ];
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

    public function categories(NamedListRequest $request): Response
    {
        $store = $this->vendorStore($request);
        $filters = $request->filters();

        return Inertia::render('vendor/categories', [
            'filters' => $filters,
            'categories' => $this->namedList($store->categories()->getQuery(), $request, fn ($query) => $query->orderBy('sort')->orderBy('id')),
        ]);
    }

    public function brands(NamedListRequest $request): Response
    {
        $store = $this->vendorStore($request);
        $filters = $request->filters();

        return Inertia::render('vendor/brands', [
            'filters' => $filters,
            'brands' => $this->namedList($store->brands()->getQuery(), $request, fn ($query) => $query->orderBy('name')->orderBy('id')),
        ]);
    }

    /**
     * A searchable, sortable, paged list of categories or brands with how
     * many products use each.
     *
     * @param  Builder<Category>|Builder<Brand>  $query
     * @param  Closure(Builder<Category>|Builder<Brand>): mixed  $defaultOrder
     * @return LengthAwarePaginator<int, array{id: int, name: string, products_count: int}>
     */
    private function namedList(Builder $query, NamedListRequest $request, Closure $defaultOrder): LengthAwarePaginator
    {
        $filters = $request->filters();

        $query->withCount('products')
            ->when($filters['search'] !== '', fn ($query) => $query->whereLike('name', $request->searchPattern()));

        match ($filters['sort']) {
            'name' => $query->orderBy('name')->orderBy('id'),
            'products' => $query->orderByDesc('products_count')->orderBy('name'),
            'newest' => $query->orderByDesc('created_at')->orderByDesc('id'),
            default => $defaultOrder($query),
        };

        return $query->paginate(NamedListRequest::PerPage)->withQueryString()->through(fn (Category|Brand $record): array => [
            'id' => $record->id,
            'name' => $record->name,
            'products_count' => (int) $record->getAttribute('products_count'),
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
                'plan_id' => $subscription?->plan_id,
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

        $botUsername = PlatformSetting::current()->botUsername();

        return Inertia::render('vendor/telegram', [
            'connected' => filled($store->telegram_chat_id),
            'chat' => filled($store->telegram_chat_id) ? [
                'name' => $store->telegram_chat_name,
                'connected_at' => $store->telegram_connected_at?->toIso8601String(),
            ] : null,
            'bot' => $botUsername === '' ? null : '@'.$botUsername,
            'link' => $request->session()->get('telegram_link'),
            'testResult' => $request->session()->get('telegram_test'),
        ]);
    }

    private function optionalStore(Request $request): ?Store
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return $user->store()->with('subscription.plan')->first();
    }
}
