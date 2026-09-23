<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\RedirectResponse;

class StoreSuspensionController extends Controller
{
    public function store(Store $store): RedirectResponse
    {
        $this->authorize('suspend', $store);
        $store->suspended_at = now();
        $store->save();

        return back();
    }

    public function destroy(Store $store): RedirectResponse
    {
        $this->authorize('suspend', $store);
        $store->suspended_at = null;
        $store->save();

        return back();
    }
}
