<?php

use App\Enums\ProductStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Plan;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Store;
use App\Models\Testimonial;
use App\Models\User;
use Database\Seeders\DemoStoreSeeder;
use Database\Seeders\DemoTestimonialSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    Plan::query()->create(['name' => 'Free', 'price_cents' => 0, 'product_limit' => 10, 'is_active' => true, 'is_default' => true]);
});

test('the demo seeders build ten stores on plan b with unique catalogs and testimonials', function () {
    $this->seed([DemoStoreSeeder::class, DemoTestimonialSeeder::class]);

    $planB = Plan::query()->where('name', 'Plan B')->sole();
    expect($planB)->price_cents->toBe(700)->product_limit->toBe(150);

    $stores = Store::query()->with('subscription')->get();
    expect($stores)->toHaveCount(10)
        ->and($stores->every(fn (Store $store): bool => $store->subscription?->plan_id === $planB->id && $store->subscription->ends_at?->isFuture()))->toBeTrue();

    foreach ($stores as $store) {
        expect($store->products()->where('status', ProductStatus::Published)->count())->toBe(25);
    }

    foreach ([Product::class, Category::class, Brand::class] as $model) {
        $names = $model::query()->pluck('name');
        expect($names->unique()->count())->toBe($names->count());
    }

    expect(Store::query()->whereNotNull('logo_path')->count())->toBe(10)
        ->and(Storage::disk('public')->exists(Store::query()->value('logo_path')))->toBeTrue()
        ->and(ProductImage::query()->where('path', 'like', '%.webp')->count())->toBe(250)
        ->and(ProductImage::query()->count())->toBe(250)
        ->and(Storage::disk('public')->exists(ProductImage::query()->value('path')))->toBeTrue()
        ->and(Testimonial::query()->whereNotNull('published_at')->count())->toBe(10)
        ->and(User::query()->where('email', 'vendor10@vendly.test')->exists())->toBeTrue();
});

test('running the demo seeders again adds nothing', function () {
    $this->seed([DemoStoreSeeder::class, DemoTestimonialSeeder::class]);
    $counts = fn (): array => [Store::query()->count(), Product::query()->count(), Category::query()->count(), Brand::query()->count(), Testimonial::query()->count(), ProductImage::query()->count()];
    $before = $counts();

    $this->seed([DemoStoreSeeder::class, DemoTestimonialSeeder::class]);

    expect($counts())->toBe($before);
});

test('a second run swaps placeholder images for photos and keeps logos', function () {
    $this->seed(DemoStoreSeeder::class);
    $image = ProductImage::query()->where('path', 'like', '%.webp')->firstOrFail();
    $image->forceFill(['path' => 'demo/placeholder.svg'])->save();
    $store = Store::query()->firstOrFail();
    $store->forceFill(['logo_path' => 'logos/own-logo.png'])->save();

    $this->seed(DemoStoreSeeder::class);

    expect($image->fresh()->path)->toEndWith('.webp')
        ->and($store->fresh()->logo_path)->toBe('logos/own-logo.png')
        ->and(ProductImage::query()->count())->toBe(250);
});

test('the demo seeders refuse to run in production', function () {
    app()->detectEnvironment(fn (): string => 'production');

    $this->artisan('db:seed', ['--class' => DemoStoreSeeder::class, '--force' => true])->assertSuccessful();
    $this->artisan('db:seed', ['--class' => DemoTestimonialSeeder::class, '--force' => true])->assertSuccessful();

    expect(Store::query()->count())->toBe(0)
        ->and(Testimonial::query()->count())->toBe(0);
});
