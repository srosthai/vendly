<?php

namespace Database\Seeders;

use App\Models\Store;
use App\Models\Testimonial;
use Illuminate\Database\Seeder;

/**
 * One testimonial from each demo vendor, for trying the website locally.
 * These quotes are invented, so they are never seeded outside local and
 * testing, where they could be taken for real reviews.
 */
class DemoTestimonialSeeder extends Seeder
{
    /**
     * @var list<string>
     */
    private const Quotes = [
        'Customers open one link and send me what they want. I answer in Telegram like I always did.',
        'I share the store link in my Facebook posts, and the orders come straight to my chat.',
        'Putting the menu online took one evening. Now regulars send their order before they arrive.',
        'The Telegram link works for customers who never install anything new.',
        'I publish new stock in the morning and the first requests come in before lunch.',
        'Tourists find the shop from the link on our card and message us with the exact pieces.',
        'Paying for more room by QR was simple, and there is no cut from my sales.',
        'Drafts let me prepare a whole collection and publish it on the day it arrives.',
        'Parents send the list of what they want and I pack it the same afternoon.',
        'Sold out shows on its own, so I stopped getting requests for things I do not have.',
    ];

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command->warn('Skipped demo testimonials: they are only seeded in local and testing.');

            return;
        }

        $stores = Store::query()
            ->whereHas('owner', fn ($owner) => $owner->where('email', 'like', 'vendor%@vendly.test'))
            ->with('owner')
            ->orderBy('id')
            ->get()
            ->filter(fn (Store $store): bool => preg_match('/^vendor\d+@vendly\.test$/', (string) $store->owner?->email) === 1)
            ->values();

        foreach ($stores as $index => $store) {
            Testimonial::query()->firstOrCreate(
                ['name' => (string) $store->owner?->name, 'role' => 'Owner, '.$store->name],
                ['quote' => self::Quotes[$index % count(self::Quotes)], 'published_at' => now(), 'sort' => $index + 1],
            );
        }
    }
}
