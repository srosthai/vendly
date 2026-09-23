<?php

use App\Models\Product;
use App\Models\User;

test('a vendor saves the store profile and empty fields clear it', function () {
    $vendor = User::factory()->create();
    $store = openStore($vendor, 'Smile Tea');

    $this->actingAs($vendor)->put(route('vendor.store.update'), [
        'name' => 'Smile Tea',
        'description' => 'Tea and <b>small</b> cakes',
        'accent' => 'teal',
        'phone' => '+855 12 345 678',
        'address' => 'Street 240, Phnom Penh',
        'hours' => 'Mon to Sat, 8:00 to 18:00',
        'social_links' => [
            'facebook' => 'https://facebook.com/smiletea',
            'website' => 'https://smiletea.example',
            'tiktok' => '',
        ],
    ])->assertSessionHasNoErrors();

    expect($store->fresh())
        ->description->toBe('Tea and small cakes')
        ->accent->toBe('teal')
        ->phone->toBe('+855 12 345 678')
        ->address->toBe('Street 240, Phnom Penh')
        ->hours->toBe('Mon to Sat, 8:00 to 18:00')
        ->social_links->toBe(['facebook' => 'https://facebook.com/smiletea', 'website' => 'https://smiletea.example']);

    $this->actingAs($vendor)->put(route('vendor.store.update'), [
        'name' => 'Smile Tea',
        'accent' => '',
        'phone' => '',
        'address' => '',
        'hours' => '',
        'social_links' => ['facebook' => ''],
    ])->assertSessionHasNoErrors();

    expect($store->fresh())
        ->accent->toBeNull()
        ->phone->toBeNull()
        ->address->toBeNull()
        ->hours->toBeNull()
        ->social_links->toBe([]);
});

test('store profile fields are validated', function () {
    $vendor = User::factory()->create();
    openStore($vendor, 'Smile Tea');

    $this->actingAs($vendor)->put(route('vendor.store.update'), [
        'name' => 'Smile Tea',
        'accent' => 'neon',
        'phone' => 'call me',
        'social_links' => [
            'facebook' => 'http://facebook.com/smiletea',
            'instagram' => 'javascript:alert(1)',
        ],
    ])->assertInvalid(['accent', 'phone', 'social_links.facebook', 'social_links.instagram']);
});

test('the storefront and product page show only the filled details', function () {
    $vendor = User::factory()->create();
    $store = openStore($vendor, 'Smile Tea');
    $store->update([
        'accent' => 'pink',
        'phone' => '+855 12 345 678',
        'social_links' => ['instagram' => 'https://instagram.com/smiletea'],
    ]);
    $product = Product::factory()->for($store)->create();

    $this->get(route('stores.show', $store))
        ->assertInertia(fn ($page) => $page
            ->where('store.accent', 'pink')
            ->where('store.phone', '+855 12 345 678')
            ->where('store.address', null)
            ->where('store.hours', null)
            ->where('store.socials', ['instagram' => 'https://instagram.com/smiletea']));

    $this->get(route('stores.products.show', ['store' => $store, 'productSlug' => $product->slug]))
        ->assertInertia(fn ($page) => $page
            ->where('store.phone', '+855 12 345 678')
            ->where('store.socials', ['instagram' => 'https://instagram.com/smiletea']));

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.vendors.show', $store))
        ->assertInertia(fn ($page) => $page->where('store.accent', 'pink')->where('store.phone', '+855 12 345 678'));
});

test('the store settings page loads the saved profile', function () {
    $vendor = User::factory()->create();
    $store = openStore($vendor, 'Smile Tea');
    $store->update(['hours' => 'Daily', 'social_links' => ['website' => 'https://smiletea.example']]);

    $this->actingAs($vendor)->get(route('vendor.store'))
        ->assertInertia(fn ($page) => $page
            ->where('store.hours', 'Daily')
            ->where('store.phone', '')
            ->where('store.social_links', ['facebook' => '', 'instagram' => '', 'tiktok' => '', 'website' => 'https://smiletea.example'])
            ->has('accents', 8));
});
