<?php

use App\Models\Plan;
use App\Models\User;

test('the home page offers sign in and start selling', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('welcome'));
});

test('sign in is the email and password form', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('auth/login'));

    $this->get(route('auth.sign-in'))
        ->assertRedirect(route('login'));
});

test('start selling requires an account and creates one store', function () {
    $this->get(route('selling.create'))->assertRedirect(route('login'));

    Plan::query()->create([
        'name' => 'Free',
        'price_cents' => 0,
        'product_limit' => 10,
        'is_active' => true,
        'is_default' => true,
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('selling.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('selling/create'));

    $this->actingAs($user)
        ->post(route('stores.store'), ['name' => 'Smile Tea'])
        ->assertRedirect();

    $this->actingAs($user)
        ->get(route('selling.create'))
        ->assertRedirect(route('dashboard'));
});
