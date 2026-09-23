<?php

use App\Enums\PaymentStatus;
use App\Models\Inquiry;
use App\Models\Plan;
use App\Models\Product;
use App\Models\SubscriptionPayment;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Support\Str;

function listAdmin(): User
{
    return User::factory()->admin()->create();
}

function listPayment(int $storeId, int $planId, PaymentStatus $status, int $amount, string $createdAt): SubscriptionPayment
{
    $payment = SubscriptionPayment::query()->create([
        'public_id' => 'subpay_'.Str::lower((string) Str::ulid()),
        'store_id' => $storeId,
        'plan_id' => $planId,
        'amount_cents' => $amount,
        'status' => $status,
    ]);
    $payment->forceFill(['created_at' => $createdAt])->save();

    return $payment;
}

test('the vendors list searches by store, link, and owner', function () {
    openStore(User::factory()->create(['email' => 'sokha@example.com']), 'Smile Tea');
    openStore(User::factory()->create(), 'Jozen Shoes');

    $this->actingAs(listAdmin())
        ->get(route('admin.vendors', ['search' => 'jozen']))
        ->assertInertia(fn ($page) => $page->has('vendors.data', 1)->where('vendors.data.0.name', 'Jozen Shoes')->where('filters.search', 'jozen'));

    $this->actingAs(listAdmin())
        ->get(route('admin.vendors', ['search' => 'sokha@']))
        ->assertInertia(fn ($page) => $page->has('vendors.data', 1)->where('vendors.data.0.name', 'Smile Tea'));
});

test('the vendors list filters by status, plan, and telegram and sorts', function () {
    $live = openStore(User::factory()->create(), 'Bravo Tea');
    $suspended = openStore(User::factory()->create(), 'Alpha Shop');
    $suspended->forceFill(['suspended_at' => now(), 'telegram_chat_id' => '42'])->save();
    Product::factory()->for($live)->count(2)->create();
    $paid = Plan::query()->create(['name' => 'Starter', 'price_cents' => 500, 'product_limit' => 100, 'is_active' => true, 'is_default' => false]);
    $live->subscription->update(['plan_id' => $paid->id]);

    $admin = listAdmin();

    $this->actingAs($admin)->get(route('admin.vendors', ['status' => 'suspended']))
        ->assertInertia(fn ($page) => $page->has('vendors.data', 1)->where('vendors.data.0.name', 'Alpha Shop'));
    $this->actingAs($admin)->get(route('admin.vendors', ['plan' => (string) $paid->id]))
        ->assertInertia(fn ($page) => $page->has('vendors.data', 1)->where('vendors.data.0.name', 'Bravo Tea'));
    $this->actingAs($admin)->get(route('admin.vendors', ['telegram' => 'connected']))
        ->assertInertia(fn ($page) => $page->has('vendors.data', 1)->where('vendors.data.0.name', 'Alpha Shop'));
    $this->actingAs($admin)->get(route('admin.vendors', ['sort' => 'name']))
        ->assertInertia(fn ($page) => $page->where('vendors.data.0.name', 'Alpha Shop'));
    $this->actingAs($admin)->get(route('admin.vendors', ['sort' => 'products']))
        ->assertInertia(fn ($page) => $page->where('vendors.data.0.name', 'Bravo Tea'));
});

