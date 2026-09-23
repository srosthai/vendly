<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

beforeEach(function () {
    config([
        'services.google.client_id' => 'google-client',
        'services.google.client_secret' => 'google-secret',
        'services.google.redirect' => 'http://localhost/auth/google/callback',
    ]);
});

test('google sign-in is refused when no keys are set', function () {
    config([
        'services.google.client_id' => null,
        'services.google.client_secret' => null,
    ]);

    $this->get(route('auth.google.redirect'))
        ->assertRedirect(route('login'));
});

test('google redirects without calling the network when faked', function () {
    Http::preventStrayRequests();
    Socialite::fake('google');

    $this->get(route('auth.google.redirect'))->assertRedirect();
});

test('google sign-in creates a verified user and does not grant admin', function () {
    Http::preventStrayRequests();

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-1',
        'name' => 'Ada Lovelace',
        'email' => 'Ada@Example.com',
        'email_verified' => true,
    ]));

    $this->get(route('auth.google.callback'))->assertRedirect(route('dashboard'));

    $user = User::query()->first();

    expect($user)->not->toBeNull()
        ->and($user->email)->toBe('ada@example.com')
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->is_admin)->toBeFalse();

    $this->assertAuthenticatedAs($user);
});

test('google sign-in refuses an email google has not verified', function () {
    Http::preventStrayRequests();

    Socialite::fake('google', SocialiteUser::fake([
        'email' => 'ada@example.com',
        'email_verified' => false,
    ]));

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', 'Google has not verified this email address.');

    expect(User::query()->count())->toBe(0);
    $this->assertGuest();
});

test('google sign-in takes the email from an account that never verified it', function () {
    Http::preventStrayRequests();
    $squatter = User::factory()->unverified()->create(['email' => 'ada@example.com']);

    Socialite::fake('google', SocialiteUser::fake([
        'email' => 'ada@example.com',
        'email_verified' => true,
    ]));

    $this->get(route('auth.google.callback'))->assertRedirect(route('dashboard'));

    $owner = User::query()->where('email', 'ada@example.com')->sole();

    expect($owner->id)->not->toBe($squatter->id)
        ->and($owner->password)->toBeNull()
        ->and($squatter->fresh()->email)->toBeNull();
    $this->assertAuthenticatedAs($owner);
});

test('google sign-in signs in the verified owner of an email', function () {
    Http::preventStrayRequests();
    $owner = User::factory()->create(['email' => 'ada@example.com']);

    Socialite::fake('google', SocialiteUser::fake([
        'email' => 'ada@example.com',
        'email_verified' => true,
    ]));

    $this->get(route('auth.google.callback'))->assertRedirect(route('dashboard'));

    expect(User::query()->count())->toBe(1);
    $this->assertAuthenticatedAs($owner);
});
