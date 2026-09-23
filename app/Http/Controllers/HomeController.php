<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * The landing page shows the plans an admin has made available, so the
     * prices on it are never out of date.
     */
    public function __invoke(): Response
    {
        return Inertia::render('welcome', [
            'plans' => Plan::query()
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('price_cents')
                ->orderBy('id')
                ->get(['id', 'name', 'price_cents', 'product_limit', 'is_default']),
        ]);
    }
}
