<?php

use App\Models\User;

test('seeded accounts can sign in for admin, vendor, and customer', function () {
    $this->seed();
    $this->seed();

    expect(User::query()->where('email', 'admin@vendly.test')->count())->toBe(1);

    $this->post(route('login.store'), [
        'email' => 'admin@vendly.test',
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->get(route('admin.vendors'))->assertOk();
    $this->get(route('admin.plans'))->assertOk();
    $this->get(route('admin.payments'))->assertOk();
    $this->get(route('admin.telegram'))->assertOk();

    $this->post(route('logout'));

    $this->post(route('login.store'), [
        'email' => 'vendor@vendly.test',
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->get(route('vendor.products'))->assertOk();
    $this->get(route('vendor.store'))->assertOk();
    $this->get(route('admin.vendors'))->assertForbidden();

    $this->post(route('logout'));

    $this->post(route('login.store'), [
        'email' => 'customer@vendly.test',
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->get(route('dashboard'))->assertOk();
    $this->get(route('vendor.products'))->assertForbidden();
    $this->get(route('admin.vendors'))->assertForbidden();
});
