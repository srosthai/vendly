<?php

use App\Enums\ProductStatus;
use App\Models\InquiryItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

test('an inquiry keeps the price from the moment it was sent', function () {
    Http::preventStrayRequests();

    $vendor = User::factory()->create();
    $customer = User::factory()->create(['name' => 'Ada']);
    $store = openStore($vendor, 'Smile Tea');

    Http::fake([
        'api.telegram.org/*' => Http::response(['ok' => true]),
    ]);
    config([
        'services.telegram.bot_token' => '123:ABC',
        'services.telegram.admin_chat_id' => '100',
    ]);
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

    $product->update(['price_cents' => 999]);

    expect(InquiryItem::query()->first()?->price_cents)->toBe(250);

    Http::assertSent(function (Request $request): bool {
        return str_contains((string) $request['text'], 'Jasmine')
            && str_contains((string) $request['text'], '$2.50')
            && ! str_contains((string) $request['text'], '$9.99')
            && (string) $request['chat_id'] === '100';
    });
});

test('a cart send is one message that lists every product', function () {
    Http::preventStrayRequests();

    $vendor = User::factory()->create();
    $customer = User::factory()->create();
    $store = openStore($vendor, 'Smile Tea');

    Http::fake([
        'api.telegram.org/*' => Http::response(['ok' => true]),
    ]);
    config([
        'services.telegram.bot_token' => '123:ABC',
        'services.telegram.admin_chat_id' => '100',
    ]);

    $tea = Product::query()->create([
        'store_id' => $store->id,
        'name' => 'Jasmine',
        'slug' => 'jasmine',
        'price_cents' => 250,
        'status' => ProductStatus::Published,
    ]);
    $cake = Product::query()->create([
        'store_id' => $store->id,
        'name' => 'Cake',
        'slug' => 'cake',
        'price_cents' => 100,
        'status' => ProductStatus::Published,
    ]);

    $this->actingAs($customer)
        ->post(route('cart.store', [$store, $tea]))
        ->assertRedirect();
    $this->actingAs($customer)
        ->post(route('cart.store', [$store, $cake]))
        ->assertRedirect();
    $this->actingAs($customer)
        ->post(route('inquiries.cart', $store))
        ->assertRedirect();

    Http::assertSentCount(1);
    Http::assertSent(function (Request $request): bool {
        $text = (string) $request['text'];

        return str_contains($text, 'Jasmine') && str_contains($text, 'Cake');
    });
});

test('telegram sends nothing when the bot is not configured', function () {
    Http::preventStrayRequests();
    config(['services.telegram.bot_token' => null]);

    $vendor = User::factory()->create();
    $customer = User::factory()->create();
    $store = openStore($vendor, 'Quiet Tea');
    $product = Product::query()->create([
        'store_id' => $store->id,
        'name' => 'Quiet',
        'slug' => 'quiet',
        'price_cents' => 100,
        'status' => ProductStatus::Published,
    ]);

    $this->actingAs($customer)
        ->post(route('inquiries.product', [$store, $product]))
        ->assertRedirect();

    Http::assertNothingSent();
});

test('a telegram link token cannot be reused', function () {
    config(['services.telegram.bot_username' => 'VendlyBot']);

    $vendor = User::factory()->create();
    $store = openStore($vendor, 'Smile Tea');

    $url = $this->actingAs($vendor)
        ->postJson(route('telegram.link'))
        ->assertOk()
        ->json('url');

    expect($url)->toContain('start=link_');
    $token = (string) str($url)->after('start=link_');

    $this->postJson(route('webhooks.telegram'), [
        'message' => [
            'text' => '/start link_'.$token,
            'chat' => ['id' => 55],
        ],
    ])->assertNoContent();

    expect($store->fresh()->telegram_chat_id)->toBe('55');

    $this->postJson(route('webhooks.telegram'), [
        'message' => [
            'text' => '/start link_'.$token,
            'chat' => ['id' => 99],
        ],
    ])->assertNoContent();

    expect($store->fresh()->telegram_chat_id)->toBe('55');
});
