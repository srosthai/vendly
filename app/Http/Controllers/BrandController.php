<?php

namespace App\Http\Controllers;

use App\Actions\Catalog\SaveBrand;
use App\Http\Controllers\Concerns\ResolvesVendorStore;
use App\Http\Requests\CreateNamedRecordRequest;
use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class BrandController extends Controller
{
    use ResolvesVendorStore;

    public function store(CreateNamedRecordRequest $request, SaveBrand $action): RedirectResponse
    {
        $this->authorize('create', Brand::class);

        $action->handle($this->vendorStore($request), $request->string('name')->toString());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Brand added.']);

        return back();
    }

    public function update(CreateNamedRecordRequest $request, Brand $brand, SaveBrand $action): RedirectResponse
    {
        $this->authorize('update', $brand);

        $action->handle($this->vendorStore($request), $request->string('name')->toString(), $brand);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Brand renamed.']);

        return back();
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        $this->authorize('delete', $brand);

        $brand->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Brand deleted.']);

        return back();
    }
}
