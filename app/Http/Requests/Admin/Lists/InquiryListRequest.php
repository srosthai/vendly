<?php

namespace App\Http\Requests\Admin\Lists;

use App\Models\Store;

class InquiryListRequest extends ListRequest
{
    /**
     * `filter=undelivered` is the older link from the dashboard; it still
     * works and reads as the delivery filter.
     *
     * @return array{search: string, delivery: string, store: string, channel: string, sort: string}
     */
    public function filters(): array
    {
        $store = $this->query('store');
        $delivery = $this->query('filter') === 'undelivered'
            ? 'undelivered'
            : $this->choice('delivery', ['all', 'undelivered']);

        return [
            'search' => $this->search(),
            'delivery' => $delivery,
            'store' => is_string($store) && ctype_digit($store) && Store::query()->whereKey((int) $store)->exists() ? $store : 'all',
            'channel' => $this->choice('channel', ['all', 'cart', 'buy']),
            'sort' => $this->choice('sort', ['newest', 'oldest'], 'newest'),
        ];
    }
}
