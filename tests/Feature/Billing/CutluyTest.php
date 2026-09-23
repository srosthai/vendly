<?php

use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\SubscriptionStatus;
use App\Jobs\ApplyCutluyWebhook;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Store;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Sleep;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    config([
        'services.cutluy.key' => 'ck_test_key',
        'services.cutluy.webhook_secret' => 'whsec_test',
        'services.cutluy.base_url' => 'https://cutluy.com',
        'services.telegram.bot_token' => null,
    ]);
});

test('a re-serialized body and a stale timestamp fail verification', function () {
    Queue::fake();
    $raw = cutluyDelivery('payment.completed');
    $signature = cutluySignature($raw, now()->getTimestamp());

    cutluyCall(json_encode(json_decode($raw), JSON_PRETTY_PRINT), 'payment.completed', $signature)->assertUnauthorized();
    cutluyCall($raw, 'payment.completed', cutluySignature($raw, now()->subMinutes(6)->getTimestamp()), now()->subMinutes(6)->getTimestamp())
        ->assertUnauthorized();

    Queue::assertNotPushed(ApplyCutluyWebhook::class);
});

test('a webhook is refused when the signing secret is not configured', function () {
    Queue::fake();
    $raw = cutluyDelivery('payment.completed');
    $signature = cutluySignature($raw, now()->getTimestamp());

    config(['services.cutluy.webhook_secret' => '']);

    cutluyCall($raw, 'payment.completed', $signature)->assertUnauthorized();

    Queue::assertNothingPushed();
});

test('a signed webhook is queued without a session and unknown events are ignored', function () {
    Queue::fake();

    cutluyCall(cutluyDelivery('payment.completed'), 'payment.completed')
        ->assertNoContent()
        ->assertCookieMissing(config('session.cookie'));

    Queue::assertPushed(ApplyCutluyWebhook::class, fn (ApplyCutluyWebhook $job): bool => $job->event === 'payment.completed');

    cutluyCall(cutluyDelivery('payment.refunded'), 'payment.refunded')->assertNoContent();

    Queue::assertPushed(ApplyCutluyWebhook::class, 1);
});

test('the signed event type wins over the unsigned header', function () {
    Http::preventStrayRequests();
    $store = openStore(User::factory()->create(), 'Header Tea');
    $freeId = $store->subscription->plan_id;
    pendingStarterPayment($store);

    cutluyCall(cutluyDelivery('payment.scanned'), 'payment.completed')->assertNoContent();

    expect($store->fresh()->subscription->plan_id)->toBe($freeId);
});

test('a completed payment that does not match the local payment does not extend the plan', function (array $remote) {
    Http::preventStrayRequests();
    $store = openStore(User::factory()->create(), 'Mismatch Tea');
    $freeId = $store->subscription->plan_id;
    $payment = pendingStarterPayment($store);

    cutluyCall(cutluyDelivery('payment.completed', $remote), 'payment.completed')->assertNoContent();

    expect($store->fresh()->subscription->plan_id)->toBe($freeId)
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Pending);
})->with([
    'a lower amount' => [['amount' => '0.01']],
    'another reference' => [['reference_id' => 'subpay_other']],
    'another currency' => [['currency' => 'KHR']],
]);

