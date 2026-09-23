<?php

use App\Actions\Billing\ApplyCutluyEvent;
use App\Actions\Billing\CreatePlanPayment;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\SubscriptionStatus;
use App\Jobs\ApplyCutluyWebhook;
use App\Jobs\RetryCutluyPayment;
use App\Jobs\SendTelegramMessage;
use App\Models\CutluyEvent;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Store;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
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

function starterPlan(string $name = 'Starter'): Plan
{
    return Plan::query()->create([
        'name' => $name,
        'price_cents' => 500,
        'product_limit' => 100,
        'is_active' => true,
        'is_default' => false,
    ]);
}

function createdCutluyPayment(string $id = 'pay_429'): array
{
    return [
        'id' => $id,
        'status' => 'pending',
        'checkout_url' => 'https://cutluy.com/pay/'.$id,
        'qr_string' => '000201',
    ];
}

test('a rate limit asks the vendor to wait and queues one delayed retry', function () {
    Http::preventStrayRequests();
    Queue::fake();
    Http::fakeSequence()
        ->push(['error' => 'rate_limited', 'message' => 'slow'], 429, ['Retry-After' => '3'])
        ->push(createdCutluyPayment(), 201);

    $vendor = User::factory()->create();
    openStore($vendor, 'Rate Tea');
    $paid = starterPlan();

    $this->actingAs($vendor)
        ->postJson(route('plans.payments.store', $paid))
        ->assertInvalid(['plan' => 'Try again in 3 seconds.']);

    Http::assertSentCount(1);
    Queue::assertPushed(RetryCutluyPayment::class, fn (RetryCutluyPayment $job): bool => $job->delay !== null);
    Queue::assertNotPushed(SendTelegramMessage::class, fn (SendTelegramMessage $job): bool => str_contains($job->text, 'unavailable'));

    Queue::pushed(RetryCutluyPayment::class)->first()->handle(app(CreatePlanPayment::class));

    $payment = SubscriptionPayment::query()->sole();
    expect($payment->cutluy_id)->toBe('pay_429');
    Http::assertSent(fn (Request $request): bool => $request['idempotency_key'] === $request['reference_id']
        && $request['reference_id'] === $payment->public_id
        && $request['amount'] === 5.0);
});

test('a second rate limit stops without another retry', function () {
    Http::preventStrayRequests();
    Queue::fake();
    Http::fakeSequence()
        ->push(['error' => 'rate_limited'], 429, ['Retry-After' => '2'])
        ->push(['error' => 'rate_limited'], 429, ['Retry-After' => '9']);

    $vendor = User::factory()->create();
    $store = openStore($vendor, 'Stop Tea');
    $freeId = $store->subscription->plan_id;

    $this->actingAs($vendor)->postJson(route('plans.payments.store', starterPlan()))->assertInvalid(['plan']);

    Queue::pushed(RetryCutluyPayment::class)->first()->handle(app(CreatePlanPayment::class));

    Queue::assertPushed(RetryCutluyPayment::class, 1);
    Queue::assertNotPushed(SendTelegramMessage::class, fn (SendTelegramMessage $job): bool => str_contains($job->text, 'unavailable'));
    expect($store->fresh()->subscription->plan_id)->toBe($freeId)
        ->and(SubscriptionPayment::query()->sole()->cutluy_id)->toBeNull();
});

test('retry-after is capped and understands http dates', function (string $header, int $seconds) {
    Http::preventStrayRequests();
    Queue::fake();
    $this->freezeTime();
    Http::fake(['cutluy.com/*' => Http::response(['error' => 'rate_limited'], 429, ['Retry-After' => $header])]);

    $vendor = User::factory()->create();
    openStore($vendor, 'Date Tea');

    $this->actingAs($vendor)
        ->postJson(route('plans.payments.store', starterPlan()))
        ->assertInvalid(['plan' => 'Try again in '.$seconds.' seconds.']);
})->with([
    'a long wait' => ['600', 60],
    'an http date' => [fn (): string => now()->addSeconds(20)->toRfc7231String(), 20],
    'garbage' => ['soon', 1],
]);

