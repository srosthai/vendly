<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Inquiry;
use App\Models\PlatformSetting;
use App\Models\Product;
use App\Models\Store;
use App\Models\SubscriptionPayment;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Everything about one vendor on one page: the store, its owner, plan and
 * usage, catalog, payments, and the requests it received.
 */
class VendorController extends Controller
{
    public function show(Store $store): Response
    {
        $store->load(['owner', 'subscription.plan']);
        $subscription = $store->subscription;
        $owner = $store->owner;

        $counts = $store->products()
            ->toBase()
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as published', [ProductStatus::Published->value])
            ->selectRaw('sum(case when status = ? then 1 else 0 end) as drafts', [ProductStatus::Draft->value])
            ->selectRaw('sum(case when stock = 0 then 1 else 0 end) as sold_out')
            ->first();

        return Inertia::render('admin/vendor', [
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'slug' => $store->slug,
                'url' => route('stores.show', $store),
                'telegram_url' => PlatformSetting::current()->miniAppLink($store->slug),
                'logo' => $store->logoUrl(),
                'description' => $store->description,
                'created_at' => $store->created_at?->toIso8601String(),
                'telegram_connected' => filled($store->telegram_chat_id),
                'suspended' => $store->isSuspended(),
                'suspended_at' => $store->suspended_at?->toIso8601String(),
                ...$store->publicProfile(),
            ],
            'owner' => $owner === null ? null : [
                'name' => $owner->name,
                'email' => $owner->email,
                'phone' => $owner->getAttribute('phone'),
                'telegram_username' => $owner->telegram_username,
                'avatar' => $owner->avatar,
                'joined_at' => $owner->created_at?->toIso8601String(),
                'email_verified' => $owner->email_verified_at !== null,
            ],
            'plan' => $subscription === null ? null : [
                'name' => $subscription->plan->name,
                'free' => $subscription->plan->isFree(),
                'status' => $subscription->status->value,
                'ends_at' => $subscription->ends_at?->toIso8601String(),
                'limit' => $subscription->plan->product_limit,
            ],
            'counts' => [
                'total' => (int) ($counts->total ?? 0),
                'published' => (int) ($counts->published ?? 0),
                'drafts' => (int) ($counts->drafts ?? 0),
                'sold_out' => (int) ($counts->sold_out ?? 0),
            ],
            'products' => $store->products()
                ->with(['coverImage', 'category', 'brand'])
                ->latest()
                ->orderByDesc('id')
                ->paginate(10, pageName: 'products_page')
                ->withQueryString()
                ->through(fn (Product $product): array => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price_cents' => $product->price_cents,
                    'status' => $product->status->value,
                    'sold_out' => $product->isSoldOut(),
                    'image' => $product->coverImage?->url(),
                    'category' => $product->category?->name,
                    'brand' => $product->brand?->name,
                    'url' => $product->isPublished() ? route('stores.products.show', ['store' => $store, 'productSlug' => $product->slug]) : null,
                ]),
            'categories' => $store->categories()->withCount('products')->orderBy('sort')->orderBy('id')->get()
                ->map(fn (Category $category): array => ['name' => $category->name, 'products_count' => (int) $category->getAttribute('products_count')]),
            'brands' => $store->brands()->withCount('products')->orderBy('name')->get()
                ->map(fn (Brand $brand): array => ['name' => $brand->name, 'products_count' => (int) $brand->getAttribute('products_count')]),
            'payments' => SubscriptionPayment::query()
                ->where('store_id', $store->id)
                ->with('plan')
                ->latest()
                ->orderByDesc('id')
                ->limit(10)
                ->get()
                ->map(fn (SubscriptionPayment $payment): array => [
                    'id' => $payment->id,
                    'plan' => $payment->plan?->name,
                    'amount_cents' => $payment->amount_cents,
                    'period' => $payment->period->value,
                    'status' => $payment->status->value,
                    'created_at' => $payment->created_at?->toIso8601String(),
                    'paid_at' => $payment->paid_at?->toIso8601String(),
                ]),
            'paidTotalCents' => (int) SubscriptionPayment::query()
                ->where('store_id', $store->id)
                ->where('status', PaymentStatus::Paid)
                ->sum('amount_cents'),
            'requests' => $store->inquiries()
                ->withCount('items')
                ->latest()
                ->orderByDesc('id')
                ->limit(10)
                ->get()
                ->map(fn (Inquiry $inquiry): array => [
                    'id' => $inquiry->id,
                    'number' => $inquiry->number,
                    'customer' => $inquiry->customer_name,
                    'from_cart' => $inquiry->from_cart,
                    'items_count' => (int) $inquiry->getAttribute('items_count'),
                    'delivered' => $inquiry->vendor_notified_at !== null,
                    'failed' => $inquiry->vendor_error !== null,
                    'created_at' => $inquiry->created_at?->toIso8601String(),
                ]),
            'requestsCount' => $store->inquiries()->count(),
        ]);
    }
}
