<?php

use App\Jobs\SendTelegramMessage;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

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

test('the link offers the vendor chat and a group, with one code', function () {
    $vendor = User::factory()->create();
    openStore($vendor, 'Smile Tea');

    $links = $this->actingAs($vendor)->postJson(route('telegram.link'))->assertOk()->json();

    expect($links['url'])->toStartWith('https://t.me/VendlyBot?start=link_')
        ->and($links['group_url'])->toStartWith('https://t.me/VendlyBot?startgroup=link_')
        ->and(str($links['url'])->after('link_')->toString())->toBe(str($links['group_url'])->after('link_')->toString());
});

test('a group connects with the command that names the bot', function () {
    $vendor = User::factory()->create();
    $store = openStore($vendor, 'Smile Tea');
    $store->update(['telegram_chat_id' => '77', 'telegram_chat_name' => 'Sokha']);
    $token = (string) str($this->actingAs($vendor)->postJson(route('telegram.link'))->json('group_url'))->after('startgroup=link_');

    $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'tg-secret')->postJson(route('webhooks.telegram'), [
        'message' => [
            'text' => '/start@VendlyBot link_'.$token,
            'chat' => ['id' => -100200300, 'type' => 'supergroup', 'title' => 'Smile Tea orders'],
        ],
    ])->assertNoContent();

    expect($store->fresh())
        ->telegram_chat_id->toBe('-100200300')
        ->telegram_chat_name->toBe('Smile Tea orders');

    $this->actingAs($vendor)->get(route('vendor.telegram'))
        ->assertInertia(fn ($page) => $page->where('chat.group', true)->where('chat.name', 'Smile Tea orders'));
});

test('a group upgraded to a supergroup stays connected', function () {
    $store = openStore(User::factory()->create(), 'Smile Tea');
    $store->update(['telegram_chat_id' => '-4001']);

    $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'tg-secret')->postJson(route('webhooks.telegram'), [
        'message' => ['chat' => ['id' => -4001, 'type' => 'group'], 'migrate_to_chat_id' => -1004001],
    ])->assertNoContent();

    expect($store->fresh()->telegram_chat_id)->toBe('-1004001');
});

test('a vendor disconnects and the old chat is told', function () {
    Queue::fake();
    $vendor = User::factory()->create();
    $store = openStore($vendor, 'Smile Tea');
    $store->update(['telegram_chat_id' => '77', 'telegram_chat_name' => 'Sokha', 'telegram_connected_at' => now()]);

    $this->actingAs($vendor)->delete(route('telegram.unlink'))->assertRedirect();

    expect($store->fresh())
        ->telegram_chat_id->toBeNull()
        ->telegram_chat_name->toBeNull()
        ->telegram_connected_at->toBeNull();
    Queue::assertPushed(SendTelegramMessage::class, fn (SendTelegramMessage $job): bool => $job->chatId === '77' && str_contains($job->text, 'no longer receives'));

    $this->actingAs($vendor)->get(route('vendor.telegram'))->assertInertia(fn ($page) => $page->where('connected', false));
});

test('someone without a store cannot disconnect', function () {
    $this->actingAs(User::factory()->create())->delete(route('telegram.unlink'))->assertForbidden();
});
