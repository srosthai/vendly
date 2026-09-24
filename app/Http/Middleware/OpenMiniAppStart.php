<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Telegram opens the mini app at the address set in @BotFather and passes
 * a link's startapp value as tgWebAppStartParam. Whatever page that is,
 * send it to the mini app entry, which opens the store or product. The
 * browser keeps the #tgWebAppData fragment across the redirect.
 */
class OpenMiniAppStart
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = $request->query('tgWebAppStartParam');

        if (
            $request->isMethod('GET')
            && is_string($start)
            && preg_match('/^[A-Za-z0-9_-]{1,64}$/', $start) === 1
            && ! $request->routeIs('mini-app')
        ) {
            return redirect()->route('mini-app', ['startapp' => $start]);
        }

        return $next($request);
    }
}
