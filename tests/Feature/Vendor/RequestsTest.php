<?php

use App\Jobs\SendTelegramMessage;
use App\Models\Inquiry;
use App\Models\InquiryItem;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

function vendorRequest(Store $store, array $attributes = [], array $items = [['Jasmine', 250, 2]]): Inquiry
{
    $inquiry = Inquiry::query()->create([
        'public_id' => 'inq_'.Str::lower((string) Str::ulid()),
        'number' => (int) Inquiry::query()->where('store_id', $store->id)->max('number') + 1,
        'store_id' => $store->id,
        'customer_name' => 'Dara',
        'contact' => '@dara_buys',
        'from_cart' => false,
        ...$attributes,
    ]);

    foreach ($items as [$name, $price, $quantity]) {
        InquiryItem::query()->create(['inquiry_id' => $inquiry->id, 'name' => $name, 'price_cents' => $price, 'quantity' => $quantity]);
    }

    return $inquiry;
}

test('the telegram message is tidy html with the customer, linked items, total, and buttons', function () {
    Queue::fake();
    config(['services.telegram.admin_chat_id' => '9']);
    URL::forceRootUrl('https://vendly.example');
    $store = openStore(User::factory()->create(), 'Alex & Co');
    $store->update(['telegram_chat_id' => '-100555']);
    $product = Product::factory()->for($store)->create(['name' => 'kdfj', 'price_cents' => 400]);
    $customer = User::factory()->create(['name' => 'THAI', 'email' => 'thai@example.com', 'telegram_username' => 'srosthai2003']);

    $this->actingAs($customer)->post(route('inquiries.product', [$store, $product]))->assertRedirect();

    Queue::assertPushed(SendTelegramMessage::class, function (SendTelegramMessage $job): bool {
        if ($job->destination !== 'vendor') {
            return false;
        }

        $buttons = collect($job->options['reply_markup']['inline_keyboard'][0])->pluck('url', 'text');

        return $job->options['parse_mode'] === 'HTML'
            && str_contains($job->text, '<b>New request #1</b> · Alex &amp; Co')
            && str_contains($job->text, 'THAI · <a href="https://t.me/srosthai2003">@srosthai2003</a> · thai@example.com')
            && str_contains($job->text, '>kdfj</a>')
            && str_contains($job->text, '<b>$4.00</b>')
            && str_contains($job->text, '<b>Total $4.00</b>')
            && str_contains((string) $buttons['Open in Vendly'], '/vendor/requests?open=')
            && $buttons['Message customer'] === 'https://t.me/srosthai2003';
    });
});

test('the vendor sees only their store requests, with search and filters', function () {
    $vendor = User::factory()->create();
    $store = openStore($vendor, 'Smile Tea');
    vendorRequest($store, ['customer_name' => 'Dara']);
    vendorRequest($store, ['customer_name' => 'Vanna', 'contact' => '@vanna_shop', 'from_cart' => true, 'handled_at' => now()], [['Oolong', 420, 1], ['Cake', 180, 3]]);
    vendorRequest(openStore(User::factory()->create(), 'Other'), ['customer_name' => 'Someone']);

    $list = fn (array $query = []) => $this->actingAs($vendor)->get(route('vendor.requests', $query));

    $list()->assertOk()->assertInertia(fn ($page) => $page
        ->component('vendor/requests')
        ->has('requests.data', 2)
        ->where('newCount', 1)
        ->where('requests.data.0.customer', 'Vanna')
        ->where('requests.data.0.total_cents', 960)
        ->has('requests.data.0.items', 2));
    $list(['status' => 'new'])->assertInertia(fn ($page) => $page->has('requests.data', 1)->where('requests.data.0.customer', 'Dara'));
    $list(['kind' => 'cart'])->assertInertia(fn ($page) => $page->has('requests.data', 1)->where('requests.data.0.customer', 'Vanna'));
    $list(['search' => 'dara'])->assertInertia(fn ($page) => $page->has('requests.data', 1));
    $list(['search' => '#2'])->assertInertia(fn ($page) => $page->has('requests.data', 1)->where('requests.data.0.reference', '#2'));
});

test('a vendor marks a request handled and reopens it, only in their store', function () {
    $vendor = User::factory()->create();
    $mine = vendorRequest(openStore($vendor, 'Smile Tea'));
    $theirs = vendorRequest(openStore(User::factory()->create(), 'Other'));

    $this->actingAs($vendor)->patch(route('vendor.requests.update', $mine), ['handled' => '1'])->assertRedirect();
    expect($mine->fresh()->handled_at)->not->toBeNull();

    $this->actingAs($vendor)->patch(route('vendor.requests.update', $mine), ['handled' => '0']);
    expect($mine->fresh()->handled_at)->toBeNull();

    $this->actingAs($vendor)->patch(route('vendor.requests.update', $theirs), ['handled' => '1'])->assertNotFound();
    expect($theirs->fresh()->handled_at)->toBeNull();
});
