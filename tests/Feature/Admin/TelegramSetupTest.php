<?php

use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

const TEST_BOT_TOKEN = '123456789:TestTokenForVendlyOnly_abcdefghijklmnop';

beforeEach(function () {
    config([
        'app.url' => 'https://vendly.example',
        'services.telegram.bot_token' => null,
        'services.telegram.bot_username' => null,
        'services.telegram.webhook_secret' => null,
    ]);
    Http::preventStrayRequests();
});

test('saving a token checks it, fills in the username, and registers the webhook', function () {
    Http::fake([
        'api.telegram.org/*/getMe' => Http::response(['ok' => true, 'result' => ['id' => 1, 'username' => 'vendlyplatform_bot', 'first_name' => 'Vendly']]),
        'api.telegram.org/*/setWebhook' => Http::response(['ok' => true, 'result' => true]),
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.telegram.update'), [
            'bot_token' => TEST_BOT_TOKEN,
            'admin_chat_id' => '5283073511',
            'mini_app_short_name' => 'shop',
        ])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('telegram_setup', fn (array $result): bool => $result['type'] === 'success');

    $settings = PlatformSetting::current();
    expect($settings->botToken())->toBe(TEST_BOT_TOKEN)
        ->and($settings->botUsername())->toBe('vendlyplatform_bot')
        ->and($settings->telegramWebhookSecret())->toHaveLength(48)
        ->and($settings->admin_chat_id)->toBe('5283073511')
        ->and($settings->miniAppLink('smile-tea'))->toBe('https://t.me/vendlyplatform_bot/shop?startapp=smile-tea')
        ->and(DB::table('platform_settings')->value('telegram_bot_token'))->not->toContain(TEST_BOT_TOKEN);

    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/setWebhook')
        && $request['url'] === 'https://vendly.example/webhooks/telegram'
        && $request['secret_token'] === $settings->telegramWebhookSecret()
        && $request['allowed_updates'] === ['message']);
});

test('the registered webhook accepts Telegram with the generated secret', function () {
    Http::fake([
        'api.telegram.org/*/getMe' => Http::response(['ok' => true, 'result' => ['id' => 1, 'username' => 'vendlyplatform_bot']]),
        'api.telegram.org/*/setWebhook' => Http::response(['ok' => true, 'result' => true]),
    ]);
    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.telegram.update'), ['bot_token' => TEST_BOT_TOKEN]);
    auth()->logout();

    $secret = PlatformSetting::current()->telegramWebhookSecret();
    $message = ['message' => ['text' => 'hello', 'chat' => ['id' => 1]]];

    $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'wrong')->postJson(route('webhooks.telegram'), $message)->assertUnauthorized();
    $this->withHeader('X-Telegram-Bot-Api-Secret-Token', $secret)->postJson(route('webhooks.telegram'), $message)->assertNoContent();
});

test('a token Telegram rejects is not saved', function () {
    Http::fake(['api.telegram.org/*/getMe' => Http::response(['ok' => false, 'description' => 'Unauthorized'], 401)]);

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.telegram.update'), ['bot_token' => TEST_BOT_TOKEN])
        ->assertInvalid(['bot_token' => 'Telegram did not accept this token']);

    expect(PlatformSetting::current()->botToken())->toBe('');
    Http::assertNotSent(fn (Request $request): bool => str_ends_with($request->url(), '/setWebhook'));
});

test('telegram settings are validated and admin only', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.telegram.update'), ['bot_token' => 'not a token', 'admin_chat_id' => 'me', 'mini_app_short_name' => 'my app'])
        ->assertInvalid(['bot_token', 'admin_chat_id', 'mini_app_short_name']);

    $this->actingAs(User::factory()->create())
        ->put(route('admin.telegram.update'), ['admin_chat_id' => '1'])
        ->assertForbidden();
    $this->actingAs(User::factory()->create())
        ->post(route('admin.telegram.webhook'))
        ->assertForbidden();
});

test('the page never sends the token and reports the live webhook', function () {
    PlatformSetting::current()->update(['telegram_bot_token' => TEST_BOT_TOKEN, 'telegram_webhook_secret' => 'secret-value', 'bot_username' => '@vendlyplatform_bot']);
    Http::fake(['api.telegram.org/*/getWebhookInfo' => Http::response(['ok' => true, 'result' => [
        'url' => 'https://vendly.example/webhooks/telegram',
        'pending_update_count' => 2,
        'last_error_message' => 'Connection timed out',
    ]])]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.telegram'))
        ->assertOk()
        ->assertDontSee(TEST_BOT_TOKEN)
        ->assertDontSee('secret-value')
        ->assertInertia(fn ($page) => $page
            ->where('settings.bot_username', 'vendlyplatform_bot')
            ->where('token', ['source' => 'admin', 'ends_with' => 'mnop'])
            ->where('webhookUrl', 'https://vendly.example/webhooks/telegram')
            ->loadDeferredProps(fn ($reload) => $reload->where('webhook', [
                'url' => 'https://vendly.example/webhooks/telegram',
                'matches' => true,
                'pending' => 2,
                'last_error' => 'Connection timed out',
                'reachable' => true,
            ])));
});

test('register again refuses a site without https', function () {
    config(['app.url' => 'http://localhost:8000']);
    PlatformSetting::current()->update(['telegram_bot_token' => TEST_BOT_TOKEN]);

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.telegram.webhook'))
        ->assertSessionHas('telegram_setup', fn (array $result): bool => $result['type'] === 'error' && str_contains($result['message'], 'https'));

    Http::assertNothingSent();
});
