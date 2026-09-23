<?php

namespace App\Http\Controllers\Selling;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Models\Store;
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

        $sample = PlatformSetting::current()->miniAppLink('__slug__');

        return Inertia::render('selling/create', [
            'webBase' => url('/s').'/',
            'telegramBase' => $sample === null ? null : str_replace('__slug__', '', $sample),
            'maxSlugLength' => Store::MaxSlugLength,
        ]);
    }
}
