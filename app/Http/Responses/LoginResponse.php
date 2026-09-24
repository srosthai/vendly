<?php

namespace App\Http\Responses;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

/**
 * Where someone goes after logging in. Customers return to the store page
 * they wanted (a product they tapped Buy on). Admins and vendors manage
 * Vendly, so a store page they browsed as a guest does not pull them away
 * from their dashboard; a back-office page they were sent from still does.
 */
class LoginResponse implements LoginResponseContract, TwoFactorLoginResponseContract
{
    /**
     * @param  Request  $request
     */
    public function toResponse($request): Response
    {
        if ($request->wantsJson()) {
            return new JsonResponse(['two_factor' => false]);
        }

        return self::redirectFor($request);
    }

    public static function redirectFor(Request $request): RedirectResponse
    {
        $user = $request->user();
        $intended = $request->session()->pull('url.intended');

        if (! is_string($intended) || ($user instanceof User && self::managesVendly($user) && self::isShopping($intended))) {
            return redirect()->route('dashboard');
        }

        return redirect()->to($intended);
    }

    private static function managesVendly(User $user): bool
    {
        return $user->is_admin || $user->store()->exists();
    }

    /**
     * Pages a customer is headed to: a store, a product, the mini app, or
     * opening a store (which someone who already has one does not need).
     */
    private static function isShopping(string $url): bool
    {
        $path = '/'.ltrim((string) parse_url($url, PHP_URL_PATH), '/');

        return str_starts_with($path, '/s/') || $path === '/m' || $path === '/start-selling';
    }
}
