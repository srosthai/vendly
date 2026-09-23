<?php

namespace App\Http\Controllers;

use App\Actions\Catalog\SaveCategory;
use App\Http\Controllers\Concerns\ResolvesVendorStore;
use App\Http\Requests\CreateNamedRecordRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class CategoryController extends Controller
{
    use ResolvesVendorStore;

    public function store(CreateNamedRecordRequest $request, SaveCategory $action): RedirectResponse
    {
        $this->authorize('create', Category::class);

        $action->handle($this->vendorStore($request), $request->string('name')->toString());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Category added.']);

        return back();
    }

    public function update(CreateNamedRecordRequest $request, Category $category, SaveCategory $action): RedirectResponse
    {
        $this->authorize('update', $category);

        $action->handle($this->vendorStore($request), $request->string('name')->toString(), $category);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Category renamed.']);

        return back();
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        $category->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Category deleted.']);

        return back();
    }
}
