<?php

use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'services.telegram.webhook_secret' => 'tg-secret',
        'services.telegram.bot_username' => 'VendlyBot',
    ]);
});

test('connecting remembers the chat name and when it connected', function () {
    $this->freezeSecond();
    $vendor = User::factory()->create();
    $store = openStore($vendor, 'Smile Tea');

    $token = (string) str($this->actingAs($vendor)->postJson(route('telegram.link'))->json('url'))->after('start=link_');

    $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'tg-secret')->postJson(route('webhooks.telegram'), [
        'message' => [
            'text' => '/start link_'.$token,
            'chat' => ['id' => 77, 'first_name' => 'Sokha', 'last_name' => 'Chan'],
        ],
    ])->assertNoContent();

    expect($store->fresh())
        ->telegram_chat_id->toBe('77')
        ->telegram_chat_name->toBe('Sokha Chan')
        ->telegram_connected_at->toEqual(now());

    $this->actingAs($vendor)->get(route('vendor.telegram'))
        ->assertInertia(fn ($page) => $page
            ->where('connected', true)
            ->where('chat.name', 'Sokha Chan')
            ->where('bot', '@VendlyBot'));
});

test('the test message goes to the vendor chat', function () {
    Http::preventStrayRequests();
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
    $vendor = User::factory()->create();
    $store = openStore($vendor, 'Smile Tea');
    config(['services.telegram.bot_token' => 'bot-token']);
    $store->update(['telegram_chat_id' => '77']);

    $this->actingAs($vendor)->post(route('telegram.test'))
        ->assertRedirect()
        ->assertSessionHas('telegram_test', ['type' => 'success', 'message' => 'Test message sent. Check your Telegram chat.']);

    Http::assertSent(fn (Request $request): bool => $request['chat_id'] === '77' && str_contains($request['text'], 'Smile Tea'));
});

test('the test explains a blocked bot in plain words', function () {
    Http::preventStrayRequests();
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'Forbidden: bot was blocked by the user'], 403)]);
    $vendor = User::factory()->create();
    $store = openStore($vendor, 'Smile Tea');
    config(['services.telegram.bot_token' => 'bot-token']);
    $store->update(['telegram_chat_id' => '77']);

    $this->actingAs($vendor)->post(route('telegram.test'))
        ->assertSessionHas('telegram_test', fn (array $result): bool => $result['type'] === 'error'
            && str_contains($result['message'], 'You blocked the Vendly bot'));
});

test('the test needs a connected chat and sends nothing otherwise', function () {
    Http::preventStrayRequests();
    $vendor = User::factory()->create();
    openStore($vendor, 'Smile Tea');
    config(['services.telegram.bot_token' => 'bot-token']);

    $this->actingAs($vendor)->post(route('telegram.test'))
        ->assertSessionHas('telegram_test', ['type' => 'error', 'message' => 'Connect your Telegram chat first.']);

    Http::assertNothingSent();
});

test('someone without a store cannot send a test', function () {
    Http::preventStrayRequests();

    $this->actingAs(User::factory()->create())->post(route('telegram.test'))
        ->assertForbidden();

    Http::assertNothingSent();
});