test('scanned does not raise the product limit and completed does only once', function () {
    Http::preventStrayRequests();
    $vendor = User::factory()->create();
    $store = openStore($vendor, 'Smile Tea', 1);
    $paid = Plan::query()->create([
        'name' => 'Starter',
        'price_cents' => 500,
        'product_limit' => 100,
        'is_active' => true,
        'is_default' => false,
    ]);
    SubscriptionPayment::query()->create([
        'public_id' => 'subpay_test',
        'store_id' => $store->id,
        'plan_id' => $paid->id,
        'amount_cents' => 500,
        'cutluy_id' => 'pay_123',
        'status' => PaymentStatus::Pending,
    ]);

    $first = Product::query()->create([
        'store_id' => $store->id,
        'name' => 'One',
        'slug' => 'one',
        'price_cents' => 100,
        'status' => ProductStatus::Draft,
    ]);
    $second = Product::query()->create([
        'store_id' => $store->id,
        'name' => 'Two',
        'slug' => 'two',
        'price_cents' => 100,
        'status' => ProductStatus::Draft,
    ]);

    $this->actingAs($vendor)->post(route('products.publish', $first))->assertRedirect();
    $this->actingAs($vendor)->post(route('products.publish', $second))->assertInvalid(['status']);

    cutluyCall(cutluyDelivery('payment.scanned'), 'payment.scanned')->assertNoContent();

    expect($store->fresh()->subscription->plan->product_limit)->toBe(1)
        ->and($store->fresh()->subscription->plan->isFree())->toBeTrue();

    $this->actingAs($vendor)->post(route('products.publish', $second))->assertInvalid(['status']);

    $this->travelTo(now());
    cutluyCall(cutluyDelivery('payment.completed'), 'payment.completed')->assertNoContent();

    $endsAt = $store->fresh()->subscription->ends_at;
    expect($store->fresh()->subscription->plan_id)->toBe($paid->id)
        ->and($store->fresh()->subscription->status)->toBe(SubscriptionStatus::Active)
        ->and($endsAt)->not->toBeNull();

    $this->actingAs($vendor)->post(route('products.publish', $second))->assertRedirect();

    cutluyCall(cutluyDelivery('payment.completed'), 'payment.completed')->assertNoContent();

    expect($store->fresh()->subscription->ends_at?->equalTo($endsAt))->toBeTrue();
});

test('cutluy client errors do not start a plan', function (int $status, string $error) {
    Http::preventStrayRequests();
    Http::fake([
        'cutluy.com/*' => Http::response(['error' => $error, 'message' => 'secret detail'], $status),
    ]);

    $vendor = User::factory()->create();
    $store = openStore($vendor, 'Smile Tea '.$status);
    $freeId = $store->subscription->plan_id;
    $paid = Plan::query()->create([
        'name' => 'Starter '.$status,
        'price_cents' => 500,
        'product_limit' => 100,
        'is_active' => true,
        'is_default' => false,
    ]);

    $this->actingAs($vendor)
        ->postJson(route('plans.payments.store', $paid))
        ->assertInvalid(['plan']);

    expect($store->fresh()->subscription->plan_id)->toBe($freeId);

    Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer ck_test_key'));
})->with([
    [401, 'unauthorized'],
    [402, 'quota_exceeded'],
    [403, 'account_suspended'],
]);

test('a rate limit waits for retry-after once', function () {
    Http::preventStrayRequests();
    Sleep::fake();
    Http::fakeSequence()
        ->push(['error' => 'rate_limited', 'message' => 'slow'], 429, ['Retry-After' => '3'])
        ->push([
            'id' => 'pay_429',
            'status' => 'pending',
            'checkout_url' => 'https://cutluy.com/pay/pay_429',
            'qr_string' => '000201',
        ], 201);

    $vendor = User::factory()->create();
    openStore($vendor, 'Rate Tea');
    $paid = Plan::query()->create([
        'name' => 'Starter',
        'price_cents' => 500,
        'product_limit' => 100,
        'is_active' => true,
        'is_default' => false,
    ]);

    $this->actingAs($vendor)
        ->postJson(route('plans.payments.store', $paid))
        ->assertOk()
        ->assertJsonPath('status', 'pending');

    Sleep::assertSleptTimes(1);
    Http::assertSentCount(2);
    Http::assertSent(fn (Request $request): bool => $request['idempotency_key'] === $request['reference_id']
        && $request['amount'] === 5.0);
});

