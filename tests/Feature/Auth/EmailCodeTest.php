<?php

use App\Models\User;
use App\Notifications\EmailSignInCode;
use Illuminate\Support\Facades\Notification;

test('an email code does not create a user until the code is valid', function () {
    Notification::fake();

    $this->post(route('auth.email-code.store'), [
        'email' => 'Ada@Example.com',
    ])->assertRedirect();

    expect(User::query()->count())->toBe(0);

    $code = sentCode();

    $this->post(route('auth.email-code.verify'), [
        'email' => 'ada@example.com',
        'code' => $code === '000000' ? '111111' : '000000',
        'name' => 'Ada',
    ])->assertInvalid(['code']);

    expect(User::query()->count())->toBe(0);

    $this->post(route('auth.email-code.verify'), [
        'email' => 'ada@example.com',
        'code' => $code,
        'name' => 'Ada',
        'is_admin' => true,
    ])->assertRedirect(route('dashboard'));

    $user = User::query()->first();

    expect($user)->not->toBeNull()
        ->and($user->email)->toBe('ada@example.com')
        ->and($user->is_admin)->toBeFalse()
        ->and($user->email_verified_at)->not->toBeNull();

    $this->assertAuthenticatedAs($user);
});

test('an existing email signs in instead of creating another user', function () {
    Notification::fake();
    $existing = User::factory()->create(['email' => 'ada@example.com']);

    $this->post(route('auth.email-code.store'), ['email' => 'ada@example.com'])->assertRedirect();

    $this->post(route('auth.email-code.verify'), [
        'email' => 'ada@example.com',
        'code' => sentCode(),
    ])->assertRedirect(route('dashboard'));

    expect(User::query()->count())->toBe(1);
    $this->assertAuthenticatedAs($existing);
});

test('email code requests are rate limited', function () {
    Notification::fake();

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->post(route('auth.email-code.store'), [
            'email' => 'ada@example.com',
        ])->assertRedirect();
    }

    $this->post(route('auth.email-code.store'), [
        'email' => 'ada@example.com',
    ])->assertTooManyRequests();
});

function sentCode(): string
{
    $code = null;

    Notification::assertSentOnDemand(
        EmailSignInCode::class,
        function (EmailSignInCode $notification) use (&$code): bool {
            $code = $notification->code;

            return true;
        },
    );

    expect($code)->toBeString();

    return $code;
}
