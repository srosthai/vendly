<?php

use App\Enums\ProductStatus;
use App\Jobs\SendTelegramMessage;
use App\Models\InquiryItem;
use App\Models\Product;
use App\Models\User;
use App\Services\Telegram\TelegramClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
    config([
        'services.telegram.bot_username' => 'VendlyBot',
        'services.telegram.webhook_secret' => 'tg-secret',
    ]);

    $vendor = User::factory()->create();
    $store = openStore($vendor, 'Smile Tea');

    $url = $this->actingAs($vendor)
        ->postJson(route('telegram.link'))
        ->assertOk()
        ->json('url');

    expect($url)->toContain('start=link_');
    $token = (string) str($url)->after('start=link_');

    $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'tg-secret')->postJson(route('webhooks.telegram'), [
        'message' => [
            'text' => '/start link_'.$token,
            'chat' => ['id' => 55],
        ],
    ])->assertNoContent();

    expect($store->fresh()->telegram_chat_id)->toBe('55');

    $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'tg-secret')->postJson(route('webhooks.telegram'), [
        'message' => [
            'text' => '/start link_'.$token,
            'chat' => ['id' => 99],
        ],
    ])->assertNoContent();

    expect($store->fresh()->telegram_chat_id)->toBe('55');
});

test('the telegram webhook is refused without the configured secret', function (?string $configured, ?string $sent) {
    config(['services.telegram.webhook_secret' => $configured]);

    $vendor = User::factory()->create();
    $store = openStore($vendor, 'Secret Tea');

    $token = (string) str(
        $this->actingAs($vendor)->postJson(route('telegram.link'))->json('url')
    )->after('start=link_');

    $request = $sent === null ? $this : $this->withHeader('X-Telegram-Bot-Api-Secret-Token', $sent);

    $request->postJson(route('webhooks.telegram'), [
        'message' => ['text' => '/start link_'.$token, 'chat' => ['id' => 55]],
    ])->assertUnauthorized();

    expect($store->fresh()->telegram_chat_id)->toBeNull();
})->with([
    'no secret configured' => [null, null],
    'an empty secret configured and sent' => ['', ''],
    'a wrong secret' => ['tg-secret', 'guess'],
    'a missing header' => ['tg-secret', null],
]);

test('a telegram connection error never carries the bot token', function () {
    config(['services.telegram.bot_token' => '123456:SECRET-TOKEN']);
    Log::spy();
    Http::fake(fn () => throw new ConnectionException(
        'cURL error 28: timed out for https://api.telegram.org/bot123456:SECRET-TOKEN/sendMessage',
    ));

    $job = new SendTelegramMessage('55', 'Hello');

    try {
        $job->handle(app(TelegramClient::class));
        $this->fail('The connection error was swallowed.');
    } catch (ConnectionException $exception) {
        expect($exception->getMessage())->not->toContain('SECRET-TOKEN')
            ->and($exception->getPrevious())->toBeNull();

        $job->failed($exception);
    }

    Log::shouldHaveReceived('error')->withArgs(
        fn (string $message, array $context): bool => ! str_contains(json_encode($context), 'SECRET-TOKEN'),
    );
});
