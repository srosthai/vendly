<?php

use App\Models\Plan;
use App\Models\User;
use App\Notifications\EmailSignInCode;
use Illuminate\Support\Facades\Notification;

test('the home page offers sign in and start selling', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('welcome'));
});

test('sign in shows the email step and then the code', function () {
    Notification::fake();

    $this->get(route('auth.sign-in', ['next' => 'sell']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('auth/sign-in')
            ->where('step', 'email'));

    $this->post(route('auth.email-code.store'), [
        'email' => 'ada@example.com',
    ])->assertRedirect(route('auth.sign-in.code'));

    $this->get(route('auth.sign-in.code'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('auth/sign-in')
            ->where('step', 'code')
            ->where('email', 'ada@example.com'));

    Notification::assertSentOnDemand(EmailSignInCode::class);
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
