<?php

use App\Actions\Billing\SavePlan;
use App\Models\PaymentMethod;
use App\Models\Plan;
use App\Models\PlatformSetting;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

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
        ->assertInertia(fn ($page) => $page->component('admin/plans')->has('plans.data', 1));

    $this->actingAs($admin)->put(route('admin.telegram.update'), [
        'admin_chat_id' => '4242',
        'mini_app_short_name' => 'shop',
    ])->assertRedirect();

    $settings = PlatformSetting::current();

    expect($settings->admin_chat_id)->toBe('4242')
        ->and($settings->mini_app_short_name)->toBe('shop');

    $this->actingAs($admin)
        ->get(route('admin.payments'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/payments'));
});

function freeDefaultPlan(): Plan
{
    return Plan::query()->create([
        'name' => 'Free',
        'price_cents' => 0,
        'product_limit' => 10,
        'is_active' => true,
        'is_default' => true,
    ]);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function planInput(array $overrides = []): array
{
    return [
        'name' => 'Starter',
        'price' => '5.00',
        'product_limit' => 100,
        'is_active' => true,
        'is_default' => false,
        ...$overrides,
    ];
}

test('only a free, active plan can become the default', function (array $input, string $field) {
    $free = freeDefaultPlan();

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.plans.store'), planInput($input))
        ->assertInvalid([$field]);

    expect(Plan::query()->where('is_default', true)->pluck('id')->all())->toBe([$free->id]);
})->with([
    'a paid default' => [['is_default' => true, 'price' => '5.00'], 'is_default'],
    'an inactive default' => [['is_default' => true, 'price' => '0', 'is_active' => false], 'is_active'],
]);

test('the default plan cannot be unticked or deactivated', function () {
    $free = freeDefaultPlan();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.plans.update', $free), planInput(['name' => 'Free', 'price' => '0', 'is_default' => false]))
        ->assertInvalid(['is_default' => 'Make another free plan the default first.']);

    $this->actingAs($admin)
        ->put(route('admin.plans.update', $free), planInput(['name' => 'Free', 'price' => '0', 'is_default' => true, 'is_active' => false]))
        ->assertInvalid(['is_active']);

    expect($free->fresh()->is_default)->toBeTrue()
        ->and($free->fresh()->is_active)->toBeTrue();
});

test('making another free plan the default moves the default', function () {
    $free = freeDefaultPlan();
    $basic = Plan::query()->create([
        'name' => 'Basic',
        'price_cents' => 0,
        'product_limit' => 3,
        'is_active' => true,
        'is_default' => false,
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.plans.update', $basic), planInput(['name' => 'Basic', 'price' => '0', 'product_limit' => 3, 'is_default' => true]))
        ->assertRedirect();

    expect(Plan::query()->where('is_default', true)->pluck('id')->all())->toBe([$basic->id])
        ->and($free->fresh()->is_default)->toBeFalse();
});

test('the database keeps a single default plan', function () {
    freeDefaultPlan();

    expect(fn () => freeDefaultPlan())->toThrow(UniqueConstraintViolationException::class);

    app(SavePlan::class)->handle([
        'name' => 'Basic',
        'price_cents' => 0,
        'product_limit' => 3,
        'is_active' => true,
        'is_default' => true,
    ]);

    expect(Plan::query()->where('is_default', true)->count())->toBe(1);
});

test('an admin can edit and hide a paid plan', function () {
    freeDefaultPlan();
    $starter = Plan::query()->create([
        'name' => 'Starter',
        'price_cents' => 500,
        'product_limit' => 100,
        'is_active' => true,
        'is_default' => false,
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.plans.update', $starter), planInput(['name' => 'Starter plus', 'price' => '7.50', 'is_active' => false]))
        ->assertRedirect();

    expect($starter->fresh())
        ->name->toBe('Starter plus')
        ->price_cents->toBe(750)
        ->is_active->toBeFalse();
});

test('an oversized price or product limit is a validation error', function (array $input, string $field) {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.plans.store'), planInput($input))
        ->assertInvalid([$field]);

    expect(Plan::query()->count())->toBe(0);
})->with([
    'price over $10,000' => [['price' => '10000.01'], 'price'],
    'a huge price' => [['price' => '99999999999'], 'price'],
    'a huge product limit' => [['product_limit' => 4294967296], 'product_limit'],
]);

test('admin vendor and payment lists are paginated', function () {
    foreach (range(1, 26) as $number) {
        openStore(User::factory()->create(), 'Store '.$number);
    }

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.vendors'))
        ->assertInertia(fn ($page) => $page->has('vendors.data', 20)->where('vendors.last_page', 2)->where('vendors.total', 26));
});

test('an admin adds, publishes, edits, and deletes a testimonial', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.testimonials.store'), [
        'name' => 'Sokha',
        'role' => 'Owner, Smile Tea',
        'quote' => 'Customers send me their cart and I answer in Telegram.',
        'published' => false,
    ])->assertRedirect();

    $testimonial = Testimonial::query()->sole();
    expect($testimonial->published_at)->toBeNull();

    $this->actingAs($admin)->put(route('admin.testimonials.update', $testimonial), [
        'name' => 'Sokha',
        'role' => 'Owner, Smile Tea',
        'quote' => '<b>Customers</b> send me their cart and I answer in Telegram.',
        'published' => true,
    ])->assertRedirect();

    expect($testimonial->fresh())
        ->published_at->not->toBeNull()
        ->quote->toBe('Customers send me their cart and I answer in Telegram.');

    $this->actingAs($admin)->delete(route('admin.testimonials.destroy', $testimonial))->assertRedirect();
    expect(Testimonial::query()->count())->toBe(0);
});

test('only an admin can manage testimonials', function () {
    $vendor = User::factory()->create();

    $this->actingAs($vendor)->get(route('admin.testimonials'))->assertForbidden();
    $this->actingAs($vendor)->post(route('admin.testimonials.store'), [
        'name' => 'Fake',
        'quote' => 'A quote nobody said out loud.',
        'published' => true,
    ])->assertForbidden();

    expect(Testimonial::query()->count())->toBe(0);
});

test('an admin shows and hides a plan with the switch, but not the default plan', function () {
    $free = freePlanForSwitch();
    $starter = Plan::query()->create(['name' => 'Starter', 'price_cents' => 500, 'product_limit' => 100, 'is_active' => true, 'is_default' => false]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->patch(route('admin.plans.availability', $starter), ['is_active' => false])
        ->assertRedirect()
        ->assertSessionHasNoErrors();
    expect($starter->fresh()->is_active)->toBeFalse();

    $this->actingAs($admin)->patch(route('admin.plans.availability', $starter), ['is_active' => true])->assertRedirect();
    expect($starter->fresh()->is_active)->toBeTrue();

    $this->actingAs($admin)->patch(route('admin.plans.availability', $free), ['is_active' => false])
        ->assertInvalid(['is_active' => 'The default plan stays available']);
    expect($free->fresh()->is_active)->toBeTrue();

    $this->actingAs(User::factory()->create())->patch(route('admin.plans.availability', $starter), ['is_active' => false])->assertForbidden();
});

function freePlanForSwitch(): Plan
{
    return Plan::query()->create(['name' => 'Free', 'price_cents' => 0, 'product_limit' => 10, 'is_active' => true, 'is_default' => true]);
}

test('the telegram test message reports success and each failure clearly', function () {
    $admin = User::factory()->admin()->create();
    PlatformSetting::current()->update(['admin_chat_id' => '-100555']);

    config(['services.telegram.bot_token' => null]);
    $this->actingAs($admin)->post(route('admin.telegram.test'))
        ->assertSessionHas('telegram_test', fn (array $result): bool => $result['type'] === 'error' && str_contains($result['message'], 'Add the bot token'));

    config(['services.telegram.bot_token' => '123:ABC']);
    Http::fake(['api.telegram.org/*' => Http::sequence()
        ->push(['ok' => false, 'description' => 'Bad Request: chat not found'], 400)
        ->push(['ok' => true])]);
    $this->actingAs($admin)->post(route('admin.telegram.test'))
        ->assertSessionHas('telegram_test', fn (array $result): bool => $result['message'] === 'Telegram refused the test: Bad Request: chat not found');

    $this->actingAs($admin)->post(route('admin.telegram.test'))
        ->assertSessionHas('telegram_test', fn (array $result): bool => $result['type'] === 'success');

    Http::assertSent(fn ($request): bool => $request['chat_id'] === '-100555' && str_contains($request['text'], 'Vendly test message'));
});

test('only an admin can send a telegram test', function () {
    $this->actingAs(User::factory()->create())->post(route('admin.telegram.test'))->assertForbidden();
});

test('an admin saves the site footer details and they reach the website', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->put(route('admin.site.update'), [
        'company_name' => 'Vendly Co',
        'address' => 'Street 240, Phnom Penh',
        'phone' => '+855 12 345 678',
        'email' => 'hello@vendly.example',
        'footer_text' => '<b>Small shops</b> on the web.',
        'social_links' => ['facebook' => 'https://facebook.com/vendly', 'tiktok' => ''],
    ])->assertSessionHasNoErrors();

    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->where('site.company_name', 'Vendly Co')
            ->where('site.address', 'Street 240, Phnom Penh')
            ->where('site.footer_text', 'Small shops on the web.')
            ->where('site.socials', ['facebook' => 'https://facebook.com/vendly']));
});

test('site settings reject bad links and phones', function () {
    $this->actingAs(User::factory()->admin()->create())->put(route('admin.site.update'), [
        'phone' => 'call us',
        'social_links' => ['instagram' => 'http://insecure.example', 'youtube' => 'not a link'],
    ])->assertInvalid(['phone', 'social_links.instagram', 'social_links.youtube']);
});

test('an admin adds, edits, and removes accepted payment methods with logos', function () {
    Storage::fake('public');
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.site.payment-methods.store'), [
        'name' => 'ABA',
        'sort' => 1,
        'logo' => UploadedFile::fake()->image('aba.png', 120, 40),
    ])->assertSessionHasNoErrors();
    $this->actingAs($admin)->post(route('admin.site.payment-methods.store'), ['name' => 'Wing', 'sort' => 2]);

    $aba = PaymentMethod::query()->where('name', 'ABA')->sole();
    Storage::disk('public')->assertExists($aba->logo_path);

    $this->get(route('pricing'))
        ->assertInertia(fn ($page) => $page
            ->where('site.payment_methods.0.name', 'ABA')
            ->where('site.payment_methods.1.name', 'Wing')
            ->where('site.payment_methods.1.logo', null));

    $logo = $aba->logo_path;
    $this->actingAs($admin)->delete(route('admin.site.payment-methods.destroy', $aba))->assertRedirect();

    Storage::disk('public')->assertMissing($logo);
    expect(PaymentMethod::query()->pluck('name')->all())->toBe(['Wing']);
});

