<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\Testimonial;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * The marketing pages and every live store.
     */
    public function sitemap(): Response
    {
        $pages = collect(['home', 'features', 'how-it-works', 'pricing', 'stores.index'])
            ->when(Testimonial::query()->whereNotNull('published_at')->exists(), fn ($pages) => $pages->push('testimonials'))
            ->map(fn (string $name): array => ['url' => route($name), 'updated' => null]);

        $stores = Store::query()
            ->whereNull('suspended_at')
            ->orderBy('id')
            ->get(['id', 'slug', 'updated_at'])
            ->map(fn (Store $store): array => ['url' => route('stores.show', $store), 'updated' => $store->updated_at?->toDateString()]);

        return response()
            ->view('sitemap', ['urls' => $pages->concat($stores)])
            ->header('Content-Type', 'application/xml');
    }

    public function robots(): Response
    {
        return response("User-agent: *\nDisallow: /admin\nDisallow: /vendor\nDisallow: /dashboard\nDisallow: /settings\n\nSitemap: ".route('sitemap')."\n")
            ->header('Content-Type', 'text/plain');
    }
}
