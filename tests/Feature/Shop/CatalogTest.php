<?php

use App\Enums\ProductStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

test('a vendor cannot update another store catalog', function () {
    Http::preventStrayRequests();
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $store = openStore($owner, 'Smile Tea');
    $otherStore = openStore($other, 'Other Tea');

    $category = Category::query()->create([
        'store_id' => $otherStore->id,
        'name' => 'Leaves',
        'slug' => 'leaves',
    ]);

    $product = Product::query()->create([
        'store_id' => $otherStore->id,
        'name' => 'Secret',
        'slug' => 'secret',
        'price_cents' => 100,
        'status' => ProductStatus::Draft,
    ]);

    $this->actingAs($owner)
        ->put(route('products.update', $product), [
            'name' => 'Stolen',
            'price' => '1.00',
        ])
        ->assertForbidden();

    $this->actingAs($owner)
        ->post(route('products.store'), [
            'name' => 'Jasmine',
            'price' => '2.50',
            'category_id' => $category->id,
        ])
        ->assertInvalid(['category_id']);

    expect($product->fresh()->name)->toBe('Secret')
        ->and($store->products()->count())->toBe(0);
});

test('the eleventh publish on a ten product plan fails and drafts do not count', function () {
    Http::preventStrayRequests();
    $user = User::factory()->create();
    $store = openStore($user, 'Smile Tea', 10);

    foreach (range(1, 5) as $number) {
        Product::query()->create([
            'store_id' => $store->id,
            'name' => 'Draft '.$number,
            'slug' => 'draft-'.$number,
            'price_cents' => 100,
            'status' => ProductStatus::Draft,
        ]);
    }

    foreach (range(1, 10) as $number) {
        $product = Product::query()->create([
            'store_id' => $store->id,
            'name' => 'Tea '.$number,
            'slug' => 'tea-'.$number,
            'price_cents' => 100,
            'status' => ProductStatus::Draft,
        ]);

        $this->actingAs($user)
            ->post(route('products.publish', $product))
            ->assertRedirect();
    }

    $eleventh = Product::query()->create([
        'store_id' => $store->id,
        'name' => 'Tea 11',
        'slug' => 'tea-11',
        'price_cents' => 100,
        'status' => ProductStatus::Draft,
    ]);

    $this->actingAs($user)
        ->post(route('products.publish', $eleventh))
        ->assertInvalid(['status']);

    expect($store->products()->published()->count())->toBe(10)
        ->and($eleventh->fresh()->status)->toBe(ProductStatus::Draft);
});

test('a suspended store is hidden and product text is stored without html', function () {
    Http::preventStrayRequests();
    Storage::fake('public');
    $user = User::factory()->create();
    $store = openStore($user, 'Smile Tea');

    $this->actingAs($user)->post(route('products.store'), [
        'name' => 'Jasmine',
        'description' => '<script>alert(1)</script>Leaf tea',
        'price' => '2.50',
        'images' => [UploadedFile::fake()->image('tea.jpg')],
    ])->assertRedirect();

    $product = Product::query()->first();

    expect($product)->not->toBeNull()
        ->and($product->description)->toBe('alert(1)Leaf tea')
        ->and($product->images)->toHaveCount(1);

    Storage::disk('public')->assertExists($product->images->first()->path);

    $this->post(route('products.publish', $product))->assertRedirect();

    $store->suspended_at = now();
    $store->save();

    $this->get(route('stores.show', $store))->assertNotFound();
});

test('opening a store does not call cutluy', function () {
    Http::preventStrayRequests();
    $user = User::factory()->create();

    Plan::query()->create([
        'name' => 'Free',
        'price_cents' => 0,
        'product_limit' => 10,
        'is_active' => true,
        'is_default' => true,
    ]);

    $this->actingAs($user)->post(route('stores.store'), [
        'name' => 'Smile Tea',
    ])->assertRedirect();

    Http::assertNothingSent();
    expect(Store::query()->first()?->subscription?->plan?->price_cents)->toBe(0);
});

