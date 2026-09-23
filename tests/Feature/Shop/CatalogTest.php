<?php

use App\Enums\ProductStatus;
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
