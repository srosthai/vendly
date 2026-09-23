<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\PlatformSetting;
use App\Models\Store;
use App\Models\SubscriptionPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function vendors(Request $request): Response
    {
        $search = trim($request->string('search')->toString());

        $stores = Store::query()
            ->with(['owner', 'subscription.plan'])
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->whereLike('name', '%'.$search.'%')
                ->orWhereLike('slug', '%'.$search.'%')
                ->orWhereHas('owner', fn ($owner) => $owner->whereLike('email', '%'.$search.'%'))))
            ->withCount(['products as published_count' => fn ($query) => $query->published()])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('admin/vendors', [
            'search' => $search,
            'vendors' => $stores->through(fn (Store $store): array => [
                'id' => $store->id,
                'name' => $store->name,
                'slug' => $store->slug,
                'owner' => $store->owner?->name,
                'plan' => $store->subscription?->plan->name,
                'published_count' => (int) $store->getAttribute('published_count'),
                'suspended' => $store->isSuspended(),
            ]),
        ]);
    }

    public function plans(): Response
    {
        return Inertia::render('admin/plans', [
            'plans' => Plan::query()->orderByDesc('created_at')->orderByDesc('id')->get(['id', 'name', 'price_cents', 'product_limit', 'is_active', 'is_default']),
        ]);
    }

    public function payments(): Response
    {
        $payments = SubscriptionPayment::query()
            ->with(['store', 'plan'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('admin/payments', [
            'payments' => $payments->through(fn (SubscriptionPayment $payment): array => [
                'id' => $payment->id,
                'store' => $payment->store?->name,
                'plan' => $payment->plan?->name,
                'amount_cents' => $payment->amount_cents,
                'status' => $payment->status->value,
                'created_at' => $payment->created_at?->toIso8601String(),
                'paid_at' => $payment->paid_at?->toIso8601String(),
            ]),
        ]);
    }

    public function telegram(): Response
    {
        $settings = PlatformSetting::current();

        return Inertia::render('admin/telegram', [
            'settings' => [
                'admin_chat_id' => $settings->admin_chat_id ?? '',
                'bot_username' => $settings->bot_username ?? '',
                'mini_app_short_name' => $settings->mini_app_short_name ?? '',
            ],
            'secrets' => [
                'bot_token' => filled(config('services.telegram.bot_token')),
                'cutluy_key' => filled(config('services.cutluy.key')),
                'cutluy_webhook' => filled(config('services.cutluy.webhook_secret')),
                'telegram_webhook' => filled(config('services.telegram.webhook_secret')),
            ],
            'testResult' => request()->session()->get('telegram_test'),
            'webhookUrls' => [
                'telegram' => route('webhooks.telegram'),
                'cutluy' => route('webhooks.cutluy'),
            ],
        ]);
    }

    public function updateTelegram(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'admin_chat_id' => ['nullable', 'string', 'max:64'],
            'bot_username' => ['nullable', 'string', 'max:64'],
            'mini_app_short_name' => ['nullable', 'string', 'max:64'],
        ]);

        PlatformSetting::current()->update($validated);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Telegram settings saved.']);

        return back();
    }
}
