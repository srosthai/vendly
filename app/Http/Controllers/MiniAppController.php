<?php

namespace App\Http\Controllers;

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
