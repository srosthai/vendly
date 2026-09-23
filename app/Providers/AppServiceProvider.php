<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        $this->configureRateLimiting();

        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('email-code', function (Request $request): Limit {
            return Limit::perMinute(5)->by(Str::transliterate(Str::lower($request->string('email')->toString()).'|'.$request->ip()));
        });

        RateLimiter::for('register-verify', function (Request $request): Limit {
            return Limit::perMinute(10)->by(Str::lower((string) $request->session()->get('register_email')).'|'.$request->ip());
        });

        RateLimiter::for('inquiries', function (Request $request): Limit {
            return Limit::perMinute(5)
                ->by($request->user()?->getAuthIdentifier().'|'.$request->ip())
                ->response(fn (Request $request, array $headers) => back()->with(
                    'status',
                    'Too many requests. Try again in '.($headers['Retry-After'] ?? 60).' seconds.',
                ));
        });

        RateLimiter::for('telegram-auth', function (Request $request): Limit {
            return Limit::perMinute(10)->by($request->ip() ?? 'telegram');
        });
    }
}
