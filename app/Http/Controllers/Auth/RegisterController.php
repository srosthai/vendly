<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\CompleteRegistration;
use App\Actions\Auth\StartRegistration;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StartRegistrationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function create(Request $request): Response
    {
        if ($request->query('next') === 'sell') {
            $request->session()->put('url.intended', route('selling.create'));
        }

        return Inertia::render('auth/register', [
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
            'step' => 'details',
            'email' => '',
            'status' => $request->session()->get('status'),
        ]);
    }

    public function code(Request $request): Response|RedirectResponse
    {
        $email = $request->session()->get('register_email');

        if (! is_string($email) || $email === '') {
            return redirect()->route('register');
        }

        return Inertia::render('auth/register', [
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
            'step' => 'code',
            'email' => $email,
            'status' => $request->session()->get('status'),
        ]);
    }

    public function store(StartRegistrationRequest $request, StartRegistration $action): RedirectResponse
    {
        $email = $request->string('email')->toString();
        $action->handle(
            $request->string('name')->toString(),
            $email,
            $request->string('password')->toString(),
        );
        $request->session()->put('register_email', strtolower($email));

        return redirect()->route('auth.register.code')->with('status', 'We sent a registration code.');
    }

    public function resend(Request $request, StartRegistration $action): RedirectResponse
    {
        $email = $request->session()->get('register_email');

        if (! is_string($email) || $email === '' || ! $action->resend($email)) {
            return redirect()->route('register')->with('status', 'Your code expired. Enter your details again.');
        }

        return back()->with('status', 'We sent a new code.');
    }

    public function verify(Request $request, CompleteRegistration $action): RedirectResponse
    {
        $email = $request->session()->get('register_email');

        if (! is_string($email) || $email === '') {
            return redirect()->route('register');
        }

        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $user = $action->handle($email, $validated['code']);

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget('register_email');

        return redirect()->intended(route('dashboard'));
    }
}
