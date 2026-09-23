<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;

test('the products list filters by status, category, and brand and sorts', function () {
    $vendor = User::factory()->create();
    $store = openStore($vendor, 'Smile Tea');
    $tea = Category::query()->create(['store_id' => $store->id, 'name' => 'Tea', 'slug' => 'tea', 'sort' => 1]);
    $leaf = Brand::query()->create(['store_id' => $store->id, 'name' => 'Leaf', 'slug' => 'leaf']);

    Product::factory()->for($store)->create(['name' => 'Cheap tea', 'price_cents' => 100, 'category_id' => $tea->id, 'stock' => 9]);
    Product::factory()->for($store)->draft()->create(['name' => 'Draft cake', 'price_cents' => 900, 'brand_id' => $leaf->id]);
    Product::factory()->for($store)->soldOut()->create(['name' => 'Gone bun', 'price_cents' => 500]);

    $list = fn (array $query) => $this->actingAs($vendor)->get(route('vendor.products', $query));

    $list(['status' => 'draft'])->assertInertia(fn ($page) => $page->has('products.data', 1)->where('products.data.0.name', 'Draft cake'));
    $list(['status' => 'sold_out'])->assertInertia(fn ($page) => $page->has('products.data', 1)->where('products.data.0.name', 'Gone bun'));
    $list(['status' => 'published'])->assertInertia(fn ($page) => $page->has('products.data', 2));
    $list(['category' => (string) $tea->id])->assertInertia(fn ($page) => $page->has('products.data', 1)->where('products.data.0.category', 'Tea'));
    $list(['brand' => (string) $leaf->id])->assertInertia(fn ($page) => $page->has('products.data', 1)->where('products.data.0.brand', 'Leaf'));
    $list(['sort' => 'price_high'])->assertInertia(fn ($page) => $page->where('products.data.0.name', 'Draft cake'));
    $list(['sort' => 'price_low'])->assertInertia(fn ($page) => $page->where('products.data.0.name', 'Cheap tea'));
    $list(['sort' => 'stock'])->assertInertia(fn ($page) => $page->where('products.data.0.name', 'Gone bun'));
    $list(['sort' => 'name'])->assertInertia(fn ($page) => $page->where('products.data.0.name', 'Cheap tea'));
    $list(['status' => 'weird', 'sort' => 'weird'])->assertInertia(fn ($page) => $page->where('filters.status', 'all')->where('filters.sort', 'newest')->has('products.data', 3));
});

test('the products list pages at twenty', function () {
    $vendor = User::factory()->create();
    Product::factory()->for(openStore($vendor, 'Big Tea', 50))->count(21)->create();

    $this->actingAs($vendor)->get(route('vendor.products'))
        ->assertInertia(fn ($page) => $page->has('products.data', 20)->where('products.total', 21));
    $this->actingAs($vendor)->get(route('vendor.products', ['page' => 2]))
        ->assertInertia(fn ($page) => $page->has('products.data', 1));
});

test('the product sheet opens only for the vendor\'s own product', function () {
    $vendor = User::factory()->create();
    $store = openStore($vendor, 'Smile Tea');
    $mine = Product::factory()->for($store)->create();
    $theirs = Product::factory()->for(openStore(User::factory()->create(), 'Other'))->create();

    $this->actingAs($vendor)->get(route('vendor.products', ['edit' => $mine->id]))
        ->assertInertia(fn ($page) => $page->where('editing.id', $mine->id)->where('creating', false));
    $this->actingAs($vendor)->get(route('vendor.products', ['edit' => $theirs->id]))
        ->assertInertia(fn ($page) => $page->where('editing', null));
    $this->actingAs($vendor)->get(route('vendor.products.create'))
        ->assertRedirect(route('vendor.products', ['create' => 1]));
    $this->actingAs($vendor)->get(route('vendor.products', ['create' => 1]))
        ->assertInertia(fn ($page) => $page->where('creating', true)->where('editing', null));
    $this->actingAs($vendor)->get(route('vendor.products.edit', $theirs))->assertForbidden();
});

test('categories and brands list with product counts, search, and sort', function () {
    $vendor = User::factory()->create();
    $store = openStore($vendor, 'Smile Tea');
    $tea = Category::query()->create(['store_id' => $store->id, 'name' => 'Tea', 'slug' => 'tea', 'sort' => 2]);
    Category::query()->create(['store_id' => $store->id, 'name' => 'Cakes', 'slug' => 'cakes', 'sort' => 1]);
    $leaf = Brand::query()->create(['store_id' => $store->id, 'name' => 'Leaf', 'slug' => 'leaf']);
    Brand::query()->create(['store_id' => $store->id, 'name' => 'Acme', 'slug' => 'acme']);
    Product::factory()->for($store)->count(2)->create(['category_id' => $tea->id, 'brand_id' => $leaf->id]);
    Category::query()->create(['store_id' => openStore(User::factory()->create(), 'Other')->id, 'name' => 'Not mine', 'slug' => 'not-mine', 'sort' => 1]);

    $this->actingAs($vendor)->get(route('vendor.categories'))
        ->assertInertia(fn ($page) => $page
            ->has('categories.data', 2)
            ->where('categories.data.0.name', 'Cakes')
            ->where('categories.data.1.products_count', 2));
    $this->actingAs($vendor)->get(route('vendor.categories', ['sort' => 'products']))
        ->assertInertia(fn ($page) => $page->where('categories.data.0.name', 'Tea'));
    $this->actingAs($vendor)->get(route('vendor.categories', ['search' => 'cak']))
        ->assertInertia(fn ($page) => $page->has('categories.data', 1)->where('filters.search', 'cak'));
    $this->actingAs($vendor)->get(route('vendor.brands'))
        ->assertInertia(fn ($page) => $page->where('brands.data.0.name', 'Acme')->where('brands.data.1.products_count', 2));
});
