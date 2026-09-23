<?php

use App\Models\User;
use App\Notifications\RegistrationCode;
use Illuminate\Support\Facades\Notification;

function startRegistration(object $test, string $email = 'test@example.com', string $password = 'password'): string
{
    $test->post(route('auth.register.store'), [
        'name' => 'Test User',
        'email' => $email,
        'password' => $password,
        'password_confirmation' => $password,
    ])->assertRedirect(route('auth.register.code'));

    $code = null;
    Notification::assertSentOnDemand(RegistrationCode::class, function (RegistrationCode $notification) use (&$code): bool {
        $code = $notification->code;

        return true;
    });

    return $code;
}

function wrongCode(string $code): string
{
    return $code === '000000' ? '111111' : '000000';
}

beforeEach(function () {
    Notification::fake();
});

test('registration screen can be rendered', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('auth/register')->where('step', 'details'));
});

test('registration creates the account only after the code is valid', function () {
    $code = startRegistration($this);

    expect(User::query()->count())->toBe(0);

    $this->post(route('auth.register.verify'), ['code' => wrongCode($code)])->assertInvalid(['code']);

    expect(User::query()->count())->toBe(0);

    $this->post(route('auth.register.verify'), [
        'code' => $code,
    ])->assertRedirect(route('dashboard', absolute: false));

    $user = User::query()->where('email', 'test@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->is_admin)->toBeFalse();
    $this->assertAuthenticatedAs($user);

    $this->post(route('logout'));

    $this->post(route('login.store'), [
        'email' => 'test@example.com',
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));
});

test('fortify registration and the old email code sign-in cannot create or sign in users', function () {
    $admin = User::factory()->create(['email' => 'admin@example.com', 'is_admin' => true]);

    $this->post('/register', [
        'name' => 'Mallory',
        'email' => 'victim@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertMethodNotAllowed();

    $this->post('/auth/email-code', ['email' => $admin->email])->assertNotFound();
    $this->post('/auth/email-code/verify', ['email' => $admin->email, 'code' => '123456'])->assertNotFound();

    expect(User::query()->count())->toBe(1);
    $this->assertGuest();
});

test('a verified email cannot be registered again', function () {
    User::factory()->create(['email' => 'test@example.com']);

    $this->post(route('auth.register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertInvalid(['email' => 'Log in instead']);

    Notification::assertNothingSent();
});

test('proving an email takes it from an account that never verified it', function () {
    $squatter = User::factory()->unverified()->create([
        'email' => 'test@example.com',
        'password' => 'squatter-password',
    ]);

    $code = startRegistration($this, password: 'owner-password');

    $this->post(route('auth.register.verify'), ['code' => $code])->assertRedirect();

    $owner = User::query()->where('email', 'test@example.com')->sole();

    expect($owner->id)->not->toBe($squatter->id)
        ->and($squatter->fresh()->email)->toBeNull();
    $this->assertAuthenticatedAs($owner);

    $this->post(route('logout'));

    $this->post(route('login.store'), [
        'email' => 'test@example.com',
        'password' => 'squatter-password',
    ])->assertInvalid(['email']);
});

test('the code stops working after five wrong attempts, even after a resend', function () {
    $code = startRegistration($this);

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->post(route('auth.register.verify'), ['code' => wrongCode($code)])->assertInvalid(['code']);
    }

    $this->post(route('auth.register.verify'), ['code' => $code])
        ->assertInvalid(['code' => 'Too many wrong codes']);

    Notification::fake();
    $newCode = startRegistration($this);

    $this->post(route('auth.register.verify'), ['code' => $newCode])
        ->assertInvalid(['code' => 'Too many wrong codes']);

    expect(User::query()->count())->toBe(0);
});

test('a wrong code does not extend the expiry', function () {
    $code = startRegistration($this);

    $this->travel(9)->minutes();
    $this->post(route('auth.register.verify'), ['code' => wrongCode($code)])->assertInvalid(['code']);

    $this->travel(2)->minutes();
    $this->post(route('auth.register.verify'), ['code' => $code])
        ->assertInvalid(['code' => 'invalid or has expired']);

    expect(User::query()->count())->toBe(0);
});

test('code checks are rate limited', function () {
    $code = startRegistration($this);

    for ($attempt = 0; $attempt < 10; $attempt++) {
        $this->post(route('auth.register.verify'), ['code' => wrongCode($code)]);
    }

    $this->post(route('auth.register.verify'), ['code' => $code])->assertTooManyRequests();
});
