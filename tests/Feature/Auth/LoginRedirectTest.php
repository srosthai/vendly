<?php

use App\Models\Product;
use App\Models\User;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

function logInAs(User $user)
{
    return test()->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
}

test('a vendor lands on the dashboard even after browsing a store as a guest', function () {
    $vendor = User::factory()->create();
    $store = openStore($vendor, 'Smile Tea');
    $product = Product::factory()->for($store)->create();

    $this->get(route('login', ['next' => "/s/{$store->slug}/p/{$product->slug}"]));

    logInAs($vendor)->assertRedirect(route('dashboard'));
});

test('an admin lands on the dashboard after browsing a store as a guest', function () {
    $store = openStore(User::factory()->create(), 'Smile Tea');

    $this->get(route('login', ['next' => "/s/{$store->slug}"]));

    logInAs(User::factory()->admin()->create())->assertRedirect(route('dashboard'));
});

test('a customer goes back to the product they wanted to buy', function () {
    $store = openStore(User::factory()->create(), 'Smile Tea');
    $product = Product::factory()->for($store)->create();
    $url = "/s/{$store->slug}/p/{$product->slug}";

    $this->get(route('login', ['next' => $url]));

    logInAs(User::factory()->create())->assertRedirect(url($url));
});

test('a vendor sent to log in from a back-office page returns to it', function () {
    $vendor = User::factory()->create();
    openStore($vendor, 'Smile Tea');

    $this->get(route('vendor.products'))->assertRedirect(route('login'));

    logInAs($vendor)->assertRedirect(route('vendor.products'));
});

test('a plain log in sends a customer to their dashboard', function () {
    logInAs(User::factory()->create())->assertRedirect(route('dashboard'));
});

test('a vendor signing in with Google also lands on the dashboard', function () {
    config(['services.google.client_id' => 'google-client', 'services.google.client_secret' => 'google-secret']);
    $vendor = User::factory()->create(['email' => 'sokha@example.com']);
    $store = openStore($vendor, 'Smile Tea');

    $this->get(route('login', ['next' => "/s/{$store->slug}"]));

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-7',
        'name' => 'Sokha',
        'email' => 'sokha@example.com',
        'email_verified' => true,
    ]));

    $this->get(route('auth.google.callback'))->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($vendor);
});
