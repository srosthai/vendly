<?php

use App\Enums\ProductStatus;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Store;
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
