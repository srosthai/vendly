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

test('google sign-in asks for the environment keys when they are missing', function () {
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
    ]));

    $this->get(route('auth.google.callback'))->assertRedirect(route('dashboard'));

    $user = User::query()->first();

    expect($user)->not->toBeNull()
        ->and($user->email)->toBe('ada@example.com')
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->is_admin)->toBeFalse();

    $this->assertAuthenticatedAs($user);
});
