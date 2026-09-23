<?php

use App\Enums\ProductStatus;
use App\Jobs\SendTelegramMessage;
use App\Models\Inquiry;
use App\Models\InquiryItem;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Services\Telegram\TelegramClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

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

function undeliveredInquiry(Store $store, string $name = 'Ada'): Inquiry
{
    $inquiry = Inquiry::query()->create([
        'public_id' => (string) Str::ulid(),
        'number' => (int) Inquiry::query()->where('store_id', $store->id)->max('number') + 1,
        'store_id' => $store->id,
        'customer_name' => $name,
    ]);
    $inquiry->items()->create(['name' => 'Jasmine', 'price_cents' => 250, 'quantity' => 2]);

    return $inquiry;
}

test('requests are numbered per store and the number is in the message', function () {
    $tea = openStore(User::factory()->create(), 'Smile Tea');
    $cake = openStore(User::factory()->create(), 'Cake Corner');
    Queue::fake();
    config(['services.telegram.admin_chat_id' => '9']);
    $customer = User::factory()->create();

    $this->actingAs($customer)->post(route('inquiries.product', [$tea, Product::factory()->for($tea)->create()]));
    $this->actingAs($customer)->post(route('inquiries.product', [$tea, Product::factory()->for($tea)->create()]));
    $this->actingAs($customer)->post(route('inquiries.product', [$cake, Product::factory()->for($cake)->create()]));

    expect(Inquiry::query()->orderBy('id')->pluck('number')->all())->toBe([1, 2, 1]);
    Queue::assertPushed(SendTelegramMessage::class, fn (SendTelegramMessage $job): bool => str_starts_with($job->text, 'New request #2 — Smile Tea'));
});

test('a permanent telegram error fails at once and is recorded', function (int $status) {
    config(['services.telegram.bot_token' => '123:ABC']);
    $inquiry = undeliveredInquiry(openStore(User::factory()->create(), 'Smile Tea'));
    config(['services.telegram.bot_token' => '123:ABC']);
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Bad Request: chat not found'], $status)]);

    $job = (new SendTelegramMessage('55', 'Hello', $inquiry->id, 'admin'))->withFakeQueueInteractions();
    $job->handle(app(TelegramClient::class));

    $job->assertFailed();
    expect($inquiry->fresh())
        ->admin_notified_at->toBeNull()
        ->admin_error->toBe('Bad Request: chat not found');
})->with([400, 403]);

test('a telegram rate limit waits as long as telegram asks', function () {
    $inquiry = undeliveredInquiry(openStore(User::factory()->create(), 'Smile Tea'));
    config(['services.telegram.bot_token' => '123:ABC']);
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => false, 'parameters' => ['retry_after' => 7]], 429)]);

    $job = (new SendTelegramMessage('55', 'Hello', $inquiry->id, 'vendor'))->withFakeQueueInteractions();
    $job->handle(app(TelegramClient::class));

    $job->assertReleased(7);
    expect($inquiry->fresh()->vendor_error)->toBeNull();
});

test('a delivered message clears an old error', function () {
    $inquiry = undeliveredInquiry(openStore(User::factory()->create(), 'Smile Tea'));
    $inquiry->update(['admin_error' => 'Bad Request: chat not found']);
    config(['services.telegram.bot_token' => '123:ABC']);
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

    (new SendTelegramMessage('55', 'Hello', $inquiry->id, 'admin'))->handle(app(TelegramClient::class));

    expect($inquiry->fresh())
        ->admin_notified_at->not->toBeNull()
        ->admin_error->toBeNull();
});

test('an admin sees undelivered requests and sends them again only where they are missing', function () {
    $store = openStore(User::factory()->create(), 'Smile Tea');
    $store->update(['telegram_chat_id' => '77']);
    $delivered = undeliveredInquiry($store, 'Grace');
    $delivered->update(['admin_notified_at' => now(), 'vendor_notified_at' => now()]);
    $missing = undeliveredInquiry($store, 'Ada');
    $missing->update(['admin_notified_at' => now(), 'vendor_error' => 'Forbidden: bot was blocked by the user']);
    $admin = User::factory()->admin()->create();
    Queue::fake();

    $this->actingAs($admin)
        ->get(route('admin.requests', ['filter' => 'undelivered']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/requests')
            ->has('inquiries.data', 1)
            ->where('inquiries.data.0.reference', '#2')
            ->where('inquiries.data.0.vendor.error', 'Forbidden: bot was blocked by the user')
            ->where('inquiries.data.0.can_retry', true));

    $this->actingAs($admin)->post(route('admin.requests.retry', $missing))->assertRedirect();

    Queue::assertPushed(SendTelegramMessage::class, 1);
    Queue::assertPushed(SendTelegramMessage::class, fn (SendTelegramMessage $job): bool => $job->destination === 'vendor' && $job->chatId === '77');
    expect($missing->fresh()->vendor_error)->toBeNull();

    $this->actingAs($admin)->post(route('admin.requests.retry', $delivered))->assertRedirect();
    Queue::assertPushed(SendTelegramMessage::class, 1);
});

test('only an admin can see or resend requests', function () {
    $inquiry = undeliveredInquiry(openStore(User::factory()->create(), 'Smile Tea'));
    $vendor = User::factory()->create();

    $this->actingAs($vendor)->get(route('admin.requests'))->assertForbidden();
    $this->actingAs($vendor)->post(route('admin.requests.retry', $inquiry))->assertForbidden();
});

test('the bot confirms when a vendor connects their chat', function () {
    config(['services.telegram.bot_username' => 'VendlyBot', 'services.telegram.webhook_secret' => 'tg-secret']);
    $vendor = User::factory()->create();
    openStore($vendor, 'Smile Tea');
    Queue::fake();

    $token = (string) str($this->actingAs($vendor)->postJson(route('telegram.link'))->json('url'))->after('start=link_');

    $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'tg-secret')
        ->postJson(route('webhooks.telegram'), ['message' => ['text' => '/start link_'.$token, 'chat' => ['id' => 55]]])
        ->assertNoContent();

    Queue::assertPushed(SendTelegramMessage::class, fn (SendTelegramMessage $job): bool => $job->chatId === '55' && str_starts_with($job->text, 'Connected to Smile Tea'));
});
