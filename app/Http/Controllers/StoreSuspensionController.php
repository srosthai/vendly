<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class StoreSuspensionController extends Controller
{
    public function store(Store $store): RedirectResponse
    {
        $this->authorize('suspend', $store);
        $store->suspended_at = now();
        $store->save();
        Inertia::flash('toast', ['type' => 'success', 'message' => $store->name.' is suspended.']);

        return back();
    }

    public function destroy(Store $store): RedirectResponse
    {
        $this->authorize('suspend', $store);
        $store->suspended_at = null;
        $store->save();
        Inertia::flash('toast', ['type' => 'success', 'message' => $store->name.' is restored.']);

        return back();
    }
}
