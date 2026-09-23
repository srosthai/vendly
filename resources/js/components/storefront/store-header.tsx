import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import StoreController from '@/actions/App/Http/Controllers/StoreController';
import { accents } from '@/components/storefront/store-profile';
import { ThemeToggle } from '@/components/theme-toggle';
import { cn } from '@/lib/utils';

type StoreIdentity = {
    name: string;
    slug: string;
    logo: string | null;
    description?: string | null;
    accent?: string | null;
};

/**
 * The store's own mark: its logo, or its first letter on the store's accent
 * (the brand tint when it has none).
 */
export function StoreMark({
    store,
    className,
}: {
    store: StoreIdentity;
    className?: string;
}) {
    return (
        <span
            className={cn(
                'flex shrink-0 items-center justify-center overflow-hidden rounded-2xl text-lg font-bold',
                store.accent && accents[store.accent]
                    ? accents[store.accent].mark
                    : 'bg-secondary text-secondary-foreground',
                className,
            )}
        >
            {store.logo ? (
                <img
                    src={store.logo}
                    alt=""
                    className="size-full object-cover"
                />
            ) : (
                store.name.slice(0, 1).toUpperCase()
            )}
        </span>
    );
}

/**
 * The top of every store page: logo, name, one line about the store, and
 * the cart, with optional details below. `compact` is the slimmer bar used
 * on a product page.
 */
export function StoreHeader({
    store,
    cart,
    details,
    compact = false,
}: {
    store: StoreIdentity;
    cart: ReactNode;
    details?: ReactNode;
    compact?: boolean;
}) {
    if (compact) {
        return (
            <header className="flex items-center justify-between gap-3">
                <Link
                    href={StoreController.show(store.slug)}
                    className="flex min-w-0 items-center gap-3 rounded-xl"
                >
                    <StoreMark store={store} className="size-10 text-base" />
                    <span className="truncate font-semibold">{store.name}</span>
                </Link>
                <div className="flex items-center gap-2">
                    <ThemeToggle />
                    {cart}
                </div>
            </header>
        );
    }

    return (
        <header className="relative overflow-hidden rounded-3xl border bg-card p-4 pt-5 sm:p-6 sm:pt-7">
            {store.accent && accents[store.accent] ? (
                <span
                    aria-hidden="true"
                    className={cn(
                        'absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r',
                        accents[store.accent].band,
                    )}
                />
            ) : null}
            <div className="grid grid-cols-[auto_minmax(0,1fr)] items-start gap-x-4 gap-y-3 sm:grid-cols-[auto_minmax(0,1fr)_auto]">
                <StoreMark
                    store={store}
                    className="size-14 text-xl sm:size-16 sm:text-2xl"
                />
                <div className="flex items-center justify-end gap-2 sm:col-start-3 sm:row-start-1">
                    <ThemeToggle />
                    {cart}
                </div>
                <div className="col-span-2 min-w-0 sm:col-span-1 sm:col-start-2 sm:row-start-1">
                    <h1 className="text-2xl font-bold tracking-tight sm:text-3xl">
                        {store.name}
                    </h1>
                    {store.description ? (
                        <p className="mt-1 max-w-[60ch] text-muted-foreground">
                            {store.description}
                        </p>
                    ) : null}
                    {details}
                </div>
            </div>
        </header>
    );
}
