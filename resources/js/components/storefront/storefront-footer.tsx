import { Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { home } from '@/routes';

/**
 * A quiet credit under the store. It is left out inside Telegram, where the
 * mini app keeps its chrome to the store alone.
 */
export function StorefrontFooter({ hidden }: { hidden: boolean }) {
    if (hidden) {
        return null;
    }

    return (
        <footer className="flex justify-center pt-4 pb-2">
            <Link
                href={home()}
                className="flex min-h-11 items-center gap-2 rounded-md text-sm text-muted-foreground hover:text-foreground"
            >
                <AppLogoIcon className="size-5" />
                Made with Vendly
            </Link>
        </footer>
    );
}
