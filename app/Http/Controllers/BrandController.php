<?php

namespace App\Http\Controllers;

use App\Actions\Catalog\CreateBrand;
use App\Http\Controllers\Concerns\ResolvesVendorStore;
use App\Http\Requests\CreateNamedRecordRequest;
use App\Models\Brand;
use Illuminate\Http\RedirectResponse;

class BrandController extends Controller
{
    use ResolvesVendorStore;

    public function store(CreateNamedRecordRequest $request, CreateBrand $action): RedirectResponse
    {
        $this->authorize('create', Brand::class);

        $action->handle($this->vendorStore($request), $request->string('name')->toString());

        return back();
    }
}
