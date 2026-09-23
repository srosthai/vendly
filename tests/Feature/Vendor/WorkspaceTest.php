<?php

use App\Models\Plan;
use App\Models\User;

test('a vendor can open the store tools and another vendor cannot', function () {
    $vendor = User::factory()->create();
    $store = openStore($vendor, 'Smile Tea');
    $other = User::factory()->create();

    $this->actingAs($vendor)
        ->get(route('vendor.products'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('vendor/products')
            ->where('usage.limit', 10));

    $this->actingAs($vendor)
        ->get(route('vendor.store'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('vendor/store')
            ->where('store.web_url', route('stores.show', $store)));

    $this->actingAs($other)->get(route('vendor.products'))->assertForbidden();
});

test('the plan page shows paid plans', function () {
    $vendor = User::factory()->create();
    openStore($vendor, 'Smile Tea');

    Plan::query()->create([
        'name' => 'Starter',
        'price_cents' => 500,
        'product_limit' => 100,
        'is_active' => true,
        'is_default' => false,
    ]);

    $this->actingAs($vendor)
        ->get(route('vendor.plan'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('vendor/plan')->has('plans', 1));
});
