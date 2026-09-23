<?php

use App\Enums\ProductStatus;
use App\Jobs\SendTelegramMessage;
use App\Models\Cart;
use App\Models\Inquiry;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

test('a guest can add to the cart and must sign in before buying', function () {
    $vendor = User::factory()->create();
    $store = openStore($vendor, 'Smile Tea');
    $product = Product::query()->create([
        'store_id' => $store->id,
        'name' => 'Jasmine',
        'slug' => 'jasmine',
        'price_cents' => 250,
        'status' => ProductStatus::Published,
    ]);

    $this->post(route('cart.store', [$store, $product]))->assertRedirect();
    expect(Cart::query()->count())->toBe(0);

    $this->get(route('stores.products.show', ['store' => $store, 'productSlug' => 'jasmine']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('cart.count', 1)->where('authenticated', false));

    $this->post(route('inquiries.product', [$store, $product]))->assertRedirect(route('login'));
    expect(Inquiry::query()->count())->toBe(0);

    $this->get(route('login', ['next' => '/s/'.$store->slug.'/p/jasmine']))
        ->assertOk();

    expect(session('url.intended'))->toBe(url('/s/'.$store->slug.'/p/jasmine'));
});

test('a signed-in customer can buy one product', function () {
    Http::fake();
    config(['services.telegram.bot_token' => '123:ABC', 'services.telegram.admin_chat_id' => '9']);

    $vendor = User::factory()->create();
    $customer = User::factory()->create();
    $store = openStore($vendor, 'Smile Tea');
    config(['services.telegram.bot_token' => '123:ABC', 'services.telegram.admin_chat_id' => '9']);
    $product = Product::query()->create([
        'store_id' => $store->id,
        'name' => 'Jasmine',
        'slug' => 'jasmine',
        'price_cents' => 250,
        'status' => ProductStatus::Published,
    ]);

    $this->actingAs($customer)
        ->post(route('inquiries.product', [$store, $product]))
        ->assertRedirect();

    expect(Inquiry::query()->count())->toBe(1);
});

test('a draft cannot be added to a cart, bought, or sent', function () {
    $store = openStore(User::factory()->create(), 'Smile Tea');
    Queue::fake();
    $draft = Product::factory()->for($store)->draft()->create();
    $customer = User::factory()->create();

    $this->post(route('cart.store', [$store, $draft]))->assertInvalid(['product']);
    expect(session('guest-carts'))->toBeNull();

    $this->actingAs($customer)->post(route('cart.store', [$store, $draft]))->assertInvalid(['product']);
    $this->actingAs($customer)->post(route('inquiries.product', [$store, $draft]))->assertInvalid(['product']);

    $cart = Cart::query()->create(['user_id' => $customer->id, 'store_id' => $store->id]);
    $cart->items()->create(['product_id' => $draft->id, 'quantity' => 1]);

    $this->actingAs($customer)->post(route('inquiries.cart', $store))->assertInvalid(['cart']);

    expect(Inquiry::query()->count())->toBe(0);
    Queue::assertNothingPushed();
});

test('a sold-out product cannot be added to a cart', function () {
    $store = openStore(User::factory()->create(), 'Smile Tea');
    $soldOut = Product::factory()->for($store)->soldOut()->create();

    $this->post(route('cart.store', [$store, $soldOut]))->assertInvalid(['product']);

    expect(session('guest-carts'))->toBeNull();
});

test('the guest cart moves to the account once at sign-in and skips products that became drafts', function () {
    $store = openStore(User::factory()->create(), 'Smile Tea');
    $kept = Product::factory()->for($store)->create();
    $unpublished = Product::factory()->for($store)->create();
    $customer = User::factory()->create(['password' => 'password']);

    $this->post(route('cart.store', [$store, $kept]), ['quantity' => 2])->assertRedirect();
    $this->post(route('cart.store', [$store, $unpublished]))->assertRedirect();

    $unpublished->update(['status' => ProductStatus::Draft]);

    $this->post(route('login.store'), ['email' => $customer->email, 'password' => 'password'])->assertRedirect();

    $cart = Cart::query()->whereBelongsTo($customer)->whereBelongsTo($store)->sole();

    expect($cart->items()->pluck('quantity', 'product_id')->all())->toBe([$kept->id => 2])
        ->and(session('guest-carts'))->toBeNull();
});

test('two stores never share a cart', function () {
    $tea = openStore(User::factory()->create(), 'Smile Tea');
    $cake = openStore(User::factory()->create(), 'Cake Corner');
    $jasmine = Product::factory()->for($tea)->create();
    $sponge = Product::factory()->for($cake)->create();
    $customer = User::factory()->create();

    $this->actingAs($customer)->post(route('cart.store', [$tea, $jasmine]))->assertRedirect();
    $this->actingAs($customer)->post(route('cart.store', [$cake, $sponge]))->assertRedirect();
    $this->actingAs($customer)->post(route('cart.store', [$tea, $sponge]))->assertNotFound();

    $this->actingAs($customer)
        ->get(route('stores.show', $tea))
        ->assertInertia(fn ($page) => $page->where('cart.count', 1)->where('cart.items.0.id', $jasmine->id));
});

test('buy and send requests are rate limited per customer', function () {
    $store = openStore(User::factory()->create(), 'Smile Tea');
    Queue::fake();
    $product = Product::factory()->for($store)->create();
    $customer = User::factory()->create();

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->actingAs($customer)->post(route('inquiries.product', [$store, $product]))->assertSessionHas('status', 'Sent to the store on Telegram.');
    }

    $this->actingAs($customer)
        ->post(route('inquiries.product', [$store, $product]))
        ->assertRedirect()
        ->assertSessionHas('status', fn (string $status): bool => str_starts_with($status, 'Too many requests. Try again in'));

    expect(Inquiry::query()->count())->toBe(5);
});

