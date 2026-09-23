import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import StoreController from '@/actions/App/Http/Controllers/StoreController';
import { ThemeToggle } from '@/components/theme-toggle';
import { cn } from '@/lib/utils';

type StoreIdentity = {
    name: string;
    slug: string;
    logo: string | null;
    description?: string | null;
};

/**
 * The store's own mark: its logo, or its first letter on the brand tint.
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
                'flex shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-secondary text-lg font-bold text-secondary-foreground',
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
 * the cart. `compact` is the slimmer bar used on a product page.
 */
export function StoreHeader({
    store,
    cart,
    compact = false,
}: {
    store: StoreIdentity;
    cart: ReactNode;
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
        <header className="flex flex-wrap items-center justify-between gap-4 rounded-3xl border bg-card p-4 sm:p-6">
            <div className="flex min-w-0 items-center gap-4">
                <StoreMark store={store} className="size-16 text-2xl" />
                <div className="min-w-0">
                    <h1 className="text-2xl font-bold tracking-tight sm:text-3xl">
                        {store.name}
                    </h1>
                    {store.description ? (
                        <p className="mt-1 max-w-[60ch] text-muted-foreground">
                            {store.description}
                        </p>
                    ) : null}
                </div>
            </div>
            <div className="flex items-center gap-2">
                <ThemeToggle />
                {cart}
            </div>
        </header>
    );
}
