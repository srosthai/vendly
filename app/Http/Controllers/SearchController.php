<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Inquiry;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Store;
use App\Models\SubscriptionPayment;
use App\Models\Testimonial;
use App\Models\User;
use App\Support\Money;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The top bar search. An admin searches the whole back office; a vendor
 * searches only their own store. Results come in small groups, each item
 * with where to go.
 */
class SearchController extends Controller
{
    private const PerGroup = 5;

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate(['q' => ['nullable', 'string', 'max:100']]);

        $query = trim($request->string('q')->toString());
        $user = $request->user();

        if (mb_strlen($query) < 2 || ! $user instanceof User) {
            return response()->json(['query' => $query, 'groups' => []]);
        }

        $pattern = '%'.$query.'%';

        if ($user->is_admin) {
            $groups = $this->adminGroups($query, $pattern);
        } else {
            $store = $user->store()->first();
            $groups = $store instanceof Store ? $this->vendorGroups($store, $query, $pattern) : [];
        }

        return response()->json([
            'query' => $query,
            'groups' => array_values(array_filter($groups, fn (array $group): bool => $group['items'] !== [])),
        ]);
    }

    /**
     * @return list<array{label: string, items: array<int, array{title: string, subtitle: string|null, url: string}>}>
     */
    private function adminGroups(string $query, string $pattern): array
    {
        return [
            $this->pages($query, [
                ['Dashboard', route('dashboard')],
                ['Vendors', route('admin.vendors')],
                ['Plans', route('admin.plans')],
                ['Payments', route('admin.payments')],
                ['Requests', route('admin.requests')],
                ['Telegram', route('admin.telegram')],
                ['Site settings', route('admin.site')],
                ['Testimonials', route('admin.testimonials')],
            ]),
            [
                'label' => 'Vendors',
                'items' => Store::query()
                    ->with('owner')
                    ->where(fn ($where) => $where
                        ->whereLike('name', $pattern)
                        ->orWhereLike('slug', $pattern)
                        ->orWhereHas('owner', fn ($owner) => $owner->whereLike('email', $pattern)->orWhereLike('name', $pattern)))
                    ->orderBy('name')
                    ->limit(self::PerGroup)
                    ->get()
                    ->map(fn (Store $store): array => [
                        'title' => $store->name,
                        'subtitle' => '/s/'.$store->slug.($store->owner ? ', '.$store->owner->name : ''),
                        'url' => route('admin.vendors.show', $store),
                    ])->values()->all(),
            ],
            [
                'label' => 'Plans',
                'items' => Plan::query()
                    ->whereLike('name', $pattern)
                    ->orderBy('price_cents')
                    ->limit(self::PerGroup)
                    ->get()
                    ->map(fn (Plan $plan): array => [
                        'title' => $plan->name,
                        'subtitle' => ($plan->isFree() ? 'Free' : Money::format($plan->price_cents).' a month').', up to '.$plan->product_limit.' products',
                        'url' => route('admin.plans', ['search' => $plan->name]),
                    ])->values()->all(),
            ],
            [
                'label' => 'Payments',
                'items' => SubscriptionPayment::query()
                    ->with('store')
                    ->where(fn ($where) => $where
                        ->whereLike('public_id', $pattern)
                        ->orWhereHas('store', fn ($store) => $store->whereLike('name', $pattern)))
                    ->latest()
                    ->limit(self::PerGroup)
                    ->get()
                    ->map(fn (SubscriptionPayment $payment): array => [
                        'title' => Money::format($payment->amount_cents).' from '.($payment->store->name ?? 'a deleted store'),
                        'subtitle' => ucfirst($payment->status->value).', '.$payment->public_id,
                        'url' => route('admin.payments', ['search' => $payment->public_id]),
                    ])->values()->all(),
            ],
            [
                'label' => 'Requests',
                'items' => $this->requests(Inquiry::query()->with('store'), $pattern, fn (Inquiry $inquiry): string => route('admin.requests', ['search' => $inquiry->customer_name])),
            ],
            [
                'label' => 'Testimonials',
                'items' => Testimonial::query()
                    ->where(fn ($where) => $where->whereLike('name', $pattern)->orWhereLike('quote', $pattern))
                    ->orderBy('sort')
                    ->limit(self::PerGroup)
                    ->get()
                    ->map(fn (Testimonial $testimonial): array => [
                        'title' => $testimonial->name,
                        'subtitle' => $testimonial->published_at !== null ? 'On the website' : 'Hidden',
                        'url' => route('admin.testimonials', ['search' => $testimonial->name]),
                    ])->values()->all(),
            ],
        ];
    }

    /**
     * @return list<array{label: string, items: array<int, array{title: string, subtitle: string|null, url: string}>}>
     */
    private function vendorGroups(Store $store, string $query, string $pattern): array
    {
        return [
            $this->pages($query, [
                ['Dashboard', route('dashboard')],
                ['Products', route('vendor.products')],
                ['New product', route('vendor.products', ['create' => 1])],
                ['Categories', route('vendor.categories')],
                ['Brands', route('vendor.brands')],
                ['Store', route('vendor.store')],
                ['Telegram', route('vendor.telegram')],
                ['Plan', route('vendor.plan')],
            ]),
            [
                'label' => 'Products',
                'items' => $store->products()
                    ->whereLike('name', $pattern)
                    ->orderBy('name')
                    ->limit(self::PerGroup)
                    ->get()
                    ->map(fn (Product $product): array => [
                        'title' => $product->name,
                        'subtitle' => Money::format($product->price_cents).', '.($product->isPublished() ? 'published' : 'draft'),
                        'url' => route('vendor.products', ['edit' => $product->id]),
                    ])->values()->all(),
            ],
            [
                'label' => 'Categories',
                'items' => $store->categories()
                    ->whereLike('name', $pattern)
                    ->orderBy('name')
                    ->limit(self::PerGroup)
                    ->get()
                    ->map(fn (Category $category): array => [
                        'title' => $category->name,
                        'subtitle' => 'Products in this category',
                        'url' => route('vendor.products', ['category' => $category->id]),
                    ])->values()->all(),
            ],
            [
                'label' => 'Brands',
                'items' => $store->brands()
                    ->whereLike('name', $pattern)
                    ->orderBy('name')
                    ->limit(self::PerGroup)
                    ->get()
                    ->map(fn (Brand $brand): array => [
                        'title' => $brand->name,
                        'subtitle' => 'Products of this brand',
                        'url' => route('vendor.products', ['brand' => $brand->id]),
                    ])->values()->all(),
            ],
            [
                'label' => 'Requests',
                'items' => $this->requests($store->inquiries()->getQuery()->with('store'), $pattern, fn (): string => route('dashboard')),
            ],
        ];
    }

    /**
     * @param  list<array{0: string, 1: string}>  $pages
     * @return array{label: string, items: array<int, array{title: string, subtitle: string|null, url: string}>}
     */
    private function pages(string $query, array $pages): array
    {
        $needle = mb_strtolower($query);

        return [
            'label' => 'Pages',
            'items' => array_values(array_map(
                fn (array $page): array => ['title' => $page[0], 'subtitle' => null, 'url' => $page[1]],
                array_filter($pages, fn (array $page): bool => str_contains(mb_strtolower($page[0]), $needle)),
            )),
        ];
    }

    /**
     * @param  Builder<Inquiry>  $inquiries
     * @param  Closure(Inquiry): string  $url
     * @return array<int, array{title: string, subtitle: string|null, url: string}>
     */
    private function requests(Builder $inquiries, string $pattern, Closure $url): array
    {
        return $inquiries
            ->where(fn ($where) => $where->whereLike('customer_name', $pattern)->orWhereLike('contact', $pattern))
            ->latest()
            ->limit(self::PerGroup)
            ->get()
            ->map(fn (Inquiry $inquiry): array => [
                'title' => $inquiry->customer_name,
                'subtitle' => $inquiry->reference().', '.$inquiry->store->name,
                'url' => $url($inquiry),
            ])->values()->all();
    }
}
