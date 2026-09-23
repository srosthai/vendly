<?php

use App\Models\PlatformSetting;
use App\Models\User;
use App\Services\Cutluy\CutluyClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    config([
        'services.cutluy.key' => 'ck_env_key',
        'services.cutluy.webhook_secret' => 'whsec_env',
        'services.cutluy.base_url' => 'https://cutluy.com',
    ]);
});

test('only an admin can change the cutluy settings', function () {
    $this->actingAs(User::factory()->create())
        ->put(route('admin.site.cutluy.update'), ['api_key' => 'ck_hijack_key'])
        ->assertForbidden();

    expect(PlatformSetting::current()->cutluy_api_key)->toBeNull();
});

test('a saved cutluy key is encrypted, never sent to the page, and kept when the field is empty', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.site.cutluy.update'), ['api_key' => 'ck_live_secret_1234', 'webhook_secret' => 'whsec_saved_5678'])
        ->assertSessionHasNoErrors();

    $raw = DB::table('platform_settings')->value('cutluy_api_key');
    expect($raw)->not->toContain('ck_live_secret_1234')
        ->and(PlatformSetting::current()->cutluyApiKey())->toBe('ck_live_secret_1234');

    $this->actingAs($admin)->get(route('admin.site'))
        ->assertInertia(fn ($page) => $page
            ->where('cutluy.api_key', ['source' => 'admin', 'ends_with' => '1234'])
            ->where('cutluy.webhook_secret', ['source' => 'admin', 'ends_with' => '5678'])
            ->where('cutluy.webhook_url', route('webhooks.cutluy')))
        ->assertDontSee('ck_live_secret_1234')
        ->assertDontSee('whsec_saved_5678');

    $this->actingAs($admin)
        ->put(route('admin.site.cutluy.update'), ['api_key' => '', 'webhook_secret' => ''])
        ->assertSessionHasNoErrors();

    expect(PlatformSetting::current()->cutluyApiKey())->toBe('ck_live_secret_1234');
});

test('clearing a saved cutluy key falls back to the environment', function () {
    $admin = User::factory()->admin()->create();
    PlatformSetting::current()->update(['cutluy_api_key' => 'ck_saved_value']);

    $this->actingAs($admin)
        ->put(route('admin.site.cutluy.update'), ['clear_api_key' => '1'])
        ->assertSessionHasNoErrors();

    expect(PlatformSetting::current()->cutluyApiKey())->toBe('ck_env_key');

    $this->actingAs($admin)->get(route('admin.site'))
        ->assertInertia(fn ($page) => $page->where('cutluy.api_key', ['source' => 'env', 'ends_with' => '_key']));
});

test('cutluy settings are validated', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.site.cutluy.update'), ['api_key' => 'has spaces in it', 'base_url' => 'http://cutluy.com'])
        ->assertInvalid(['api_key', 'base_url']);
});

test('the cutluy client uses the saved key and address', function () {
    Http::preventStrayRequests();
    Http::fake(['cutluy.example/*' => Http::response(['id' => 'pay_1', 'status' => 'pending', 'checkout_url' => 'https://cutluy.example/pay/pay_1', 'qr_string' => '0002'], 201)]);
    PlatformSetting::current()->update(['cutluy_api_key' => 'ck_admin_key', 'cutluy_base_url' => 'https://cutluy.example']);

    app(CutluyClient::class)->createPayment(5.0, 'subpay_ref', [], 'subpay_ref');

    Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), 'https://cutluy.example/')
        && $request->hasHeader('Authorization', 'Bearer ck_admin_key'));
});

test('webhooks are verified with the saved secret', function () {
    Queue::fake();
    PlatformSetting::current()->update(['cutluy_webhook_secret' => 'whsec_admin']);

    $raw = json_encode(['id' => 'evt_1', 'type' => 'payment.completed', 'data' => ['payment' => ['id' => 'pay_1']]], JSON_THROW_ON_ERROR);
    $sign = fn (string $secret): string => 't='.now()->getTimestamp().',v1='.hash_hmac('sha256', now()->getTimestamp().'.'.$raw, $secret);
    $call = fn (string $signature) => $this->call('POST', route('webhooks.cutluy'), [], [], [], [
        'HTTP_X_CUTLUY_SIGNATURE' => $signature,
        'HTTP_X_CUTLUY_EVENT' => 'payment.completed',
        'CONTENT_TYPE' => 'application/json',
    ], $raw);

    $call($sign('whsec_env'))->assertUnauthorized();
    $call($sign('whsec_admin'))->assertNoContent();
});
