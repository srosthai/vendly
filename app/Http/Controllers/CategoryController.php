<?php

namespace App\Http\Controllers;

use App\Actions\Catalog\CreateCategory;
use App\Http\Requests\CreateNamedRecordRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class CategoryController extends Controller
{
    public function store(CreateNamedRecordRequest $request, CreateCategory $action): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->store !== null, 403);

        $action->handle($user->store, $request->string('name')->toString());

        return back();
    }
}
