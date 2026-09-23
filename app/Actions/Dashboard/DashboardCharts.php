<?php

namespace App\Actions\Dashboard;

use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Models\Inquiry;
use App\Models\InquiryItem;
use App\Models\Store;
use App\Models\SubscriptionPayment;
use Carbon\CarbonInterface;

/**
 * The series behind the dashboard charts, built from real records: daily
 * totals over the last 30 days, compared with the 30 days before, and
 * simple breakdowns.
 */
class DashboardCharts
{
    public const Days = 30;

    public function __construct(private DailyCounts $daily) {}

    /**
     * @return array{days: list<string>, revenue: array{values: list<int>, total: int, previous: int}, vendors: array{values: list<int>, total: int, previous: int}, payments_by_status: list<array{key: string, value: int}>, delivery: list<array{key: string, value: int}>}
     */
    public function admin(): array
    {
        $since = $this->since();
        $before = $since->subDays(self::Days);

        $paid = SubscriptionPayment::query()
            ->where('status', PaymentStatus::Paid)
            ->where('paid_at', '>=', $before)
            ->get(['amount_cents', 'paid_at']);
        $current = $paid->filter(fn (SubscriptionPayment $payment): bool => $payment->paid_at !== null && $payment->paid_at->greaterThanOrEqualTo($since));

        $stores = Store::query()->where('created_at', '>=', $before)->pluck('created_at');
        $newStores = $stores->filter(fn (?CarbonInterface $at): bool => $at !== null && $at->greaterThanOrEqualTo($since));

        $statusCounts = SubscriptionPayment::query()
            ->where('created_at', '>=', $since)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $inquiries = Inquiry::query()->where('inquiries.created_at', '>=', $since)->with('store:id,telegram_chat_id')->get(['id', 'store_id', 'vendor_notified_at', 'vendor_error']);

        return [
            'days' => $this->daily->days(self::Days),
            'revenue' => [
                'values' => $this->daily->handle($current->map(fn (SubscriptionPayment $payment): array => ['at' => $payment->paid_at, 'amount' => $payment->amount_cents]), self::Days),
                'total' => (int) $current->sum('amount_cents'),
                'previous' => (int) $paid->sum('amount_cents') - (int) $current->sum('amount_cents'),
            ],
            'vendors' => [
                'values' => $this->daily->handle($newStores->map(fn (?CarbonInterface $at): array => ['at' => $at]), self::Days),
                'total' => $newStores->count(),
                'previous' => $stores->count() - $newStores->count(),
            ],
            'payments_by_status' => array_values(array_filter(array_map(
                fn (PaymentStatus $status): array => ['key' => $status->value, 'value' => (int) ($statusCounts[$status->value] ?? 0)],
                PaymentStatus::cases(),
            ), fn (array $slice): bool => $slice['value'] > 0)),
            'delivery' => array_values(array_filter([
                ['key' => 'delivered', 'value' => $inquiries->filter(fn (Inquiry $inquiry): bool => $inquiry->vendor_notified_at !== null)->count()],
                ['key' => 'failed', 'value' => $inquiries->filter(fn (Inquiry $inquiry): bool => $inquiry->vendor_notified_at === null && $inquiry->vendor_error !== null)->count()],
                ['key' => 'admin_only', 'value' => $inquiries->filter(fn (Inquiry $inquiry): bool => $inquiry->vendor_notified_at === null && $inquiry->vendor_error === null)->count()],
            ], fn (array $slice): bool => $slice['value'] > 0)),
        ];
    }

    /**
     * @return array{days: list<string>, requests: array{values: list<int>, total: int, previous: int}, products_by_status: list<array{key: string, value: int}>, top_products: array<int, array{name: string, value: int}>}
     */
    public function vendor(Store $store): array
    {
        $since = $this->since();
        $before = $since->subDays(self::Days);

        $requests = $store->inquiries()->where('created_at', '>=', $before)->pluck('created_at');
        $current = $requests->filter(fn (?CarbonInterface $at): bool => $at !== null && $at->greaterThanOrEqualTo($since));

        $soldOut = $store->products()->published()->where('stock', 0)->count();
        $published = $store->products()->published()->count() - $soldOut;
        $drafts = $store->products()->where('status', ProductStatus::Draft)->count();

        $top = InquiryItem::query()
            ->whereHas('inquiry', fn ($inquiry) => $inquiry->where('store_id', $store->id)->where('created_at', '>=', $since))
            ->selectRaw('name, sum(quantity) as total')
            ->groupBy('name')
            ->orderByDesc('total')
            ->orderBy('name')
            ->limit(5)
            ->get();

        return [
            'days' => $this->daily->days(self::Days),
            'requests' => [
                'values' => $this->daily->handle($current->map(fn (?CarbonInterface $at): array => ['at' => $at]), self::Days),
                'total' => $current->count(),
                'previous' => $requests->count() - $current->count(),
            ],
            'products_by_status' => array_values(array_filter([
                ['key' => 'published', 'value' => $published],
                ['key' => 'sold_out', 'value' => $soldOut],
                ['key' => 'draft', 'value' => $drafts],
            ], fn (array $slice): bool => $slice['value'] > 0)),
            'top_products' => $top->map(fn (InquiryItem $item): array => [
                'name' => (string) $item->name,
                'value' => (int) $item->getAttribute('total'),
            ])->values()->all(),
        ];
    }

    private function since(): CarbonInterface
    {
        return now()->subDays(self::Days - 1)->startOfDay()->toImmutable();
    }
}
