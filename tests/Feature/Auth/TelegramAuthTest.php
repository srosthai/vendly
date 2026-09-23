<?php

use App\Models\Inquiry;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

test('a bad telegram hash does not create a user', function () {
    Http::preventStrayRequests();

    $this->post(route('auth.telegram.store'), [
        'init_data' => telegramInitData(['id' => 42, 'first_name' => 'Ada'], hash: 'deadbeef'),
    ])->assertInvalid(['init_data']);

    expect(User::query()->count())->toBe(0);
});

test('stale telegram sign-in data is rejected', function () {
    Http::preventStrayRequests();

    $this->post(route('auth.telegram.store'), [
        'init_data' => telegramInitData(
            ['id' => 42, 'first_name' => 'Ada'],
            now()->subDays(2)->getTimestamp(),
        ),
    ])->assertInvalid(['init_data']);

    expect(User::query()->count())->toBe(0);
});

test('valid telegram sign-in creates a customer without an email', function () {
    Http::preventStrayRequests();

    $this->post(route('auth.telegram.store'), [
        'init_data' => telegramInitData([
            'id' => 42,
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'username' => 'ada',
        ]),
    ])->assertRedirect(route('dashboard'));

    $user = User::query()->first();

    expect($user)->not->toBeNull()
        ->and($user->email)->toBeNull()
        ->and($user->telegram_id)->toBe('42')
        ->and($user->telegram_username)->toBe('ada')
        ->and($user->name)->toBe('Ada Lovelace')
        ->and($user->is_admin)->toBeFalse();

    $this->assertAuthenticatedAs($user);
    $this->get(route('dashboard'))->assertOk();
});

test('telegram sign-in data older than the configured window or from the future is rejected', function (int $offsetSeconds) {
    config(['services.telegram.init_data_max_age' => 3600]);

    $this->post(route('auth.telegram.store'), [
        'init_data' => telegramInitData(['id' => 42, 'first_name' => 'Ada'], now()->addSeconds($offsetSeconds)->getTimestamp()),
    ])->assertInvalid(['init_data']);

    expect(User::query()->count())->toBe(0);
})->with([
    'two hours old' => [-7200],
    'ten minutes in the future' => [600],
]);

test('signed telegram data without a user is rejected', function () {
    $initData = telegramInitData(['id' => 42]);
    parse_str($initData, $fields);
    unset($fields['user'], $fields['hash']);
    ksort($fields);
    $check = collect($fields)->map(fn (string $value, string $key): string => $key.'='.$value)->implode("\n");
    $fields['hash'] = hash_hmac('sha256', $check, hash_hmac('sha256', '123456:telegram-test-token', 'WebAppData', true));

    $this->post(route('auth.telegram.store'), ['init_data' => http_build_query($fields)])->assertInvalid(['init_data']);

    expect(User::query()->count())->toBe(0);
});

test('a mini app sign-in returns to the store page it started on', function (string $redirect, string $expected) {
    $this->post(route('auth.telegram.store'), [
        'init_data' => telegramInitData(['id' => 42, 'first_name' => 'Ada']),
        'redirect' => $redirect,
    ])->assertRedirect($expected);

    $this->assertAuthenticated();
})->with([
    'a store page' => ['/s/smile-tea/p/jasmine', '/s/smile-tea/p/jasmine'],
    'another site' => ['//evil.example/s/', '/dashboard'],
    'a page outside the store' => ['/admin/vendors', '/dashboard'],
]);

test('telegram sign-in is rate limited per telegram account', function () {
    for ($attempt = 0; $attempt < 10; $attempt++) {
        $this->post(route('auth.telegram.store'), ['init_data' => telegramInitData(['id' => 42], hash: 'bad')]);
    }

    $this->post(route('auth.telegram.store'), ['init_data' => telegramInitData(['id' => 42], hash: 'bad')])
        ->assertTooManyRequests();

    $this->post(route('auth.telegram.store'), ['init_data' => telegramInitData(['id' => 43, 'first_name' => 'Grace'])])
        ->assertRedirect();
});

test('a mini app customer can send a request without an email', function () {
    Queue::fake();
    $store = openStore(User::factory()->create(), 'Smile Tea');
    $product = Product::factory()->for($store)->create();

    $this->post(route('auth.telegram.store'), [
        'init_data' => telegramInitData(['id' => 42, 'first_name' => 'Ada', 'username' => 'ada']),
        'redirect' => '/s/'.$store->slug,
    ])->assertRedirect('/s/'.$store->slug);

    $this->post(route('inquiries.product', [$store, $product]))
        ->assertSessionHas('status', 'Sent to the store on Telegram.');

    expect(Inquiry::query()->sole())
        ->contact->toBe('@ada')
        ->customer_name->toBe('Ada');
});

/**
 * @param  array<string, mixed>  $user
 */
function telegramInitData(array $user, ?int $authDate = null, ?string $hash = null): string
{
    $token = '123456:telegram-test-token';
    config(['services.telegram.bot_token' => $token]);

    $fields = [
        'auth_date' => (string) ($authDate ?? now()->getTimestamp()),
        'query_id' => 'AAE',
        'user' => json_encode($user, JSON_THROW_ON_ERROR),
    ];
    ksort($fields);

    $check = collect($fields)->map(fn (string $value, string $key): string => $key.'='.$value)->implode("\n");
    $secret = hash_hmac('sha256', $token, 'WebAppData', true);
    $fields['hash'] = $hash ?? hash_hmac('sha256', $check, $secret);

    return http_build_query($fields);
}