test('a second rate limit stops', function () {
    Http::preventStrayRequests();
    Sleep::fake();
    Http::fakeSequence()
        ->push(['error' => 'rate_limited', 'message' => 'slow'], 429, ['Retry-After' => '2'])
        ->push(['error' => 'rate_limited', 'message' => 'slow'], 429, ['Retry-After' => '9']);

    $vendor = User::factory()->create();
    $store = openStore($vendor, 'Stop Tea');
    $freeId = $store->subscription->plan_id;
    $paid = Plan::query()->create([
        'name' => 'Starter stop',
        'price_cents' => 500,
        'product_limit' => 100,
        'is_active' => true,
        'is_default' => false,
    ]);

    $this->actingAs($vendor)
        ->postJson(route('plans.payments.store', $paid))
        ->assertInvalid(['plan']);

    Sleep::assertSleptTimes(1);
    expect($store->fresh()->subscription->plan_id)->toBe($freeId);
});

test('expired plans block new publishes and keep old products visible', function () {
    Http::preventStrayRequests();
    $vendor = User::factory()->create();
    $store = openStore($vendor, 'Late Tea');
    $published = Product::query()->create([
        'store_id' => $store->id,
        'name' => 'Still here',
        'slug' => 'still-here',
        'price_cents' => 100,
        'status' => ProductStatus::Published,
    ]);
    $draft = Product::query()->create([
        'store_id' => $store->id,
        'name' => 'Next',
        'slug' => 'next',
        'price_cents' => 100,
        'status' => ProductStatus::Draft,
    ]);

    $subscription = $store->subscription;
    $subscription->ends_at = now()->subDay();
    $subscription->save();

    $this->artisan('subscriptions:expire')->assertSuccessful();

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Expired);

    $this->get(route('stores.show', $store))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('products', 1)->where('products.0.name', $published->name));

    $this->actingAs($vendor)
        ->post(route('products.publish', $draft))
        ->assertInvalid(['status']);
});

test('a paid price under one cent is rejected', function () {
    $admin = User::factory()->create();
    $admin->is_admin = true;
    $admin->save();

    $this->actingAs($admin)->post(route('admin.plans.store'), [
        'name' => 'Tiny',
        'price' => '0.001',
        'product_limit' => 5,
        'is_active' => true,
        'is_default' => false,
    ])->assertInvalid(['price']);

    expect(Plan::query()->where('name', 'Tiny')->exists())->toBeFalse();
});

function pendingStarterPayment(Store $store): SubscriptionPayment
{
    $plan = Plan::query()->create([
        'name' => 'Starter '.$store->id,
        'price_cents' => 500,
        'product_limit' => 100,
        'is_active' => true,
        'is_default' => false,
    ]);

    return SubscriptionPayment::query()->create([
        'public_id' => 'subpay_test',
        'store_id' => $store->id,
        'plan_id' => $plan->id,
        'amount_cents' => 500,
        'cutluy_id' => 'pay_123',
        'status' => PaymentStatus::Pending,
    ]);
}

/**
 * A delivery in CutLuy's documented shape: the top-level id names the event,
 * and the payment lives under data.payment.
 *
 * @param  array<string, mixed>  $payment
 */
function cutluyDelivery(string $type, array $payment = []): string
{
    return json_encode([
        'id' => 'evt_'.$type,
        'type' => $type,
        'created' => now()->toIso8601String(),
        'data' => [
            'payment' => [
                'id' => 'pay_123',
                'status' => 'paid',
                'amount' => '5.00',
                'currency' => 'USD',
                'reference_id' => 'subpay_test',
                'metadata' => null,
                ...$payment,
            ],
        ],
    ], JSON_THROW_ON_ERROR);
}

function cutluySignature(string $raw, int $timestamp): string
{
    $hash = hash_hmac('sha256', $timestamp.'.'.$raw, (string) config('services.cutluy.webhook_secret'));

    return 't='.$timestamp.',v1='.$hash;
}

function cutluyCall(string $raw, string $event, ?string $signature = null, ?int $timestamp = null): TestResponse
{
    $timestamp ??= now()->getTimestamp();

    return test()->call('POST', route('webhooks.cutluy'), [], [], [], [
        'HTTP_X_CUTLUY_SIGNATURE' => $signature ?? cutluySignature($raw, $timestamp),
        'HTTP_X_CUTLUY_EVENT' => $event,
        'CONTENT_TYPE' => 'application/json',
    ], $raw);
}
