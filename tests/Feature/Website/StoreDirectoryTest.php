<?php

use App\Models\Product;
use App\Models\User;

test('the store directory lists live stores that have something to sell', function () {
    $tea = openStore(User::factory()->create(), 'Smile Tea');
    Product::factory()->for($tea)->count(2)->create();
    Product::factory()->for($tea)->draft()->create();

    $empty = openStore(User::factory()->create(), 'Empty Shop');
    Product::factory()->for($empty)->draft()->create();

    $suspended = openStore(User::factory()->create(), 'Closed Shop');
    Product::factory()->for($suspended)->create();
    $suspended->forceFill(['suspended_at' => now()])->save();

    $this->get(route('stores.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('marketing/stores')
            ->has('stores.data', 1)
            ->where('stores.data.0.name', 'Smile Tea')
            ->where('stores.data.0.products_count', 2)
            ->where('stores.data.0.url', route('stores.show', $tea))
            ->missing('stores.data.0.user_id'));
});

test('the store directory searches by store name', function () {
    Product::factory()->for(openStore(User::factory()->create(), 'Smile Tea'))->create();
    Product::factory()->for(openStore(User::factory()->create(), 'Jozen Shoes'))->create();

    $this->get(route('stores.index', ['search' => 'smile']))
        ->assertInertia(fn ($page) => $page
            ->where('search', 'smile')
            ->has('stores.data', 1)
            ->where('stores.data.0.name', 'Smile Tea'));

    $this->get(route('stores.index', ['search' => 'nothing like it']))
        ->assertInertia(fn ($page) => $page->has('stores.data', 0));
});
