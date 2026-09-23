<?php

namespace App\Http\Controllers\Selling;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StartSellingController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        if ($user->store()->exists()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('selling/create');
    }
}
