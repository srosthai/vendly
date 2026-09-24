<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Concerns\ResolvesVendorStore;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\RequestListRequest;
use App\Models\Inquiry;
use App\Models\InquiryItem;
use App\Services\Telegram\TelegramNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Every buy and cart request the vendor's store received, with a way to
 * mark each one handled once the vendor has replied.
 */
class RequestController extends Controller
{
    use ResolvesVendorStore;

    public function index(RequestListRequest $request): Response
    {
        $store = $this->vendorStore($request);
        $filters = $request->filters();
        $pattern = $request->searchPattern();
        $number = ltrim($filters['search'], '#');

        $requests = $store->inquiries()
            ->with(['items.product', 'customer'])
            ->when($filters['search'] !== '', fn ($query) => $query->where(fn ($query) => $query
                ->whereLike('customer_name', $pattern)
                ->orWhereLike('contact', $pattern)
                ->when(ctype_digit($number), fn ($query) => $query->orWhere('number', (int) $number))))
            ->when($filters['status'] === 'new', fn ($query) => $query->whereNull('handled_at'))
            ->when($filters['status'] === 'handled', fn ($query) => $query->whereNotNull('handled_at'))
            ->when($filters['kind'] === 'cart', fn ($query) => $query->where('from_cart', true))
            ->when($filters['kind'] === 'buy', fn ($query) => $query->where('from_cart', false));

        $filters['sort'] === 'oldest'
            ? $requests->orderBy('created_at')->orderBy('id')
            : $requests->orderByDesc('created_at')->orderByDesc('id');

        return Inertia::render('vendor/requests', [
            'filters' => $filters,
            'newCount' => $store->inquiries()->whereNull('handled_at')->count(),
            'open' => $request->integer('open') ?: null,
            'requests' => $requests->paginate(RequestListRequest::PerPage)->withQueryString()->through(fn (Inquiry $inquiry): array => [
                'id' => $inquiry->id,
                'reference' => $inquiry->reference(),
                'customer' => $inquiry->customer_name,
                'contact' => $inquiry->contact,
                'telegram' => TelegramNotifier::customerUsername($inquiry),
                'from_cart' => $inquiry->from_cart,
                'items' => $inquiry->items->map(fn (InquiryItem $item): array => [
                    'name' => $item->name,
                    'quantity' => $item->quantity,
                    'price_cents' => $item->price_cents,
                    'url' => $item->product === null ? null : route('stores.products.show', ['store' => $store, 'productSlug' => $item->product->slug]),
                ])->values()->all(),
                'total_cents' => (int) $inquiry->items->sum(fn (InquiryItem $item): int => $item->price_cents * $item->quantity),
                'sent_at' => $inquiry->created_at?->toIso8601String(),
                'handled_at' => $inquiry->handled_at?->toIso8601String(),
                'delivered' => $inquiry->vendor_notified_at !== null,
            ]),
        ]);
    }

    /**
     * Mark a request handled, or open it again.
     */
    public function update(Request $request, Inquiry $inquiry): RedirectResponse
    {
        abort_unless($inquiry->store_id === $this->vendorStore($request)->id, 404);
        $request->validate(['handled' => ['required', 'boolean']]);

        $inquiry->handled_at = $request->boolean('handled') ? now() : null;
        $inquiry->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => $request->boolean('handled')
            ? 'Request '.$inquiry->reference().' marked handled.'
            : 'Request '.$inquiry->reference().' is open again.']);

        return back();
    }
}