test('only an admin can change site settings', function () {
    $vendor = User::factory()->create();

    $this->actingAs($vendor)->get(route('admin.site'))->assertForbidden();
    $this->actingAs($vendor)->put(route('admin.site.update'), ['company_name' => 'Hijack'])->assertForbidden();
    $this->actingAs($vendor)->post(route('admin.site.payment-methods.store'), ['name' => 'Fake'])->assertForbidden();
});

test('an admin sets and clears a yearly price, and a free plan cannot have one', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.plans.store'), planInput(['name' => 'Yearly', 'yearly_price' => '50.00']))
        ->assertSessionHasNoErrors();

    $plan = Plan::query()->where('name', 'Yearly')->sole();
    expect($plan->yearly_price_cents)->toBe(5000);

    $this->actingAs($admin)
        ->put(route('admin.plans.update', $plan), planInput(['name' => 'Yearly', 'yearly_price' => '']))
        ->assertSessionHasNoErrors();

    expect($plan->fresh()->yearly_price_cents)->toBeNull();

    $this->actingAs($admin)
        ->post(route('admin.plans.store'), planInput(['name' => 'Free yearly', 'price' => '0', 'yearly_price' => '10.00']))
        ->assertInvalid(['yearly_price']);

    $this->actingAs($admin)
        ->post(route('admin.plans.store'), planInput(['name' => 'Zero yearly', 'yearly_price' => '0']))
        ->assertInvalid(['yearly_price']);
});
