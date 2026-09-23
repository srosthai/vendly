<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MiniAppController extends Controller
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $slug = $request->query('startapp', $request->query('tgWebAppStartParam'));

        if (is_string($slug) && preg_match('/^p_(\d+)$/', $slug, $matches) === 1) {
            $product = Product::query()->published()->with('store')->find((int) $matches[1]);

            if ($product instanceof Product && ! $product->store->isSuspended()) {
                $request->session()->put('mini_app', true);

                return redirect()->route('stores.products.show', ['store' => $product->store, 'productSlug' => $product->slug]);
            }
        }

        if (is_string($slug) && $slug !== '') {
            $store = Store::query()->where('slug', $slug)->first();

            if ($store instanceof Store && ! $store->isSuspended()) {
                $request->session()->put('mini_app', true);

                return redirect()->route('stores.show', $store);
            }
        }

        return Inertia::render('stores/enter');
    }
}
