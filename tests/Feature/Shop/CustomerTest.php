<?php

use App\Enums\ProductStatus;
use App\Models\Cart;
use App\Models\Inquiry;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Http;

test('a guest can add to the cart and must sign in before buying', function () {
    $vendor = User::factory()->create();
    $store = openStore($vendor, 'Smile Tea');
    $product = Product::query()->create([
        'store_id' => $store->id,
        'name' => 'Jasmine',
        'slug' => 'jasmine',
        'price_cents' => 250,
        'status' => ProductStatus::Published,
    ]);

    $this->post(route('cart.store', [$store, $product]))->assertRedirect();
    expect(Cart::query()->count())->toBe(0);

    $this->get(route('stores.products.show', ['store' => $store, 'productSlug' => 'jasmine']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('cart.count', 1)->where('authenticated', false));

    $this->post(route('inquiries.product', [$store, $product]))->assertRedirect(route('login'));
    expect(Inquiry::query()->count())->toBe(0);

    $this->get(route('auth.sign-in', ['next' => '/s/'.$store->slug.'/p/jasmine']))
        ->assertOk();

    expect(session('url.intended'))->toBe(url('/s/'.$store->slug.'/p/jasmine'));
});

test('a signed-in customer can buy one product', function () {
    Http::fake();
    config(['services.telegram.bot_token' => '123:ABC', 'services.telegram.admin_chat_id' => '9']);

    $vendor = User::factory()->create();
    $customer = User::factory()->create();
    $store = openStore($vendor, 'Smile Tea');
    config(['services.telegram.bot_token' => '123:ABC', 'services.telegram.admin_chat_id' => '9']);
    $product = Product::query()->create([
        'store_id' => $store->id,
        'name' => 'Jasmine',
        'slug' => 'jasmine',
        'price_cents' => 250,
        'status' => ProductStatus::Published,
    ]);

    $this->actingAs($customer)
        ->post(route('inquiries.product', [$store, $product]))
        ->assertRedirect();

    expect(Inquiry::query()->count())->toBe(1);
});