test('cutluy outages never show the raw error and tell the admin', function (?int $status) {
    Http::preventStrayRequests();
    Queue::fake();
    Http::fake([
        'cutluy.com/*' => $status === null
            ? fn () => throw new ConnectionException('Could not resolve host')
            : Http::response(['error' => 'server_error', 'message' => 'secret detail'], $status),
    ]);

    $vendor = User::factory()->create();
    $store = openStore($vendor, 'Down Tea');
    $freeId = $store->subscription->plan_id;

    $this->actingAs($vendor)
        ->postJson(route('plans.payments.store', starterPlan()))
        ->assertInvalid(['plan' => 'Payments are unavailable.'])
        ->assertDontSee('secret detail');

    Queue::assertPushed(SendTelegramMessage::class, fn (SendTelegramMessage $job): bool => str_contains($job->text, 'CutLuy payments are unavailable'));
    expect($store->fresh()->subscription->plan_id)->toBe($freeId);
})->with([
    'unauthorized' => [401],
    'quota exceeded' => [402],
    'account suspended' => [403],
    'server error' => [500],
    'no connection' => [null],
]);

test('trying again reuses the same local payment and idempotency key', function () {
    Http::preventStrayRequests();
    Queue::fake();
    Http::fakeSequence()
        ->push(['error' => 'server_error'], 503)
        ->push(createdCutluyPayment('pay_again'), 201);

    $vendor = User::factory()->create();
    openStore($vendor, 'Again Tea');
    $paid = starterPlan();

    $this->actingAs($vendor)->postJson(route('plans.payments.store', $paid))->assertInvalid(['plan']);
    $this->actingAs($vendor)->postJson(route('plans.payments.store', $paid))->assertOk();
    $this->actingAs($vendor)->postJson(route('plans.payments.store', $paid))->assertOk()->assertJsonPath('checkout_url', 'https://cutluy.com/pay/pay_again');

    $payment = SubscriptionPayment::query()->sole();
    $keys = collect(Http::recorded())->map(fn (array $pair): string => $pair[0]['idempotency_key'])->unique()->values()->all();

    expect($keys)->toBe([$payment->public_id])
        ->and($payment->cutluy_id)->toBe('pay_again');
    Http::assertSentCount(2);
});

test('completed adds a month on top of a future end date and reactivates an expired plan', function () {
    Http::preventStrayRequests();
    $this->freezeSecond();
    $store = openStore(User::factory()->create(), 'Renew Tea');
    $payment = pendingStarterPayment($store);

    $subscription = $store->subscription;
    $subscription->plan_id = $payment->plan_id;
    $subscription->ends_at = now()->addDays(10);
    $subscription->status = SubscriptionStatus::Expired;
    $subscription->save();

    cutluyCall(cutluyDelivery('payment.completed'), 'payment.completed')->assertNoContent();

    expect($subscription->fresh())
        ->status->toBe(SubscriptionStatus::Active)
        ->ends_at->toEqual(now()->addDays(10)->addMonth());
});

test('an event that arrives before the cutluy id is stored still applies', function () {
    Http::preventStrayRequests();
    $store = openStore(User::factory()->create(), 'Early Tea');
    $payment = pendingStarterPayment($store);
    $payment->update(['cutluy_id' => null]);

    cutluyCall(cutluyDelivery('payment.completed'), 'payment.completed')->assertNoContent();

    expect($payment->fresh())
        ->cutluy_id->toBe('pay_123')
        ->status->toBe(PaymentStatus::Paid);
});

test('an event for an unknown payment is not recorded so a retry can apply it', function () {
    expect(fn () => app(ApplyCutluyEvent::class)->handle('payment.completed', json_decode(cutluyDelivery('payment.completed', ['id' => 'pay_unknown', 'reference_id' => 'subpay_unknown']), true)))
        ->toThrow(RuntimeException::class);

    expect(CutluyEvent::query()->count())->toBe(0);
});

