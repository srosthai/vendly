<?php

namespace App\Http\Controllers;

use App\Actions\Catalog\CreateCategory;
use App\Http\Controllers\Concerns\ResolvesVendorStore;
use App\Http\Requests\CreateNamedRecordRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;

class CategoryController extends Controller
{
    use ResolvesVendorStore;

    public function store(CreateNamedRecordRequest $request, CreateCategory $action): RedirectResponse
    {
        $this->authorize('create', Category::class);

        $action->handle($this->vendorStore($request), $request->string('name')->toString());

        return back();
    }
}
