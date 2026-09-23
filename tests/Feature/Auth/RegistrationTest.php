<?php

use App\Models\User;
use App\Notifications\EmailSignInCode;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('registration creates the account only after the code is valid', function () {
    Notification::fake();

    $this->post(route('auth.register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('auth.register.code'));

    expect(User::query()->count())->toBe(0);

    $code = null;
    Notification::assertSentOnDemand(EmailSignInCode::class, function (EmailSignInCode $notification) use (&$code): bool {
        $code = $notification->code;

        return $notification->purpose === 'registration';
    });

    $this->post(route('auth.register.verify'), [
        'code' => '000000',
    ])->assertInvalid(['code']);

    expect(User::query()->count())->toBe(0);

    $this->post(route('auth.register.verify'), [
        'code' => $code,
    ])->assertRedirect(route('dashboard', absolute: false));

    $user = User::query()->where('email', 'test@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->email_verified_at)->not->toBeNull();
    $this->assertAuthenticatedAs($user);

    $this->post(route('logout'));

    $this->post(route('login.store'), [
        'email' => 'test@example.com',
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));
});