test('a vendor renames and deletes a category and a brand, and products keep existing', function () {
    $vendor = User::factory()->create();
    $store = openStore($vendor, 'Smile Tea');
    $category = Category::query()->create(['store_id' => $store->id, 'name' => 'Leaves', 'slug' => 'leaves']);
    $brand = Brand::query()->create(['store_id' => $store->id, 'name' => 'Leaf Co', 'slug' => 'leaf-co']);
    $product = Product::factory()->for($store)->create(['category_id' => $category->id, 'brand_id' => $brand->id]);

    $this->actingAs($vendor)->put(route('categories.update', $category), ['name' => 'Loose leaf'])->assertRedirect();
    $this->actingAs($vendor)->put(route('brands.update', $brand), ['name' => 'Leaf and Co'])->assertRedirect();

    expect($category->fresh())->name->toBe('Loose leaf')->slug->toBe('leaves')
        ->and($brand->fresh()->name)->toBe('Leaf and Co');

    $this->actingAs($vendor)->delete(route('categories.destroy', $category))->assertRedirect();
    $this->actingAs($vendor)->delete(route('brands.destroy', $brand))->assertRedirect();

    expect($product->fresh())->not->toBeNull()
        ->category_id->toBeNull()
        ->brand_id->toBeNull();
});

test('a vendor cannot rename or delete another store\'s category or brand', function () {
    $other = openStore(User::factory()->create(), 'Other Tea');
    $category = Category::query()->create(['store_id' => $other->id, 'name' => 'Leaves', 'slug' => 'leaves']);
    $brand = Brand::query()->create(['store_id' => $other->id, 'name' => 'Leaf Co', 'slug' => 'leaf-co']);
    $intruder = User::factory()->create();
    openStore($intruder, 'Intruder Tea');

    $this->actingAs($intruder)->put(route('categories.update', $category), ['name' => 'Mine'])->assertForbidden();
    $this->actingAs($intruder)->delete(route('categories.destroy', $category))->assertForbidden();
    $this->actingAs($intruder)->put(route('brands.update', $brand), ['name' => 'Mine'])->assertForbidden();
    $this->actingAs($intruder)->delete(route('brands.destroy', $brand))->assertForbidden();

    expect($category->fresh()->name)->toBe('Leaves')
        ->and($brand->fresh()->name)->toBe('Leaf Co');
});

test('a category name is unique within a store, ignoring case', function () {
    $vendor = User::factory()->create();
    $store = openStore($vendor, 'Smile Tea');
    Category::query()->create(['store_id' => $store->id, 'name' => 'Green', 'slug' => 'green']);

    $this->actingAs($vendor)->post(route('categories.store'), ['name' => 'green'])->assertInvalid(['name']);

    expect($store->categories()->count())->toBe(1);
});

test('products with khmer or repeated names still get working links', function () {
    Storage::fake('public');
    $vendor = User::factory()->create();
    $store = openStore($vendor, 'Smile Tea');

    $this->actingAs($vendor)->post(route('products.store'), ['name' => 'តែបៃតង', 'price' => '1.00'])->assertRedirect();
    $this->actingAs($vendor)->post(route('products.store'), ['name' => 'Jasmine', 'price' => '1.00'])->assertRedirect();
    $this->actingAs($vendor)->post(route('products.store'), ['name' => 'Jasmine', 'price' => '1.00'])->assertRedirect();

    $slugs = $store->products()->orderBy('id')->pluck('slug')->all();

    expect($slugs[0])->toStartWith('product-')
        ->and(array_slice($slugs, 1))->toBe(['jasmine', 'jasmine-2']);
});

test('saving a new product opens its edit page and renaming keeps its link', function () {
    $vendor = User::factory()->create();
    $store = openStore($vendor, 'Smile Tea');

    $this->actingAs($vendor)->post(route('products.store'), [
        'name' => 'Jasmine',
        'price' => '2.50',
        'stock' => 4,
        'description' => 'Floral',
    ])->assertRedirect(route('vendor.products.edit', $product = $store->products()->sole()));

    $this->actingAs($vendor)
        ->get(route('vendor.products.edit', $product))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('vendor/product-form')->where('product.price', '2.50'));

    $this->actingAs($vendor)->put(route('products.update', $product), [
        'name' => 'Jasmine pearls',
        'price' => '3.00',
        'stock' => '',
        'description' => '',
    ])->assertRedirect(route('vendor.products.edit', $product));

    expect($product->fresh())
        ->name->toBe('Jasmine pearls')
        ->slug->toBe('jasmine')
        ->stock->toBeNull()
        ->description->toBeNull();

    $this->actingAs($vendor)->post(route('products.publish', $product))->assertRedirect();
    $this->get(route('stores.products.show', ['store' => $store, 'productSlug' => 'jasmine']))->assertOk();
});

