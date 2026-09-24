<?php

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Plan;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Ten demo vendors, each with a store on Plan B, a logo, and 25 published
 * products with photos across its own categories and brands. The photos
 * are openly licensed; see data/images/CREDITS.md. Local and testing only: the
 * accounts share a public password. Running it again adds nothing.
 *
 * Logins: vendor1@vendly.test to vendor10@vendly.test, password "password".
 *
 * @phpstan-type DemoStore array{owner: string, store: string, accent: string, description: string, address: string, hours: string, price: array{0: int, 1: int}, brands: list<string>, categories: array<string, list<string>>}
 */
class DemoStoreSeeder extends Seeder
{
    public const Stores = 10;

    public const ProductsPerStore = 25;

    /**
     * The mark's background for each accent, for the generated photos.
     *
     * @var array<string, string>
     */
    private const AccentColors = [
        'blue' => '#0054D5', 'orange' => '#FD890F', 'green' => '#15803D', 'teal' => '#0F766E',
        'purple' => '#7C3AED', 'pink' => '#DB2777', 'red' => '#DC2626', 'slate' => '#334155',
    ];

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command->warn('Skipped demo stores: they are only seeded in local and testing.');

            return;
        }

        $plan = Plan::query()->firstOrCreate(
            ['name' => 'Plan B'],
            ['price_cents' => 700, 'product_limit' => 150, 'is_active' => true, 'is_default' => false],
        );

        /** @var list<DemoStore> $stores */
        $stores = require __DIR__.'/data/demo-stores.php';

        foreach (array_slice($stores, 0, self::Stores) as $index => $data) {
            DB::transaction(fn () => $this->seedStore($index + 1, $data, $plan));
        }
    }

    /**
     * @param  DemoStore  $data
     */
    private function seedStore(int $number, array $data, Plan $plan): void
    {
        $owner = User::query()->firstOrCreate(
            ['email' => "vendor{$number}@vendly.test"],
            ['name' => $data['owner'], 'password' => 'password', 'email_verified_at' => now()],
        );

        $store = $owner->store()->first() ?? Store::query()->create([
            'user_id' => $owner->id,
            'name' => $data['store'],
            'slug' => Str::slug($data['store']),
            'description' => $data['description'],
            'currency' => 'USD',
            'accent' => $data['accent'],
            'address' => $data['address'],
            'hours' => $data['hours'],
        ]);

        $this->ensureLogo($store);

        $subscription = $store->subscription()->first();

        if ($subscription === null || $subscription->plan_id !== $plan->id) {
            $store->subscription()->updateOrCreate([], [
                'plan_id' => $plan->id,
                'status' => SubscriptionStatus::Active,
                'starts_at' => now(),
                'ends_at' => now()->addMonth(),
            ]);
        }

        $brands = collect($data['brands'])->map(fn (string $name): Brand => Brand::query()->firstOrCreate(
            ['store_id' => $store->id, 'slug' => Str::slug($name)],
            ['name' => $name],
        ))->values();

        $sort = 0;

        foreach ($data['categories'] as $categoryName => $products) {
            $category = Category::query()->firstOrCreate(
                ['store_id' => $store->id, 'slug' => Str::slug($categoryName)],
                ['name' => $categoryName, 'sort' => ++$sort],
            );

            foreach ($products as $productName) {
                $this->seedProduct($store, $category, $brands->random(), $productName, $data);
            }
        }
    }

    /**
     * @param  DemoStore  $data
     */
    private function seedProduct(Store $store, Category $category, Brand $brand, string $name, array $data): void
    {
        $slug = Str::slug($name);
        $product = $store->products()->where('slug', $slug)->first();

        if ($product === null) {
            [$min, $max] = $data['price'];
            $roll = random_int(1, 10);

            $product = Product::query()->create([
                'store_id' => $store->id,
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'name' => $name,
                'slug' => $slug,
                'description' => $name.' from '.$store->name.'. '.$data['description'],
                'price_cents' => (int) (round(random_int($min, $max) / 10) * 10),
                'stock' => match (true) {
                    $roll === 1 => 0,
                    $roll <= 5 => null,
                    default => random_int(1, 60),
                },
                'status' => ProductStatus::Published,
            ]);
        }

        $this->ensurePhoto($store, $product, $data);
    }

    /**
     * Give the store its logo from the seed data, unless it already has one.
     */
    private function ensureLogo(Store $store): void
    {
        $source = __DIR__.'/data/logos/'.$store->slug.'.svg';

        if ($store->logo_path !== null || ! is_file($source)) {
            return;
        }

        $path = 'logos/demo-'.$store->slug.'.svg';
        Storage::disk('public')->put($path, (string) file_get_contents($source));
        $store->forceFill(['logo_path' => $path])->save();
    }

    /**
     * Give the product its photo from the seed data. A product seeded before
     * the photos existed has its placeholder swapped for the photo; one with
     * no photo in the data keeps or gets the placeholder.
     *
     * @param  DemoStore  $data
     */
    private function ensurePhoto(Store $store, Product $product, array $data): void
    {
        $image = $product->images()->first();
        $source = __DIR__.'/data/images/'.$store->slug.'/'.$product->slug.'.webp';

        if (is_file($source)) {
            if ($image !== null && ! str_ends_with($image->path, '.svg')) {
                return;
            }

            $path = "demo/{$store->slug}/{$product->slug}.webp";
            Storage::disk('public')->put($path, (string) file_get_contents($source));

            if ($image !== null) {
                Storage::disk('public')->delete($image->path);
                $image->forceFill(['path' => $path])->save();
            } else {
                ProductImage::query()->create(['product_id' => $product->id, 'path' => $path, 'sort' => 0]);
            }

            return;
        }

        if ($image === null) {
            $path = "demo/{$store->slug}/{$product->slug}.svg";
            Storage::disk('public')->put($path, $this->photo($product->name, self::AccentColors[$data['accent']] ?? '#0054D5'));
            ProductImage::query()->create(['product_id' => $product->id, 'path' => $path, 'sort' => 0]);
        }
    }

    /**
     * A plain product card image, used only when the seed data has no photo
     * for a product: the product's initials on the store's color.
     */
    private function photo(string $name, string $color): string
    {
        $initials = collect(explode(' ', $name))
            ->filter()
            ->take(2)
            ->map(fn (string $word): string => mb_strtoupper(mb_substr($word, 0, 1)))
            ->implode('');
        $label = e($name);

        return <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 600" width="600" height="600">
          <defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="{$color}" stop-opacity="0.18"/><stop offset="1" stop-color="{$color}" stop-opacity="0.42"/></linearGradient></defs>
          <rect width="600" height="600" fill="#f4f7fc"/>
          <rect width="600" height="600" fill="url(#g)"/>
          <circle cx="300" cy="270" r="120" fill="{$color}"/>
          <text x="300" y="300" text-anchor="middle" font-family="system-ui, sans-serif" font-size="96" font-weight="700" fill="#ffffff">{$initials}</text>
          <text x="300" y="470" text-anchor="middle" font-family="system-ui, sans-serif" font-size="34" font-weight="600" fill="#081A3B">{$label}</text>
        </svg>
        SVG;
    }
}
