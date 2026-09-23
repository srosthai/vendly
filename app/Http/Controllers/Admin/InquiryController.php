<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\InquiryItem;
use App\Services\Telegram\TelegramNotifier;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InquiryController extends Controller
{
    public function index(Request $request): Response
    {
        $undelivered = $request->query('filter') === 'undelivered';

        $inquiries = Inquiry::query()
            ->with(['store', 'items'])
            ->when($undelivered, fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->whereNull('admin_notified_at')
                ->orWhere(fn (Builder $query) => $query
                    ->whereNull('vendor_notified_at')
                    ->whereHas('store', fn (Builder $store) => $store->whereNotNull('telegram_chat_id')))))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25)
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
            'filter' => $undelivered ? 'undelivered' : 'all',
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
