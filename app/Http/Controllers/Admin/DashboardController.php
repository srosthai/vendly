<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Lists\PaymentListRequest;
use App\Http\Requests\Admin\Lists\PlanListRequest;
use App\Http\Requests\Admin\Lists\VendorListRequest;
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
    public function vendors(VendorListRequest $request): Response
    {
        $filters = $request->filters();
        $pattern = $request->searchPattern();

        $stores = Store::query()
            ->with(['owner', 'subscription.plan'])
            ->when($filters['search'] !== '', fn ($query) => $query->where(fn ($query) => $query
                ->whereLike('name', $pattern)
                ->orWhereLike('slug', $pattern)
                ->orWhereHas('owner', fn ($owner) => $owner->whereLike('email', $pattern)->orWhereLike('name', $pattern))))
            ->when($filters['status'] === 'live', fn ($query) => $query->whereNull('suspended_at'))
            ->when($filters['status'] === 'suspended', fn ($query) => $query->whereNotNull('suspended_at'))
            ->when($filters['plan'] !== 'all', fn ($query) => $query->whereHas('subscription', fn ($subscription) => $subscription->where('plan_id', (int) $filters['plan'])))
            ->when($filters['telegram'] === 'connected', fn ($query) => $query->whereNotNull('telegram_chat_id'))
            ->when($filters['telegram'] === 'missing', fn ($query) => $query->whereNull('telegram_chat_id'))
            ->withCount(['products as published_count' => fn ($query) => $query->published()]);

        match ($filters['sort']) {
            'oldest' => $stores->orderBy('created_at')->orderBy('id'),
            'name' => $stores->orderBy('name')->orderBy('id'),
            'products' => $stores->orderByDesc('published_count')->orderByDesc('id'),
            default => $stores->orderByDesc('created_at')->orderByDesc('id'),
        };

        return Inertia::render('admin/vendors', [
            'filters' => $filters,
            'plans' => Plan::query()->orderBy('price_cents')->orderBy('id')->get(['id', 'name']),
            'vendors' => $stores->paginate(VendorListRequest::PerPage)->withQueryString()->through(fn (Store $store): array => [
                'id' => $store->id,
                'name' => $store->name,
                'slug' => $store->slug,
                'owner' => $store->owner?->name,
                'plan' => $store->subscription?->plan->name,
                'published_count' => (int) $store->getAttribute('published_count'),
                'telegram_connected' => filled($store->telegram_chat_id),
                'suspended' => $store->isSuspended(),
            ]),
        ]);
    }

    public function plans(PlanListRequest $request): Response
    {
        $filters = $request->filters();

        $plans = Plan::query()
            ->withCount(['subscriptions as stores_count' => fn ($query) => $query->where('status', SubscriptionStatus::Active)])
            ->when($filters['search'] !== '', fn ($query) => $query->whereLike('name', $request->searchPattern()))
            ->when($filters['availability'] === 'available', fn ($query) => $query->where('is_active', true))
            ->when($filters['availability'] === 'hidden', fn ($query) => $query->where('is_active', false))
            ->when($filters['price'] === 'free', fn ($query) => $query->where('price_cents', 0))
            ->when($filters['price'] === 'paid', fn ($query) => $query->where('price_cents', '>', 0));

        match ($filters['sort']) {
            'price' => $plans->orderBy('price_cents')->orderBy('id'),
            'limit' => $plans->orderBy('product_limit')->orderBy('id'),
            default => $plans->orderByDesc('created_at')->orderByDesc('id'),
        };

        return Inertia::render('admin/plans', [
            'filters' => $filters,
            'plans' => $plans->paginate(PlanListRequest::PerPage)->withQueryString()->through(fn (Plan $plan): array => [
                'id' => $plan->id,
                'name' => $plan->name,
                'price_cents' => $plan->price_cents,
                'yearly_price_cents' => $plan->yearly_price_cents,
                'product_limit' => $plan->product_limit,
                'is_active' => $plan->is_active,
                'is_default' => $plan->is_default,
                'stores_count' => (int) $plan->getAttribute('stores_count'),
            ]),
        ]);
    }

    public function payments(PaymentListRequest $request): Response
    {
        $filters = $request->filters();
        $pattern = $request->searchPattern();
        $from = $request->fromDate();
        $to = $request->toDate();

        $payments = SubscriptionPayment::query()
            ->with(['store', 'plan'])
            ->when($filters['search'] !== '', fn ($query) => $query->where(fn ($query) => $query
                ->whereLike('public_id', $pattern)
                ->orWhereLike('cutluy_id', $pattern)
                ->orWhereHas('store', fn ($store) => $store->whereLike('name', $pattern))))
            ->when($filters['status'] !== 'all', fn ($query) => $query->where('status', $filters['status']))
            ->when($from !== null, fn ($query) => $query->where('created_at', '>=', $from))
            ->when($to !== null, fn ($query) => $query->where('created_at', '<=', $to));

        match ($filters['sort']) {
            'oldest' => $payments->orderBy('created_at')->orderBy('id'),
            'amount' => $payments->orderByDesc('amount_cents')->orderByDesc('id'),
            default => $payments->orderByDesc('created_at')->orderByDesc('id'),
        };

        return Inertia::render('admin/payments', [
            'filters' => $filters,
            'payments' => $payments->paginate(PaymentListRequest::PerPage)->withQueryString()->through(fn (SubscriptionPayment $payment): array => [
                'id' => $payment->id,
                'reference' => $payment->public_id,
                'store' => $payment->store?->name,
                'store_id' => $payment->store_id,
                'plan' => $payment->plan?->name,
                'amount_cents' => $payment->amount_cents,
                'period' => $payment->period->value,
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
                'cutluy_key' => $settings->cutluyApiKey() !== '',
                'cutluy_webhook' => $settings->cutluyWebhookSecret() !== '',
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
