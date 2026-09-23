<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

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