test('a renewal that lands during the expiry scan keeps the plan active', function () {
    Http::preventStrayRequests();
    $store = openStore(User::factory()->create(), 'Race Tea');
    $subscription = $store->subscription;
    $subscription->ends_at = now()->subDay();
    $subscription->save();

    $renewed = false;
    Subscription::retrieved(function (Subscription $candidate) use (&$renewed): void {
        if ($renewed) {
            return;
        }

        $renewed = true;
        DB::table('subscriptions')->where('id', $candidate->id)->update(['ends_at' => now()->addMonth()]);
    });

    $this->artisan('subscriptions:expire')->assertSuccessful();

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Active);
});

test('expiry tells the vendor and the admin', function () {
    Queue::fake();
    config(['services.telegram.admin_chat_id' => '9']);
    $store = openStore(User::factory()->create(), 'Ended Tea');
    config(['services.telegram.admin_chat_id' => '9']);
    $store->update(['telegram_chat_id' => '77']);
    $subscription = $store->subscription;
    $subscription->ends_at = now()->subDay();
    $subscription->save();

    $this->artisan('subscriptions:expire')->assertSuccessful();

    Queue::assertPushed(SendTelegramMessage::class, fn (SendTelegramMessage $job): bool => $job->chatId === '9');
    Queue::assertPushed(SendTelegramMessage::class, fn (SendTelegramMessage $job): bool => $job->chatId === '77');
});

