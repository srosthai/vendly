<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Google and Telegram accounts have no password to confirm. They pass
 * straight through; everyone else confirms their password as usual.
 */
class RequirePasswordWhenSet extends RequirePassword
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle($request, Closure $next, $redirectToRoute = null, $passwordTimeoutSeconds = null): Response
    {
        $user = $request->user();

        if ($user instanceof User && $user->password === null) {
            return $next($request);
        }

        return parent::handle($request, $next, $redirectToRoute, $passwordTimeoutSeconds);
    }
}
