<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Lists\InquiryListRequest;
use App\Models\Inquiry;
use App\Models\InquiryItem;
use App\Models\Store;
use App\Services\Telegram\TelegramNotifier;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class InquiryController extends Controller
{
    public function index(InquiryListRequest $request): Response
    {
        $filters = $request->filters();
        $pattern = $request->searchPattern();

        $inquiries = Inquiry::query()
            ->with(['store', 'items'])
            ->when($filters['search'] !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->whereLike('customer_name', $pattern)
                ->orWhereLike('contact', $pattern)
                ->orWhereHas('store', fn (Builder $store) => $store->whereLike('name', $pattern))))
            ->when($filters['delivery'] === 'undelivered', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->whereNull('admin_notified_at')
                ->orWhere(fn (Builder $query) => $query
                    ->whereNull('vendor_notified_at')
                    ->whereHas('store', fn (Builder $store) => $store->whereNotNull('telegram_chat_id')))))
            ->when($filters['store'] !== 'all', fn (Builder $query) => $query->where('store_id', (int) $filters['store']))
            ->when($filters['channel'] === 'cart', fn (Builder $query) => $query->where('from_cart', true))
            ->when($filters['channel'] === 'buy', fn (Builder $query) => $query->where('from_cart', false))
            ->when($filters['sort'] === 'oldest', fn (Builder $query) => $query->orderBy('created_at')->orderBy('id'))
            ->when($filters['sort'] !== 'oldest', fn (Builder $query) => $query->orderByDesc('created_at')->orderByDesc('id'))
            ->paginate(InquiryListRequest::PerPage)
            ->withQueryString()
            ->through(fn (Inquiry $inquiry): array => [
                'id' => $inquiry->id,
                'reference' => $inquiry->reference(),
                'store' => $inquiry->store->name,
                'customer' => $inquiry->customer_name,
                'contact' => $inquiry->contact,
                'sent_at' => $inquiry->created_at?->toIso8601String(),
                'lines' => $inquiry->items->map(fn (InquiryItem $item): string => $item->quantity.' × '.$item->name)->all(),
                'total' => Money::format((int) $inquiry->items->sum(fn (InquiryItem $item): int => $item->price_cents * $item->quantity)),
                'admin' => ['delivered' => $inquiry->admin_notified_at !== null, 'error' => $inquiry->admin_error],
                'vendor' => [
                    'connected' => filled($inquiry->store->telegram_chat_id),
                    'delivered' => $inquiry->vendor_notified_at !== null,
                    'error' => $inquiry->vendor_error,
                ],
                'can_retry' => $inquiry->undeliveredDestinations() !== [],
            ]);

        return Inertia::render('admin/requests', [
            'inquiries' => $inquiries,
            'filters' => $filters,
            'stores' => Store::query()->whereHas('inquiries')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Send again only to the chats that have not received the request.
     */
    public function retry(Inquiry $inquiry, TelegramNotifier $telegram): RedirectResponse
    {
        $destinations = $inquiry->undeliveredDestinations();

        if ($destinations === []) {
            Inertia::flash('toast', ['type' => 'info', 'message' => 'Request '.$inquiry->reference().' was already delivered.']);

            return back();
        }

        $inquiry->forceFill(array_fill_keys(array_map(fn (string $destination): string => $destination.'_error', $destinations), null))->save();
        $telegram->inquiry($inquiry, $destinations);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sending request '.$inquiry->reference().' again.']);

        return back();
    }
}
