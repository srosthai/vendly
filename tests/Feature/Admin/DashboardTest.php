<?php

use App\Models\PlatformSetting;
use App\Models\User;

test('vendors cannot open the admin dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.vendors'))->assertForbidden();
});

test('an admin can list vendors, create a plan, and save telegram settings', function () {
    $admin = User::factory()->create();
    $admin->is_admin = true;
    $admin->save();

    $this->actingAs($admin)
        ->get(route('admin.vendors'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/vendors'));

    $this->actingAs($admin)->post(route('admin.plans.store'), [
        'name' => 'Starter',
        'price' => '5.00',
        'product_limit' => 100,
        'is_active' => true,
        'is_default' => false,
    ])->assertRedirect();

    $this->actingAs($admin)
        ->get(route('admin.plans'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/plans')->has('plans', 1));

    $this->actingAs($admin)->put(route('admin.telegram.update'), [
        'admin_chat_id' => '4242',
        'bot_username' => 'VendlyBot',
        'mini_app_short_name' => 'shop',
    ])->assertRedirect();

    $settings = PlatformSetting::current();

    expect($settings->admin_chat_id)->toBe('4242')
        ->and($settings->botUsername())->toBe('VendlyBot');

    $this->actingAs($admin)
        ->get(route('admin.payments'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/payments'));
});