test('unknown list values fall back to the defaults', function () {
    openStore(User::factory()->create(), 'Smile Tea');

    $this->actingAs(listAdmin())
        ->get(route('admin.vendors', ['status' => 'everything', 'plan' => '999', 'sort' => 'random']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('filters.status', 'all')
            ->where('filters.plan', 'all')
            ->where('filters.sort', 'newest')
            ->has('vendors.data', 1));
});

test('the plans list filters by availability and price and counts active stores', function () {
    openStore(User::factory()->create(), 'Smile Tea');
    Plan::query()->create(['name' => 'Hidden Pro', 'price_cents' => 900, 'product_limit' => 300, 'is_active' => false, 'is_default' => false]);
    Plan::query()->create(['name' => 'Starter', 'price_cents' => 500, 'product_limit' => 100, 'is_active' => true, 'is_default' => false]);

    $admin = listAdmin();

    $this->actingAs($admin)->get(route('admin.plans', ['availability' => 'hidden']))
        ->assertInertia(fn ($page) => $page->has('plans.data', 1)->where('plans.data.0.name', 'Hidden Pro'));
    $this->actingAs($admin)->get(route('admin.plans', ['price' => 'free']))
        ->assertInertia(fn ($page) => $page->has('plans.data', 1)->where('plans.data.0.name', 'Free')->where('plans.data.0.stores_count', 1));
    $this->actingAs($admin)->get(route('admin.plans', ['sort' => 'price']))
        ->assertInertia(fn ($page) => $page->where('plans.data.0.name', 'Free')->where('plans.data.2.name', 'Hidden Pro'));
    $this->actingAs($admin)->get(route('admin.plans', ['search' => 'start']))
        ->assertInertia(fn ($page) => $page->has('plans.data', 1));
});

test('the payments list searches, filters by status and dates, and sorts by amount', function () {
    $tea = openStore(User::factory()->create(), 'Smile Tea');
    $shoes = openStore(User::factory()->create(), 'Jozen Shoes');
    $plan = Plan::query()->create(['name' => 'Starter', 'price_cents' => 500, 'product_limit' => 100, 'is_active' => true, 'is_default' => false]);

    listPayment($tea->id, $plan->id, PaymentStatus::Paid, 500, '2026-09-01 10:00:00');
    $reference = listPayment($tea->id, $plan->id, PaymentStatus::Pending, 5000, '2026-09-10 10:00:00')->public_id;
    listPayment($shoes->id, $plan->id, PaymentStatus::Paid, 700, '2026-09-20 10:00:00');

    $admin = listAdmin();

    $this->actingAs($admin)->get(route('admin.payments', ['status' => 'paid']))
        ->assertInertia(fn ($page) => $page->has('payments.data', 2));
    $this->actingAs($admin)->get(route('admin.payments', ['search' => 'jozen']))
        ->assertInertia(fn ($page) => $page->has('payments.data', 1)->where('payments.data.0.store', 'Jozen Shoes'));
    $this->actingAs($admin)->get(route('admin.payments', ['search' => $reference]))
        ->assertInertia(fn ($page) => $page->has('payments.data', 1)->where('payments.data.0.amount_cents', 5000));
    $this->actingAs($admin)->get(route('admin.payments', ['from' => '2026-09-05', 'to' => '2026-09-15']))
        ->assertInertia(fn ($page) => $page->has('payments.data', 1)->where('payments.data.0.reference', $reference));
    $this->actingAs($admin)->get(route('admin.payments', ['sort' => 'amount']))
        ->assertInertia(fn ($page) => $page->where('payments.data.0.amount_cents', 5000));
    $this->actingAs($admin)->get(route('admin.payments', ['from' => '2026-09-15', 'to' => '2026-09-01']))
        ->assertInvalid(['to']);
});

test('the requests list searches and filters by store and kind, and keeps the old undelivered link', function () {
    $tea = openStore(User::factory()->create(), 'Smile Tea');
    $shoes = openStore(User::factory()->create(), 'Jozen Shoes');
    Inquiry::query()->create(['public_id' => 'inq_a', 'number' => 1, 'store_id' => $tea->id, 'customer_name' => 'Dara', 'from_cart' => true, 'admin_notified_at' => now()]);
    Inquiry::query()->create(['public_id' => 'inq_b', 'number' => 1, 'store_id' => $shoes->id, 'customer_name' => 'Vanna', 'from_cart' => false]);

    $admin = listAdmin();

    $this->actingAs($admin)->get(route('admin.requests', ['search' => 'vanna']))
        ->assertInertia(fn ($page) => $page->has('inquiries.data', 1)->where('inquiries.data.0.customer', 'Vanna'));
    $this->actingAs($admin)->get(route('admin.requests', ['store' => (string) $tea->id]))
        ->assertInertia(fn ($page) => $page->has('inquiries.data', 1)->where('inquiries.data.0.store', 'Smile Tea'));
    $this->actingAs($admin)->get(route('admin.requests', ['channel' => 'cart']))
        ->assertInertia(fn ($page) => $page->has('inquiries.data', 1)->where('inquiries.data.0.customer', 'Dara'));
    $this->actingAs($admin)->get(route('admin.requests', ['filter' => 'undelivered']))
        ->assertInertia(fn ($page) => $page->where('filters.delivery', 'undelivered')->has('inquiries.data', 1)->where('inquiries.data.0.customer', 'Vanna'));
});

test('the testimonials list searches and filters by status', function () {
    Testimonial::factory()->create(['name' => 'Sokha', 'published_at' => now()]);
    Testimonial::factory()->create(['name' => 'Dara', 'published_at' => null]);

    $admin = listAdmin();

    $this->actingAs($admin)->get(route('admin.testimonials', ['status' => 'draft']))
        ->assertInertia(fn ($page) => $page->has('testimonials.data', 1)->where('testimonials.data.0.name', 'Dara'));
    $this->actingAs($admin)->get(route('admin.testimonials', ['search' => 'sokha']))
        ->assertInertia(fn ($page) => $page->has('testimonials.data', 1));
});

test('list filters are for admins only', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.payments', ['status' => 'paid']))
        ->assertForbidden();
});
