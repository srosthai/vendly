<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\SendEmailCode;
use App\Actions\Auth\VerifyEmailCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendEmailCodeRequest;
use App\Http\Requests\Auth\VerifyEmailCodeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class EmailCodeController extends Controller
{
    public function store(SendEmailCodeRequest $request, SendEmailCode $action): RedirectResponse
    {
        $action->handle($request->string('email')->toString());

        return back()->with('status', 'We sent a sign-in code.');
    }

    public function verify(VerifyEmailCodeRequest $request, VerifyEmailCode $action): RedirectResponse
    {
        $user = $action->handle(
            $request->string('email')->toString(),
            $request->string('code')->toString(),
            $request->string('name')->toString() ?: null,
        );

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