test('deleting a product or a photo removes the files', function () {
    Storage::fake('public');
    $vendor = User::factory()->create();
    $store = openStore($vendor, 'Smile Tea');

    $this->actingAs($vendor)->post(route('products.store'), [
        'name' => 'Jasmine',
        'price' => '2.50',
        'images' => [UploadedFile::fake()->image('one.jpg'), UploadedFile::fake()->image('two.png')],
    ])->assertRedirect();

    $product = $store->products()->sole();
    [$first, $second] = $product->images()->orderBy('sort')->get()->all();

    $this->actingAs($vendor)->post(route('products.images.cover', [$product, $second]))->assertRedirect();
    expect($product->images()->orderBy('sort')->first()->id)->toBe($second->id);

    $this->actingAs($vendor)->delete(route('products.images.destroy', [$product, $first]))->assertRedirect();
    Storage::disk('public')->assertMissing($first->path);
    Storage::disk('public')->assertExists($second->path);

    $this->actingAs($vendor)->delete(route('products.destroy', $product))->assertRedirect(route('vendor.products'));

    expect(Product::query()->count())->toBe(0);
    Storage::disk('public')->assertMissing($second->path);
});

test('a photo can only be removed through its own product', function () {
    Storage::fake('public');
    $vendor = User::factory()->create();
    $store = openStore($vendor, 'Smile Tea');
    $mine = Product::factory()->for($store)->create();
    $photo = $mine->images()->create(['path' => UploadedFile::fake()->image('a.jpg')->store('products', 'public'), 'sort' => 1]);

    $other = Product::factory()->for(openStore(User::factory()->create(), 'Other Tea'))->create();

    $this->actingAs($vendor)->delete(route('products.images.destroy', [$other, $photo]))->assertNotFound();

    Storage::disk('public')->assertExists($photo->path);
});

test('product photos must be small jpeg, png, or webp images', function (UploadedFile $file) {
    Storage::fake('public');
    $vendor = User::factory()->create();
    openStore($vendor, 'Smile Tea');

    $this->actingAs($vendor)->post(route('products.store'), [
        'name' => 'Jasmine',
        'price' => '2.50',
        'images' => [$file],
    ])->assertInvalid(['images.0']);

    expect(Product::query()->count())->toBe(0);
})->with([
    'a gif' => fn () => UploadedFile::fake()->image('tea.gif'),
    'a pdf' => fn () => UploadedFile::fake()->create('menu.pdf', 10, 'application/pdf'),
    'a large photo' => fn () => UploadedFile::fake()->image('big.jpg')->size(3000),
]);

test('uploaded photos are stored under a generated name', function () {
    Storage::fake('public');
    $vendor = User::factory()->create();
    $store = openStore($vendor, 'Smile Tea');

    $this->actingAs($vendor)->post(route('products.store'), [
        'name' => 'Jasmine',
        'price' => '2.50',
        'images' => [UploadedFile::fake()->image('../../evil name.jpg')],
    ])->assertRedirect();

    $path = $store->products()->sole()->images()->sole()->path;

    expect($path)->toStartWith('products/')->not->toContain('evil');
});

test('deleting an account removes its store photos', function () {
    Storage::fake('public');
    $vendor = User::factory()->create(['password' => null]);
    $store = openStore($vendor, 'Smile Tea');

    $this->actingAs($vendor)->post(route('products.store'), [
        'name' => 'Jasmine',
        'price' => '2.50',
        'images' => [UploadedFile::fake()->image('one.jpg')],
    ])->assertRedirect();

    $path = $store->products()->sole()->images()->sole()->path;

    $this->actingAs($vendor)->delete(route('profile.destroy'))->assertRedirect('/');

    Storage::disk('public')->assertMissing($path);
});