test('payment creation is rate limited per vendor', function () {
    Http::preventStrayRequests();
    Http::fake(['cutluy.com/*' => Http::response(createdCutluyPayment(), 201)]);
    $vendor = User::factory()->create();
    openStore($vendor, 'Busy Tea');
    $paid = starterPlan();

    for ($attempt = 0; $attempt < 10; $attempt++) {
        $this->actingAs($vendor)->postJson(route('plans.payments.store', $paid))->assertOk();
    }

    $this->actingAs($vendor)->postJson(route('plans.payments.store', $paid))->assertTooManyRequests();
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
        ->assertInertia(fn ($page) => $page->has('products.data', 1)->where('products.data.0.name', $published->name));

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

test('the payment dialog reads its status and refresh applies a paid payment once', function () {
    Http::preventStrayRequests();
    $vendor = User::factory()->create();
    $store = openStore($vendor, 'Status Tea');
    $payment = pendingStarterPayment($store);
    Http::fake(['cutluy.com/v1/payments/pay_123' => Http::response([
        'id' => 'pay_123',
        'status' => 'paid',
        'amount' => '5.00',
        'currency' => 'USD',
        'reference_id' => 'subpay_test',
    ])]);

    $this->actingAs($vendor)
        ->getJson(route('vendor.plan.payments.show', $payment->public_id))
        ->assertOk()
        ->assertJsonPath('status', 'pending');
    Http::assertNothingSent();

    $this->actingAs($vendor)
        ->getJson(route('vendor.plan.payments.show', ['publicId' => $payment->public_id, 'refresh' => 1]))
        ->assertOk()
        ->assertJsonPath('status', 'paid')
        ->assertJsonPath('notice', null);

    Http::assertSentCount(1);
    expect($store->fresh()->subscription->plan_id)->toBe($payment->plan_id);

    cutluyCall(cutluyDelivery('payment.completed'), 'payment.completed')->assertNoContent();
    expect($store->fresh()->subscription->ends_at?->lessThan(now()->addMonth()->addDay()))->toBeTrue();
});

test('refresh shows a scanned payment as opened, never paid', function () {
    Http::preventStrayRequests();
    $vendor = User::factory()->create();
    $store = openStore($vendor, 'Scan Tea');
    $freeId = $store->subscription->plan_id;
    $payment = pendingStarterPayment($store);
    Http::fake(['cutluy.com/*' => Http::response(['id' => 'pay_123', 'status' => 'scanned', 'amount' => '5.00', 'currency' => 'USD', 'reference_id' => 'subpay_test'])]);

    $this->actingAs($vendor)
        ->getJson(route('vendor.plan.payments.show', ['publicId' => $payment->public_id, 'refresh' => 1]))
        ->assertJsonPath('status', 'scanned');

    expect($store->fresh()->subscription->plan_id)->toBe($freeId);
});

test('refresh stops on a rate limit and tells the vendor how long to wait', function () {
    Http::preventStrayRequests();
    $vendor = User::factory()->create();
    $payment = pendingStarterPayment(openStore($vendor, 'Busy Status Tea'));
    Http::fake(['cutluy.com/*' => Http::response(['error' => 'rate_limited'], 429, ['Retry-After' => '7'])]);

    $this->actingAs($vendor)
        ->getJson(route('vendor.plan.payments.show', ['publicId' => $payment->public_id, 'refresh' => 1]))
        ->assertOk()
        ->assertJsonPath('status', 'pending')
        ->assertJsonPath('notice', 'CutLuy is busy. Try again in 7 seconds.');

    Http::assertSentCount(1);
});

test('a vendor cannot read another store\'s payment', function () {
    $payment = pendingStarterPayment(openStore(User::factory()->create(), 'Owner Tea'));
    $other = User::factory()->create();
    openStore($other, 'Other Tea');

    $this->actingAs($other)
        ->getJson(route('vendor.plan.payments.show', $payment->public_id))
        ->assertNotFound();
});

test('the plan page shows an expired plan before a publish fails', function () {
    $vendor = User::factory()->create();
    $store = openStore($vendor, 'Late Plan Tea');
    $subscription = $store->subscription;
    $subscription->status = SubscriptionStatus::Expired;
    $subscription->ends_at = now()->subDay();
    $subscription->save();

    $this->actingAs($vendor)
        ->get(route('vendor.plan'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('usage.status', 'expired')
            ->where('usage.can_publish', false)
            ->where('usage.ends_at', $subscription->ends_at->toIso8601String()));
});

test('a yearly checkout charges the yearly price and records the period', function () {
    Http::preventStrayRequests();
    Http::fakeSequence()
        ->push(createdCutluyPayment('pay_month'), 201)
        ->push(createdCutluyPayment('pay_year'), 201);

    $vendor = User::factory()->create();
    openStore($vendor, 'Year Tea');
    $plan = starterPlan();
    $plan->update(['yearly_price_cents' => 5000]);

    $this->actingAs($vendor)->postJson(route('plans.payments.store', $plan))
        ->assertOk()
        ->assertJsonPath('amount_cents', 500)
        ->assertJsonPath('period', 'monthly');

    $this->actingAs($vendor)->postJson(route('plans.payments.store', $plan), ['period' => 'yearly'])
        ->assertOk()
        ->assertJsonPath('amount_cents', 5000)
        ->assertJsonPath('period', 'yearly');

    expect(SubscriptionPayment::query()->where('period', 'yearly')->sole()->cutluy_id)->toBe('pay_year');
    Http::assertSent(fn ($request) => (float) $request['amount'] === 50.0 && $request['metadata']['period'] === 'yearly');
});

test('yearly is refused for a plan without a yearly price', function () {
    Http::preventStrayRequests();
    $vendor = User::factory()->create();
    openStore($vendor, 'Monthly Tea');
    $plan = starterPlan();

    $this->actingAs($vendor)->postJson(route('plans.payments.store', $plan), ['period' => 'yearly'])
        ->assertInvalid(['plan']);
    $this->actingAs($vendor)->postJson(route('plans.payments.store', $plan), ['period' => 'weekly'])
        ->assertInvalid(['period']);

    expect(SubscriptionPayment::query()->count())->toBe(0);
});

test('a completed yearly payment adds twelve months', function () {
    Http::preventStrayRequests();
    $this->freezeSecond();
    $store = openStore(User::factory()->create(), 'Twelve Tea');
    $payment = pendingStarterPayment($store);
    $payment->update(['amount_cents' => 5000, 'period' => 'yearly']);

    cutluyCall(cutluyDelivery('payment.completed', ['amount' => '50.00']), 'payment.completed')->assertNoContent();

    expect($store->fresh()->subscription)
        ->status->toBe(SubscriptionStatus::Active)
        ->ends_at->toEqual(now()->addYear());
});
