<?php

namespace App\Http\Requests\Admin\Lists;

use App\Models\Plan;

class VendorListRequest extends ListRequest
{
    /**
     * @return array{search: string, status: string, plan: string, telegram: string, sort: string}
     */
    public function filters(): array
    {
        $plan = $this->query('plan');

        return [
            'search' => $this->search(),
            'status' => $this->choice('status', ['all', 'live', 'suspended']),
            'plan' => is_string($plan) && ctype_digit($plan) && Plan::query()->whereKey((int) $plan)->exists() ? $plan : 'all',
            'telegram' => $this->choice('telegram', ['all', 'connected', 'missing']),
            'sort' => $this->choice('sort', ['newest', 'oldest', 'name', 'products'], 'newest'),
        ];
    }
}
