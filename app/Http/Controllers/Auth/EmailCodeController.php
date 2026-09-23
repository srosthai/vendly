<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\SendEmailCode;
use App\Actions\Auth\VerifyEmailCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendEmailCodeRequest;
use App\Http\Requests\Auth\VerifyEmailCodeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class EmailCodeController extends Controller
{
    public function create(Request $request): Response
    {
        $next = $request->query('next');

        if ($next === 'sell') {
            $request->session()->put('url.intended', route('selling.create'));
        }

        if (is_string($next) && str_starts_with($next, '/s/')) {
            $request->session()->put('url.intended', url($next));
        }

        return Inertia::render('auth/sign-in', [
            'step' => 'email',
            'email' => '',
            'status' => $request->session()->get('status'),
        ]);
    }

    public function code(Request $request): Response|RedirectResponse
    {
        $email = $request->session()->get('sign_in_email');

        if (! is_string($email) || $email === '') {
            return redirect()->route('auth.sign-in');
        }

        return Inertia::render('auth/sign-in', [
            'step' => 'code',
            'email' => $email,
            'status' => $request->session()->get('status'),
        ]);
    }

    public function store(SendEmailCodeRequest $request, SendEmailCode $action): RedirectResponse
    {
        $email = $request->string('email')->toString();
        $action->handle($email);
        $request->session()->put('sign_in_email', strtolower($email));

        return redirect()->route('auth.sign-in.code')->with('status', 'We sent a sign-in code.');
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
