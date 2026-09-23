<?php

namespace App\Http\Middleware;

use App\Models\Store;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasActiveStore
{
    /**
     * Vendor pages need a store the user owns that is not suspended. A user
     * without a store is sent to Start selling. A suspended store can read a
     * page that says so, and every write is refused.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $store = $user->store()->with('subscription.plan')->first();

        if ($store === null) {
            return $request->isMethod('GET')
                ? redirect()->route('selling.create')
                : abort(403, 'Open a store first.');
        }

        if ($store->isSuspended()) {
            abort_unless($request->isMethod('GET'), 403, 'Your store is suspended.');

            return Inertia::render('vendor/suspended', ['store' => ['name' => $store->name]])
                ->toResponse($request)
                ->setStatusCode(403);
        }

        $request->attributes->set(Store::class, $store);

        return $next($request);
    }
}
