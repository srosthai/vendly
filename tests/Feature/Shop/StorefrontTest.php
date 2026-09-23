<?php

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;

test('the public store shows only that store published products', function () {
    $vendor = User::factory()->create();
    $store = openStore($vendor, 'Smile Tea');
    $category = Category::query()->create([
        'store_id' => $store->id,
        'name' => 'Tea',
        'slug' => 'tea',
        'sort' => 1,
    ]);

    Product::query()->create([
        'store_id' => $store->id,
        'category_id' => $category->id,
        'name' => 'Jasmine',
        'slug' => 'jasmine',
        'price_cents' => 250,
        'status' => ProductStatus::Published,
    ]);
    Product::query()->create([
        'store_id' => $store->id,
        'name' => 'Hidden',
        'slug' => 'hidden',
        'price_cents' => 100,
        'status' => ProductStatus::Draft,
    ]);

    $this->get(route('stores.show', $store))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('stores/show')
            ->has('products', 1)
            ->where('products.0.name', 'Jasmine'));

    $this->get(route('stores.show', ['store' => $store, 'category' => 'tea']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('products', 1));

    $this->get(route('stores.products.show', ['store' => $store, 'productSlug' => 'jasmine']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('stores/product')->where('product.name', 'Jasmine'));
});

test('the mini app opens the store named in the link', function () {
    $vendor = User::factory()->create();
    $store = openStore($vendor, 'Smile Tea');

    $this->get(route('mini-app', ['startapp' => $store->slug]))
        ->assertRedirect(route('stores.show', $store));

    $this->get(route('mini-app'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('stores/enter'));
});
