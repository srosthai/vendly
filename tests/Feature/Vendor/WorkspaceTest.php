<?php

use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Store;
use App\Models\SubscriptionPayment;
use App\Models\User;

test('a vendor can open the store tools and another vendor cannot', function () {
    $vendor = User::factory()->create();
    $store = openStore($vendor, 'Smile Tea');
    $other = User::factory()->create();

    $this->actingAs($vendor)
        ->get(route('vendor.products'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('vendor/products')
            ->where('usage.limit', 10));

    $this->actingAs($vendor)
        ->get(route('vendor.store'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('vendor/store')
            ->where('store.web_url', route('stores.show', $store)));

    $this->actingAs($other)->get(route('vendor.products'))->assertRedirect(route('selling.create'));
    $this->actingAs($other)->post(route('categories.store'), ['name' => 'Tea'])->assertForbidden();
});

test('the plan page shows paid plans', function () {
    $vendor = User::factory()->create();
    openStore($vendor, 'Smile Tea');

    Plan::query()->create([
        'name' => 'Starter',
        'price_cents' => 500,
        'product_limit' => 100,
        'is_active' => true,
        'is_default' => false,
    ]);

    $this->actingAs($vendor)
        ->get(route('vendor.plan'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('vendor/plan')->has('plans', 1));
});

test('a suspended vendor sees why and cannot change anything', function () {
    config(['services.telegram.bot_username' => 'VendlyBot']);
    $vendor = User::factory()->create();
    $store = openStore($vendor, 'Paused Tea');
    $product = Product::query()->create([
        'store_id' => $store->id,
        'name' => 'Jasmine',
        'slug' => 'jasmine',
        'price_cents' => 250,
        'status' => ProductStatus::Draft,
    ]);
    $paid = Plan::query()->create([
        'name' => 'Starter',
        'price_cents' => 500,
        'product_limit' => 100,
        'is_active' => true,
        'is_default' => false,
    ]);

    $store->suspended_at = now();
    $store->save();

    $this->actingAs($vendor)
        ->get(route('vendor.products'))
        ->assertForbidden()
        ->assertInertia(fn ($page) => $page->component('vendor/suspended')->where('store.name', 'Paused Tea'));

    $this->put(route('vendor.store.update'), ['name' => 'Renamed'])->assertForbidden();
    $this->post(route('categories.store'), ['name' => 'Green'])->assertForbidden();
    $this->post(route('brands.store'), ['name' => 'Leaf'])->assertForbidden();
    $this->post(route('products.store'), ['name' => 'New', 'price' => '1.00'])->assertForbidden();
    $this->put(route('products.update', $product), ['name' => 'Changed', 'price' => '1.00'])->assertForbidden();
    $this->post(route('products.publish', $product))->assertForbidden();
    $this->postJson(route('telegram.link'))->assertForbidden();
    $this->postJson(route('plans.payments.store', $paid))->assertForbidden();

    expect($store->fresh()->name)->toBe('Paused Tea')
        ->and($store->categories()->count())->toBe(0)
        ->and($product->fresh()->status)->toBe(ProductStatus::Draft);
});

test('a vendor cannot change another store\'s product', function () {
    $owner = User::factory()->create();
    $store = openStore($owner, 'Owner Tea');
    $product = Product::query()->create([
        'store_id' => $store->id,
        'name' => 'Jasmine',
        'slug' => 'jasmine',
        'price_cents' => 250,
        'status' => ProductStatus::Draft,
    ]);
    $intruder = User::factory()->create();
    openStore($intruder, 'Intruder Tea');

    $this->actingAs($intruder)->put(route('products.update', $product), ['name' => 'Mine', 'price' => '1.00'])->assertForbidden();
    $this->actingAs($intruder)->post(route('products.publish', $product))->assertForbidden();

    expect($product->fresh()->name)->toBe('Jasmine')
        ->and($product->fresh()->status)->toBe(ProductStatus::Draft);
});

test('only an admin can suspend and restore a store', function () {
    $store = openStore(User::factory()->create(), 'Watched Tea');
    $admin = User::factory()->create();
    $admin->is_admin = true;
    $admin->save();

    $this->actingAs(User::factory()->create())->post(route('admin.stores.suspend', $store))->assertForbidden();
    expect($store->fresh()->isSuspended())->toBeFalse();

    $this->actingAs($admin)->post(route('admin.stores.suspend', $store))->assertRedirect();
    expect($store->fresh()->isSuspended())->toBeTrue();
    $this->get(route('stores.show', $store))->assertNotFound();

    $this->actingAs($admin)->delete(route('admin.stores.restore', $store))->assertRedirect();
    expect(Store::query()->find($store->id)->isSuspended())->toBeFalse();
});

test('telegram link requests are rate limited', function () {
    config(['services.telegram.bot_username' => 'VendlyBot']);
    $vendor = User::factory()->create();
    openStore($vendor, 'Busy Tea');

    for ($attempt = 0; $attempt < 6; $attempt++) {
        $this->actingAs($vendor)->postJson(route('telegram.link'))->assertOk();
    }

    $this->actingAs($vendor)->postJson(route('telegram.link'))->assertTooManyRequests();
});

test('a vendor searches their own products', function () {
    $vendor = User::factory()->create();
    $store = openStore($vendor, 'Smile Tea');
    Product::factory()->for($store)->create(['name' => 'Jasmine pearls']);
    Product::factory()->for($store)->create(['name' => 'Oolong']);
    Product::factory()->for(openStore(User::factory()->create(), 'Other Tea'))->create(['name' => 'Jasmine green']);

    $this->actingAs($vendor)
        ->get(route('vendor.products', ['search' => 'jasmine']))
        ->assertInertia(fn ($page) => $page
            ->where('search', 'jasmine')
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Jasmine pearls'));
});

test('the sidebar card shows plan room for a vendor and undelivered requests for an admin', function () {
    $vendor = User::factory()->create();
    $store = openStore($vendor, 'Smile Tea', 10);
    Product::factory()->for($store)->count(3)->create();

    $this->actingAs($vendor)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('workspace.kind', 'vendor')
            ->where('workspace.published', 3)
            ->where('workspace.limit', 10));

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.vendors', ['search' => 'smile']))
        ->assertInertia(fn ($page) => $page
            ->where('workspace.kind', 'admin')
            ->has('vendors.data', 1));

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('workspace', null));
});

test('the vendor overview counts products and this week\'s requests per day', function () {
    $this->freezeSecond();
    $vendor = User::factory()->create();
    $store = openStore($vendor, 'Smile Tea', 10);
    Product::factory()->for($store)->count(2)->create();
    Product::factory()->for($store)->draft()->create();

    foreach ([0, 0, 3, 9] as $daysAgo) {
        $inquiry = $store->inquiries()->create([
            'public_id' => (string) str()->ulid(),
            'number' => $store->inquiries()->max('number') + 1,
            'customer_name' => 'Ada',
        ]);
        $inquiry->forceFill(['created_at' => now()->subDays($daysAgo)])->save();
        $inquiry->items()->create(['name' => 'Jasmine', 'price_cents' => 250, 'quantity' => 1]);
    }

    $this->actingAs($vendor)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->component('vendor/overview')
            ->where('stats.published', 2)
            ->where('stats.drafts', 1)
            ->where('stats.requests_this_week', 3)
            ->where('stats.requests_trend', [0, 0, 0, 1, 0, 0, 2])
            ->has('recentRequests', 4)
            ->where('recentRequests.0.total', '$2.50'));
});

test('the admin overview counts vendors, paid plans, money this month, and undelivered requests', function () {
    $store = openStore(User::factory()->create(), 'Smile Tea');
    $paid = Plan::query()->create(['name' => 'Starter', 'price_cents' => 500, 'product_limit' => 100, 'is_active' => true, 'is_default' => false]);
    $store->subscription->update(['plan_id' => $paid->id, 'ends_at' => now()->addMonth()]);
    SubscriptionPayment::query()->create([
        'public_id' => 'subpay_one',
        'store_id' => $store->id,
        'plan_id' => $paid->id,
        'amount_cents' => 500,
        'status' => PaymentStatus::Paid,
        'paid_at' => now(),
    ]);
    $store->inquiries()->create(['public_id' => 'inq_one', 'number' => 1, 'customer_name' => 'Ada']);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->component('admin/overview')
            ->where('stats.vendors', 1)
            ->where('stats.paid_plans', 1)
            ->where('stats.revenue_this_month', '$5.00')
            ->where('stats.undelivered', 1)
            ->has('recentPayments', 1));
});

test('a customer dashboard lists only their own requests', function () {
    $store = openStore(User::factory()->create(), 'Smile Tea');
    $customer = User::factory()->create();
    $store->inquiries()->create(['public_id' => 'mine', 'number' => 1, 'user_id' => $customer->id, 'customer_name' => 'Me']);
    $store->inquiries()->create(['public_id' => 'theirs', 'number' => 2, 'user_id' => User::factory()->create()->id, 'customer_name' => 'Them']);

    $this->actingAs($customer)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->has('requests', 1)
            ->where('requests.0.reference', '#1'));
});

test('connecting telegram explains when the bot is not set up', function () {
    config(['services.telegram.bot_username' => null]);
    $vendor = User::factory()->create();
    openStore($vendor, 'Smile Tea');

    $this->actingAs($vendor)
        ->post(route('telegram.link'))
        ->assertInvalid(['telegram' => 'not set up yet']);
});
