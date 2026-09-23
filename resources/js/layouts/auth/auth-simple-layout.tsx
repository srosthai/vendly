import { Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { ThemeSwitch } from '@/components/theme-switch';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

/**
 * One white card on the tinted page, with the Vendly logo above it. Every
 * sign-in, registration, and password screen uses it.
 */
export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="relative flex min-h-svh flex-col items-center justify-center gap-6 bg-background px-4 py-16 sm:px-6">
            <ThemeSwitch className="absolute top-4 right-4" />
            <Link
                href={home()}
                className="flex items-center gap-2 rounded-md"
                aria-label="Vendly home"
            >
                <AppLogoIcon className="size-10" />
                <span className="text-2xl font-bold tracking-tight">
                    Vendly
                </span>
            </Link>
            <main className="w-full max-w-md rounded-2xl border bg-card p-6 sm:p-8">
                <div className="mb-6 space-y-1.5 text-center">
                    <h1 className="text-xl font-bold tracking-tight">
                        {title}
                    </h1>
                    {description ? (
                        <p className="text-sm text-muted-foreground">
                            {description}
                        </p>
                    ) : null}
                </div>
                <div className="flex flex-col gap-6">{children}</div>
            </main>
            <p className="max-w-md text-center text-xs text-muted-foreground">
                Vendly runs small shops on the web and inside Telegram.
            </p>
        </div>
    );
}
