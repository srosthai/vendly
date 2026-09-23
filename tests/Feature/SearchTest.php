<?php

use App\Enums\PaymentStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Inquiry;
use App\Models\Plan;
use App\Models\Product;
use App\Models\SubscriptionPayment;
use App\Models\Testimonial;
use App\Models\User;

/**
 * @return array<string, list<string>>
 */
function searchTitles(array $groups): array
{
    return collect($groups)->mapWithKeys(fn (array $group): array => [$group['label'] => array_column($group['items'], 'title')])->all();
}

test('an admin searches across the back office', function () {
    $store = openStore(User::factory()->create(['email' => 'owner@tealeaf.example']), 'Tealeaf Shop');
    $plan = Plan::query()->create(['name' => 'Tealeaf Plus', 'price_cents' => 500, 'product_limit' => 100, 'is_active' => true, 'is_default' => false]);
    SubscriptionPayment::query()->create(['public_id' => 'subpay_tealeaf1', 'store_id' => $store->id, 'plan_id' => $plan->id, 'amount_cents' => 500, 'status' => PaymentStatus::Paid]);
    Inquiry::query()->create(['public_id' => 'inq_t', 'number' => 1, 'store_id' => $store->id, 'customer_name' => 'Tealeaf Fan', 'from_cart' => false]);
    Testimonial::factory()->create(['name' => 'Tealeaf Owner']);

    $groups = $this->actingAs(User::factory()->admin()->create())
        ->getJson(route('search', ['q' => 'tealeaf']))
        ->assertOk()
        ->json('groups');

    expect(searchTitles($groups))
        ->toHaveKeys(['Vendors', 'Plans', 'Payments', 'Requests', 'Testimonials'])
        ->and(searchTitles($groups)['Vendors'])->toBe(['Tealeaf Shop']);

    $pages = $this->actingAs(User::factory()->admin()->create())->getJson(route('search', ['q' => 'settings']))->json('groups');
    expect(searchTitles($pages)['Pages'])->toBe(['Site settings']);
});

test('a vendor searches only their own store', function () {
    $vendor = User::factory()->create();
    $mine = openStore($vendor, 'Smile Tea');
    $other = openStore(User::factory()->create(), 'Other Tea');

    Product::factory()->for($mine)->create(['name' => 'Jasmine pearls']);
    Product::factory()->for($other)->create(['name' => 'Jasmine green']);
    Category::query()->create(['store_id' => $mine->id, 'name' => 'Jasmine teas', 'slug' => 'jasmine', 'sort' => 1]);
    Category::query()->create(['store_id' => $other->id, 'name' => 'Jasmine others', 'slug' => 'jasmine-o', 'sort' => 1]);
    Brand::query()->create(['store_id' => $other->id, 'name' => 'Jasmine Co', 'slug' => 'jasmine-co']);
    Inquiry::query()->create(['public_id' => 'inq_o', 'number' => 1, 'store_id' => $other->id, 'customer_name' => 'Jasmine Buyer', 'from_cart' => false]);

    $titles = searchTitles($this->actingAs($vendor)->getJson(route('search', ['q' => 'jasmine']))->assertOk()->json('groups'));

    expect($titles)->toBe([
        'Products' => ['Jasmine pearls'],
        'Categories' => ['Jasmine teas'],
    ]);
});

test('search needs two characters and a workspace', function () {
    $vendor = User::factory()->create();
    openStore($vendor, 'Smile Tea');

    $this->actingAs($vendor)->getJson(route('search', ['q' => 'j']))->assertOk()->assertJsonPath('groups', []);
    $this->actingAs(User::factory()->create())->getJson(route('search', ['q' => 'smile']))->assertOk()->assertJsonPath('groups', []);
    auth()->logout();
    $this->getJson(route('search', ['q' => 'smile']))->assertUnauthorized();
});

test('vendor results open the right screens', function () {
    $vendor = User::factory()->create();
    $store = openStore($vendor, 'Smile Tea');
    $product = Product::factory()->for($store)->create(['name' => 'Oolong']);

    $groups = $this->actingAs($vendor)->getJson(route('search', ['q' => 'oolong']))->json('groups');

    expect($groups[0]['items'][0]['url'])->toBe(route('vendor.products', ['edit' => $product->id]));
});
