<?php

use App\Enums\PaymentStatus;
use App\Models\Inquiry;
use App\Models\InquiryItem;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Store;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

function chartPayment(Store $store, Plan $plan, PaymentStatus $status, int $amount, ?CarbonInterface $paidAt, ?CarbonInterface $createdAt = null): void
{
    $payment = SubscriptionPayment::query()->create([
        'public_id' => 'subpay_'.Str::lower((string) Str::ulid()),
        'store_id' => $store->id,
        'plan_id' => $plan->id,
        'amount_cents' => $amount,
        'status' => $status,
        'paid_at' => $paidAt,
    ]);
    $payment->forceFill(['created_at' => $createdAt ?? now()])->save();
}

function chartInquiry(Store $store, array $attributes = [], ?CarbonInterface $at = null): Inquiry
{
    $inquiry = Inquiry::query()->create([
        'public_id' => 'inq_'.Str::lower((string) Str::ulid()),
        'number' => (int) Inquiry::query()->where('store_id', $store->id)->max('number') + 1,
        'store_id' => $store->id,
        'customer_name' => 'Dara',
        'from_cart' => false,
        ...$attributes,
    ]);
    $inquiry->forceFill(['created_at' => $at ?? now()])->save();

    return $inquiry;
}

test('the admin charts sum real payments, vendors, and deliveries by day', function () {
    $this->freezeSecond();
    $store = openStore(User::factory()->create(), 'Smile Tea');
    $plan = Plan::query()->create(['name' => 'Starter', 'price_cents' => 500, 'product_limit' => 100, 'is_active' => true, 'is_default' => false]);

    chartPayment($store, $plan, PaymentStatus::Paid, 500, now());
    chartPayment($store, $plan, PaymentStatus::Paid, 700, now());
    chartPayment($store, $plan, PaymentStatus::Paid, 300, now()->subDays(3));
    chartPayment($store, $plan, PaymentStatus::Paid, 900, now()->subDays(40));
    chartPayment($store, $plan, PaymentStatus::Pending, 500, null);

    chartInquiry($store, ['vendor_notified_at' => now()]);
    chartInquiry($store, ['vendor_error' => 'Forbidden']);
    chartInquiry($store);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->component('admin/overview')
            ->has('charts.days', 30)
            ->where('charts.days.29', now()->toDateString())
            ->where('charts.revenue.values.29', 1200)
            ->where('charts.revenue.values.26', 300)
            ->where('charts.revenue.total', 1500)
            ->where('charts.revenue.previous', 900)
            ->where('charts.vendors.total', 1)
            ->where('charts.vendors.values.29', 1)
            ->where('charts.payments_by_status', [['key' => 'pending', 'value' => 1], ['key' => 'paid', 'value' => 4]])
            ->where('charts.delivery', [
                ['key' => 'delivered', 'value' => 1],
                ['key' => 'failed', 'value' => 1],
                ['key' => 'admin_only', 'value' => 1],
            ]));
});

test('the vendor charts show only their store', function () {
    $this->freezeSecond();
    $vendor = User::factory()->create();
    $store = openStore($vendor, 'Smile Tea', 50);
    $other = openStore(User::factory()->create(), 'Other Tea');

    Product::factory()->for($store)->count(2)->create();
    Product::factory()->for($store)->soldOut()->create();
    Product::factory()->for($store)->draft()->create();

    $today = chartInquiry($store);
    InquiryItem::query()->create(['inquiry_id' => $today->id, 'name' => 'Jasmine', 'price_cents' => 250, 'quantity' => 3]);
    InquiryItem::query()->create(['inquiry_id' => $today->id, 'name' => 'Oolong', 'price_cents' => 420, 'quantity' => 1]);
    $old = chartInquiry($store, [], now()->subDays(45));
    InquiryItem::query()->create(['inquiry_id' => $old->id, 'name' => 'Oolong', 'price_cents' => 420, 'quantity' => 9]);
    $theirs = chartInquiry($other);
    InquiryItem::query()->create(['inquiry_id' => $theirs->id, 'name' => 'Their tea', 'price_cents' => 100, 'quantity' => 20]);
    chartInquiry($store, [], now()->subDays(35));

    $this->actingAs($vendor)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->component('vendor/overview')
            ->where('charts.requests.total', 1)
            ->where('charts.requests.values.29', 1)
            ->where('charts.requests.previous', 2)
            ->where('charts.products_by_status', [
                ['key' => 'published', 'value' => 2],
                ['key' => 'sold_out', 'value' => 1],
                ['key' => 'draft', 'value' => 1],
            ])
            ->where('charts.top_products', [
                ['name' => 'Jasmine', 'value' => 3],
                ['name' => 'Oolong', 'value' => 1],
            ]));
});
