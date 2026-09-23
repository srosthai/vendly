<?php

use App\Actions\Stores\CreateStore;
use App\Models\Plan;
use App\Models\Store;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('the home page offers sign in and start selling', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('marketing/home'));
});

test('sign in is the email and password form', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('auth/login'));
});

test('start selling requires an account and creates one store', function () {
    $this->get(route('selling.create'))->assertRedirect(route('login'));

    Plan::query()->create([
        'name' => 'Free',
        'price_cents' => 0,
        'product_limit' => 10,
        'is_active' => true,
        'is_default' => true,
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('selling.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('selling/create'));

    $this->actingAs($user)
        ->post(route('stores.store'), ['name' => 'Smile Tea'])
        ->assertRedirect();

    $this->actingAs($user)
        ->get(route('selling.create'))
        ->assertRedirect(route('dashboard'));
});

function freePlan(): void
{
    Plan::query()->create([
        'name' => 'Free',
        'price_cents' => 0,
        'product_limit' => 10,
        'is_active' => true,
        'is_default' => true,
    ]);
}

test('a vendor can choose the store link and it works on the web', function () {
    freePlan();
    $vendor = User::factory()->create();

    $this->actingAs($vendor)
        ->post(route('stores.store'), ['name' => 'Smile Tea', 'slug' => 'smile'])
        ->assertRedirect(route('stores.show', 'smile'));

    $this->get('/s/smile')->assertOk();
});

test('a khmer-only store name opens a store with a generated link', function () {
    freePlan();
    $vendor = User::factory()->create();

    $this->actingAs($vendor)->post(route('stores.store'), ['name' => 'ហាងតែ'])->assertRedirect();

    expect($vendor->store->slug)->toStartWith('store-')
        ->and($vendor->store->name)->toBe('ហាងតែ');
});

test('store links must be short, well formed, free, and not reserved', function (string $slug, string $message) {
    freePlan();
    $taken = User::factory()->create();
    $this->actingAs($taken)->post(route('stores.store'), ['name' => 'Taken', 'slug' => 'taken-tea']);
    auth()->logout();

    $this->actingAs(User::factory()->create())
        ->post(route('stores.store'), ['name' => 'Smile Tea', 'slug' => $slug])
        ->assertInvalid(['slug' => $message]);
})->with([
    'too long' => [str_repeat('a', 65), 'must not be greater than 64'],
    'too short' => ['ab', 'must be at least 3'],
    'uppercase and spaces' => ['Smile Tea', 'lowercase letters'],
    'reserved' => ['admin', 'reserved'],
    'taken' => ['taken-tea', 'already uses that link'],
]);

test('a long store name gets a link Telegram accepts', function () {
    freePlan();
    $vendor = User::factory()->create();

    $this->actingAs($vendor)->post(route('stores.store'), ['name' => str_repeat('Green tea house ', 10)])->assertRedirect();

    expect(strlen($vendor->store->slug))->toBeLessThanOrEqual(64);
});

test('a name that turns into a reserved link gets another one', function () {
    freePlan();
    $vendor = User::factory()->create();

    $this->actingAs($vendor)->post(route('stores.store'), ['name' => 'Admin'])->assertRedirect();

    expect($vendor->store->slug)->toBe('admin-shop');
});

test('a slug race ends in a validation error, not a server error', function () {
    freePlan();
    $vendor = User::factory()->create();

    Store::creating(function (Store $store): void {
        if ($store->slug === 'raced') {
            DB::table('stores')->insert([
                'user_id' => User::factory()->create()->id,
                'name' => 'First',
                'slug' => 'raced',
                'currency' => 'USD',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    });

    $this->actingAs($vendor)
        ->post(route('stores.store'), ['name' => 'Second', 'slug' => 'raced'])
        ->assertInvalid(['slug' => 'already uses that link']);

    expect($vendor->store()->exists())->toBeFalse();
});

test('the pricing page lists the plans vendors can choose, free default first', function () {
    freePlan();
    Plan::query()->create(['name' => 'Starter', 'price_cents' => 500, 'product_limit' => 100, 'is_active' => true, 'is_default' => false]);
    Plan::query()->create(['name' => 'Hidden', 'price_cents' => 900, 'product_limit' => 500, 'is_active' => false, 'is_default' => false]);

    $this->get(route('pricing'))
        ->assertInertia(fn ($page) => $page
            ->component('marketing/pricing')
            ->has('plans', 2)
            ->where('plans.0.name', 'Free')
            ->where('plans.1.name', 'Starter'));
});

test('each marketing page has its own title, description, and canonical link', function (string $route, string $component, string $title) {
    $this->get(route($route))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component($component)->where('meta.title', $title))
        ->assertSee('rel="canonical" href="'.route($route).'"', false)
        ->assertSee($title.' - Vendly</title>', false);
})->with([
    'home' => ['home', 'marketing/home', 'A shop on the web and in Telegram'],
    'features' => ['features', 'marketing/features', 'Features'],
    'how it works' => ['how-it-works', 'marketing/how-it-works', 'How it works'],
    'pricing' => ['pricing', 'marketing/pricing', 'Pricing'],
]);

test('the home page shows the newest live stores and never suspended ones', function () {
    freePlan();
    $older = app(CreateStore::class)->handle(User::factory()->create(), 'Older Tea');
    $older->forceFill(['created_at' => now()->subDays(3)])->save();
    $hidden = app(CreateStore::class)->handle(User::factory()->create(), 'Hidden Tea');
    $hidden->forceFill(['suspended_at' => now()])->save();
    app(CreateStore::class)->handle(User::factory()->create(), 'Newest Tea');

    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page
            ->has('recentStores', 2)
            ->where('recentStores.0.name', 'Newest Tea')
            ->where('recentStores.1.name', 'Older Tea')
            ->where('recentStores.1.url', route('stores.show', $older)));
});

test('testimonials only show when published, and the page is hidden without any', function () {
    Testimonial::factory()->draft()->create(['name' => 'Draft Person']);

    $this->get(route('testimonials'))->assertNotFound();
    $this->get(route('home'))->assertInertia(fn ($page) => $page->where('showTestimonials', false)->has('testimonials', 0));

    Testimonial::factory()->create(['name' => 'Real Seller']);

    $this->get(route('testimonials'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('testimonials', 1)->where('testimonials.0.name', 'Real Seller'));
    $this->get(route('features'))->assertInertia(fn ($page) => $page->where('showTestimonials', true));
});

test('the sitemap lists the marketing pages and live stores, and robots points to it', function () {
    freePlan();
    $live = app(CreateStore::class)->handle(User::factory()->create(), 'Live Tea');
    $suspended = app(CreateStore::class)->handle(User::factory()->create(), 'Gone Tea');
    $suspended->forceFill(['suspended_at' => now()])->save();

    $this->get(route('sitemap'))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml')
        ->assertSee('<loc>'.route('pricing').'</loc>', false)
        ->assertSee('<loc>'.route('stores.show', $live).'</loc>', false)
        ->assertDontSee(route('stores.show', $suspended), false)
        ->assertDontSee(route('testimonials'), false);

    $this->get('/robots.txt')
        ->assertOk()
        ->assertSee('Sitemap: '.route('sitemap'))
        ->assertSee('Disallow: /admin');
});

test('the saved theme is applied on the first render with a matching browser bar color', function (string $appearance, bool $dark, string $color) {
    $response = $this->withUnencryptedCookie('appearance', $appearance)->get(route('home'));

    $response->assertOk()->assertSee('<meta name="theme-color" content="'.$color.'">', false);

    expect(str_contains($response->getContent(), 'class="dark"'))->toBe($dark);
})->with([
    'dark' => ['dark', true, '#0b0b0c'],
    'light' => ['light', false, '#f4f7fc'],
    'system' => ['system', false, '#f4f7fc'],
]);

test('the footer names Vendly when no company name is set, whatever the app name', function () {
    config(['app.name' => 'Laravel']);

    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page->where('site.company_name', 'Vendly'));
});
