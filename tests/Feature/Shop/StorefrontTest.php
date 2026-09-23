<?php

use App\Enums\ProductStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

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
            ->has('products.data', 1)
            ->where('products.data.0.name', 'Jasmine'));

    $this->get(route('stores.show', ['store' => $store, 'category' => 'tea']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('products.data', 1));

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

test('the storefront shows 24 products at a time, newest first', function () {
    $store = openStore(User::factory()->create(), 'Big Tea');
    Product::factory()->for($store)->count(30)->sequence(fn ($sequence) => ['created_at' => now()->subMinutes(30 - $sequence->index)])->create();

    $this->get(route('stores.show', $store))
        ->assertInertia(fn ($page) => $page
            ->has('products.data', 24)
            ->where('products.data.0.id', Product::query()->latest('created_at')->value('id')));

    $this->get(route('stores.show', ['store' => $store, 'page' => 2]))
        ->assertInertia(fn ($page) => $page->has('products.data', 6));
});

test('a product card shows its cover photo', function () {
    $store = openStore(User::factory()->create(), 'Photo Tea');
    $product = Product::factory()->for($store)->create();
    $product->images()->create(['path' => 'products/second.jpg', 'sort' => 2]);
    $product->images()->create(['path' => 'products/cover.jpg', 'sort' => 1]);

    $this->get(route('stores.show', $store))
        ->assertInertia(fn ($page) => $page->where('products.data.0.image', fn (string $url): bool => str_ends_with($url, 'products/cover.jpg')));
});

test('foreign keys used by the storefront and admin lists are indexed', function () {
    expect(Schema::hasIndex('products', ['category_id']))->toBeTrue()
        ->and(Schema::hasIndex('products', ['brand_id']))->toBeTrue()
        ->and(Schema::hasIndex('product_images', ['product_id', 'sort']))->toBeTrue()
        ->and(Schema::hasIndex('cart_items', ['product_id']))->toBeTrue()
        ->and(Schema::hasIndex('inquiry_items', ['inquiry_id']))->toBeTrue()
        ->and(Schema::hasIndex('subscription_payments', ['store_id', 'status']))->toBeTrue();
});

test('a vendor uploads, replaces, and removes the store logo', function () {
    Storage::fake('public');
    $vendor = User::factory()->create();
    $store = openStore($vendor, 'Logo Tea');

    $this->actingAs($vendor)->put(route('vendor.store.update'), [
        'name' => 'Logo Tea',
        'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
    ])->assertRedirect();

    $first = $store->fresh()->logo_path;
    Storage::disk('public')->assertExists($first);

    $this->get(route('stores.show', $store))
        ->assertInertia(fn ($page) => $page->where('store.logo', Storage::disk('public')->url($first)));

    $this->actingAs($vendor)->put(route('vendor.store.update'), [
        'name' => 'Logo Tea',
        'logo' => UploadedFile::fake()->image('new.png', 200, 200),
    ])->assertRedirect();

    Storage::disk('public')->assertMissing($first);

    $second = $store->fresh()->logo_path;

    $this->actingAs($vendor)->put(route('vendor.store.update'), [
        'name' => 'Logo Tea',
        'remove_logo' => '1',
    ])->assertRedirect();

    expect($store->fresh()->logo_path)->toBeNull();
    Storage::disk('public')->assertMissing($second);
});

test('a store logo must be a small image', function () {
    Storage::fake('public');
    $vendor = User::factory()->create();
    openStore($vendor, 'Logo Tea');

    $this->actingAs($vendor)->put(route('vendor.store.update'), [
        'name' => 'Logo Tea',
        'logo' => UploadedFile::fake()->create('logo.pdf', 10, 'application/pdf'),
    ])->assertInvalid(['logo']);
});

test('the product page shows every photo in order', function () {
    $store = openStore(User::factory()->create(), 'Photo Tea');
    $product = Product::factory()->for($store)->create();
    $product->images()->create(['path' => 'products/b.jpg', 'sort' => 2]);
    $product->images()->create(['path' => 'products/a.jpg', 'sort' => 1]);

    $this->get(route('stores.products.show', ['store' => $store, 'productSlug' => $product->slug]))
        ->assertInertia(fn ($page) => $page
            ->has('product.images', 2)
            ->where('product.images.0', fn (string $url): bool => str_ends_with($url, 'products/a.jpg')));
});

test('the store page shows the result of sending a cart', function () {
    $store = openStore(User::factory()->create(), 'Sent Tea');

    $this->withSession(['status' => 'Sent to the store on Telegram.'])
        ->get(route('stores.show', $store))
        ->assertInertia(fn ($page) => $page->where('status', 'Sent to the store on Telegram.'));
});

test('the storefront filters by brand and sorts by price', function () {
    $store = openStore(User::factory()->create(), 'Brand Tea');
    $leaf = Brand::query()->create(['store_id' => $store->id, 'name' => 'Leaf', 'slug' => 'leaf']);
    Brand::query()->create(['store_id' => $store->id, 'name' => 'Unused', 'slug' => 'unused']);

    Product::factory()->for($store)->create(['name' => 'Cheap', 'price_cents' => 100, 'brand_id' => $leaf->id]);
    Product::factory()->for($store)->create(['name' => 'Dear', 'price_cents' => 900, 'brand_id' => $leaf->id]);
    Product::factory()->for($store)->create(['name' => 'Middle', 'price_cents' => 500]);

    $this->get(route('stores.show', ['store' => $store, 'sort' => 'price-low']))
        ->assertInertia(fn ($page) => $page
            ->where('brands', [['name' => 'Leaf', 'slug' => 'leaf']])
            ->where('filters.sort', 'price-low')
            ->where('products.data.0.name', 'Cheap')
            ->where('products.data.2.name', 'Dear'));

    $this->get(route('stores.show', ['store' => $store, 'brand' => 'leaf', 'sort' => 'price-high']))
        ->assertInertia(fn ($page) => $page
            ->has('products.data', 2)
            ->where('products.data.0.name', 'Dear'));

    $this->get(route('stores.show', ['store' => $store, 'sort' => 'anything']))
        ->assertInertia(fn ($page) => $page->where('filters.sort', 'newest'));
});

test('buy links open the product in the telegram mini app once the bot is set', function () {
    $store = openStore(User::factory()->create(), 'Link Tea');
    $product = Product::factory()->for($store)->create();

    config(['services.telegram.bot_username' => null]);

    $this->get(route('stores.show', $store))
        ->assertInertia(fn ($page) => $page
            ->where('store.telegram_url', null)
            ->where('products.data.0.telegram_url', null));

    config([
        'services.telegram.bot_username' => 'VendlyBot',
        'services.telegram.mini_app_short_name' => 'shop',
    ]);

    $this->get(route('stores.show', $store))
        ->assertInertia(fn ($page) => $page
            ->where('store.telegram_url', 'https://t.me/VendlyBot/shop?startapp='.$store->slug)
            ->where('products.data.0.telegram_url', 'https://t.me/VendlyBot/shop?startapp=p_'.$product->id));

    $this->get(route('stores.products.show', ['store' => $store, 'productSlug' => $product->slug]))
        ->assertInertia(fn ($page) => $page->where('product.telegram_url', 'https://t.me/VendlyBot/shop?startapp=p_'.$product->id));
});

test('the mini app opens the product named in the link', function () {
    $store = openStore(User::factory()->create(), 'Deep Tea');
    $product = Product::factory()->for($store)->create();
    $draft = Product::factory()->for($store)->draft()->create();

    $this->get(route('mini-app', ['startapp' => 'p_'.$product->id]))
        ->assertRedirect(route('stores.products.show', ['store' => $store, 'productSlug' => $product->slug]))
        ->assertSessionHas('mini_app', true);

    $this->get(route('mini-app', ['startapp' => 'p_'.$draft->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('stores/enter'));
});