test('a cart request ends with the store link', function () {
    $store = openStore(User::factory()->create(), 'Smile Tea');
    Queue::fake();
    config(['services.telegram.admin_chat_id' => '9']);
    $product = Product::factory()->for($store)->create();
    $customer = User::factory()->create();

    $this->actingAs($customer)->post(route('cart.store', [$store, $product]))->assertRedirect();
    $this->actingAs($customer)->post(route('inquiries.cart', $store))->assertRedirect();

    Queue::assertPushed(SendTelegramMessage::class, fn (SendTelegramMessage $job): bool => str_ends_with($job->text, route('stores.show', $store)));
});

test('a guest can change a cart quantity and remove a line', function () {
    $store = openStore(User::factory()->create(), 'Smile Tea');
    $jasmine = Product::factory()->for($store)->create();
    $oolong = Product::factory()->for($store)->create();

    $this->post(route('cart.store', [$store, $jasmine]))->assertRedirect();
    $this->post(route('cart.store', [$store, $oolong]))->assertRedirect();

    $this->patch(route('cart.update', [$store, $jasmine]), ['quantity' => 4])->assertRedirect();
    $this->delete(route('cart.destroy', [$store, $oolong]))->assertRedirect();

    $this->get(route('stores.show', $store))
        ->assertInertia(fn ($page) => $page
            ->where('cart.count', 4)
            ->where('cart.total_cents', $jasmine->price_cents * 4)
            ->has('cart.items', 1)
            ->where('cart.items.0.id', $jasmine->id));
});

test('a customer can change a cart quantity and remove a line', function () {
    $store = openStore(User::factory()->create(), 'Smile Tea');
    $jasmine = Product::factory()->for($store)->create();
    $customer = User::factory()->create();

    $this->actingAs($customer)->post(route('cart.store', [$store, $jasmine]))->assertRedirect();
    $this->actingAs($customer)->patch(route('cart.update', [$store, $jasmine]), ['quantity' => 3])->assertRedirect();

    $cart = Cart::query()->whereBelongsTo($customer)->sole();
    expect($cart->items()->sole()->quantity)->toBe(3);

    $this->actingAs($customer)->delete(route('cart.destroy', [$store, $jasmine]))->assertRedirect();
    expect($cart->items()->count())->toBe(0);
});

test('a cart quantity must be between 1 and 99', function (int $quantity) {
    $store = openStore(User::factory()->create(), 'Smile Tea');
    $jasmine = Product::factory()->for($store)->create();

    $this->post(route('cart.store', [$store, $jasmine]))->assertRedirect();
    $this->patch(route('cart.update', [$store, $jasmine]), ['quantity' => $quantity])->assertInvalid(['quantity']);

    expect(session('guest-carts')[(string) $store->id][(string) $jasmine->id])->toBe(1);
})->with([0, 100]);

test('a cart line from another store cannot be changed, and a product not in the cart is not added', function () {
    $tea = openStore(User::factory()->create(), 'Smile Tea');
    $cake = openStore(User::factory()->create(), 'Cake Corner');
    $jasmine = Product::factory()->for($tea)->create();
    $sponge = Product::factory()->for($cake)->create();
    $customer = User::factory()->create();

    $this->actingAs($customer)->post(route('cart.store', [$tea, $jasmine]))->assertRedirect();

    $this->actingAs($customer)->patch(route('cart.update', [$tea, $sponge]), ['quantity' => 5])->assertNotFound();
    $this->actingAs($customer)->delete(route('cart.destroy', [$tea, $sponge]))->assertNotFound();
    $this->actingAs($customer)->patch(route('cart.update', [$cake, $sponge]), ['quantity' => 5])->assertRedirect();

    expect(Cart::query()->whereBelongsTo($customer)->whereBelongsTo($cake)->exists())->toBeFalse()
        ->and(Cart::query()->whereBelongsTo($customer)->whereBelongsTo($tea)->sole()->items()->sole()->quantity)->toBe(1);
});
