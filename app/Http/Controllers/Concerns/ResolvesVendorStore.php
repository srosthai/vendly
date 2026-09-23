<?php

namespace App\Http\Controllers\Concerns;

use App\Http\Middleware\EnsureUserHasActiveStore;
use App\Models\Store;
use Illuminate\Http\Request;

trait ResolvesVendorStore
{
    /**
     * The owned, active store resolved by {@see EnsureUserHasActiveStore}.
     */
    protected function vendorStore(Request $request): Store
    {
        $store = $request->attributes->get(Store::class);
        abort_unless($store instanceof Store, 403);

        return $store;
    }
}
