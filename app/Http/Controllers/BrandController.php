<?php

namespace App\Http\Controllers;

use App\Actions\Catalog\CreateBrand;
use App\Http\Requests\CreateNamedRecordRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class BrandController extends Controller
{
    public function store(CreateNamedRecordRequest $request, CreateBrand $action): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->store !== null, 403);

        $action->handle($user->store, $request->string('name')->toString());

        return back();
    }
}
