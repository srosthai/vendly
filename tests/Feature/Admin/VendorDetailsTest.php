<?php

use App\Enums\PaymentStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Inquiry;
use App\Models\Plan;
use App\Models\Product;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\Support\Str;

test('only an admin can open a vendor', function () {
    $store = openStore(User::factory()->create(), 'Private Tea');

    $this->get(route('admin.vendors.show', $store))->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create())->get(route('admin.vendors.show', $store))->assertForbidden();
});

test('the vendor page shows that store and nothing from other stores', function () {
    $owner = User::factory()->create(['name' => 'Sokha', 'email' => 'sokha@example.com', 'telegram_username' => 'sokha']);
    $store = openStore($owner, 'Smile Tea');
    $other = openStore(User::factory()->create(), 'Other Shop');

    $tea = Category::query()->create(['store_id' => $store->id, 'name' => 'Tea', 'slug' => 'tea', 'sort' => 1]);
    $leaf = Brand::query()->create(['store_id' => $store->id, 'name' => 'Leaf', 'slug' => 'leaf']);
    Product::factory()->for($store)->count(2)->create(['category_id' => $tea->id, 'brand_id' => $leaf->id]);
    Product::factory()->for($store)->draft()->create();
    Product::factory()->for($store)->soldOut()->create();
    Product::factory()->for($other)->count(3)->create();

    $plan = Plan::query()->create(['name' => 'Starter', 'price_cents' => 500, 'product_limit' => 100, 'is_active' => true, 'is_default' => false]);
    foreach ([[$store, PaymentStatus::Paid], [$store, PaymentStatus::Pending], [$other, PaymentStatus::Paid]] as [$payer, $status]) {
        SubscriptionPayment::query()->create([
            'public_id' => 'subpay_'.Str::lower((string) Str::ulid()),
            'store_id' => $payer->id,
            'plan_id' => $plan->id,
            'amount_cents' => 500,
            'status' => $status,
            'paid_at' => $status === PaymentStatus::Paid ? now() : null,
        ]);
    }

    Inquiry::query()->create(['public_id' => 'inq_1', 'number' => 7, 'store_id' => $store->id, 'customer_name' => 'Dara', 'from_cart' => false, 'vendor_notified_at' => now()]);
    Inquiry::query()->create(['public_id' => 'inq_2', 'number' => 1, 'store_id' => $other->id, 'customer_name' => 'Someone', 'from_cart' => false]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.vendors.show', $store))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/vendor')
            ->where('store.name', 'Smile Tea')
            ->where('store.suspended', false)
            ->where('owner.email', 'sokha@example.com')
            ->where('owner.telegram_username', 'sokha')
            ->where('plan.name', 'Free')
            ->where('counts', ['total' => 4, 'published' => 3, 'drafts' => 1, 'sold_out' => 1])
            ->has('products.data', 4)
            ->where('categories', [['name' => 'Tea', 'products_count' => 2]])
            ->where('brands', [['name' => 'Leaf', 'products_count' => 2]])
            ->has('payments', 2)
            ->where('paidTotalCents', 500)
            ->has('requests', 1)
            ->where('requests.0.customer', 'Dara')
            ->where('requests.0.delivered', true)
            ->where('requestsCount', 1));
});

test('the vendor page lists products ten at a time', function () {
    $store = openStore(User::factory()->create(), 'Many Tea');
    Product::factory()->for($store)->count(12)->create();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.vendors.show', ['store' => $store, 'products_page' => 2]))
        ->assertInertia(fn ($page) => $page->has('products.data', 2));
});
