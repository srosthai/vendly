<?php

namespace App\Http\Middleware;

use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user = $request->user(),
                'hasStore' => $user instanceof User && $user->store()->exists(),
                'hasPassword' => $user instanceof User && $user->password !== null,
            ],
            'workspace' => fn (): ?array => $user instanceof User ? $this->workspace($user) : null,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * What the sidebar card shows: plan usage for a vendor, requests that did
     * not reach Telegram for an admin, and nothing for a customer.
     *
     * @return array<string, mixed>|null
     */
    private function workspace(User $user): ?array
    {
        if ($user->is_admin) {
            return [
                'kind' => 'admin',
                'undelivered' => Inquiry::query()->whereNull('admin_notified_at')->count(),
            ];
        }

        $store = $user->store()->with('subscription.plan')->first();

        if ($store === null) {
            return null;
        }

        $subscription = $store->subscription;

        return [
            'kind' => 'vendor',
            'store' => $store->name,
            'plan' => $subscription?->plan->name,
            'free' => $subscription?->plan->isFree() ?? true,
            'published' => $store->products()->published()->count(),
            'limit' => (int) ($subscription?->plan->product_limit ?? 0),
            'can_publish' => $subscription?->allowsPublishing() ?? false,
            'suspended' => $store->isSuspended(),
        ];
    }
}
