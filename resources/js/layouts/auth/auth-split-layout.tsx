import { Link } from '@inertiajs/react';
import { ChevronLeft } from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import {
    RequestMessage,
    ShareLinkFragment,
} from '@/components/marketing/product-fragments';
import { ThemeToggle } from '@/components/theme-toggle';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

/**
 * Two columns on a wide screen: a navy brand panel with what Vendly gives a
 * seller (a store with two links, and the request that arrives in
 * Telegram), and the form on the page background. On a phone the panel folds away and the logo sits above the
 * form. Every sign-in, registration, and password screen uses it.
 */
export default function AuthSplitLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="grid min-h-svh bg-background lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
            <aside className="relative m-3 hidden flex-col overflow-hidden rounded-[2rem] bg-[var(--brand-navy)] p-10 text-white lg:flex xl:p-12 dark:bg-secondary dark:text-secondary-foreground">
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute inset-0 bg-[radial-gradient(55%_45%_at_85%_100%,color-mix(in_oklab,var(--primary)_45%,transparent),transparent),radial-gradient(35%_30%_at_0%_0%,color-mix(in_oklab,var(--highlight)_22%,transparent),transparent)]"
                />
                <Link
                    href={home()}
                    className="relative flex min-h-11 items-center gap-2 self-start rounded-md"
                    aria-label="Vendly home"
                >
                    <AppLogoIcon className="size-9" />
                    <span className="text-xl font-bold tracking-tight">
                        Vendly
                    </span>
                </Link>
                <div className="relative mx-auto my-auto w-full max-w-lg py-12">
                    <p className="text-4xl leading-[1.08] font-bold tracking-[-0.03em] xl:text-5xl">
                        One link for your shop, on the web and in Telegram.
                    </p>
                    <p className="mt-5 text-lg leading-relaxed text-white/70 dark:text-muted-foreground">
                        Customers pick products from your store and the request
                        lands in your Telegram chat.
                    </p>
                    <div className="mt-12" aria-hidden="true">
                        <div className="rounded-3xl bg-card p-5 text-card-foreground shadow-2xl shadow-black/20">
                            <div className="mb-4 flex items-center gap-3">
                                <span className="flex size-10 items-center justify-center rounded-xl bg-primary font-bold text-primary-foreground">
                                    S
                                </span>
                                <span>
                                    <span className="block font-semibold">
                                        Smile Tea
                                    </span>
                                    <span className="block text-sm text-muted-foreground">
                                        One store, two links to share
                                    </span>
                                </span>
                            </div>
                            <ShareLinkFragment />
                        </div>
                        <RequestMessage className="relative -mt-4 ml-auto w-72 shadow-2xl shadow-black/25 sm:mr-6" />
                    </div>
                </div>
                <p className="relative text-sm text-white/60 dark:text-muted-foreground">
                    Start free. Vendly takes nothing from your sales.
                </p>
            </aside>

            <div className="relative flex flex-col px-4 py-6 sm:px-8">
                <div className="flex items-center justify-between gap-3">
                    <Link
                        href={home()}
                        className="inline-flex min-h-11 items-center gap-1 rounded-full pr-3 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
                    >
                        <ChevronLeft className="size-4" aria-hidden="true" />
                        Back to website
                    </Link>
                    <ThemeToggle />
                </div>
                <main className="mx-auto flex w-full max-w-sm flex-1 flex-col justify-center py-10">
                    <Link
                        href={home()}
                        className="mb-8 flex min-h-11 items-center gap-2 self-start rounded-md lg:hidden"
                        aria-label="Vendly home"
                    >
                        <AppLogoIcon className="size-9" />
                        <span className="text-xl font-bold tracking-tight">
                            Vendly
                        </span>
                    </Link>
                    <div className="mb-8 space-y-2">
                        <h1 className="text-3xl font-bold tracking-[-0.02em]">
                            {title}
                        </h1>
                        {description ? (
                            <p className="text-muted-foreground">
                                {description}
                            </p>
                        ) : null}
                    </div>
                    <div className="flex flex-col gap-6">{children}</div>
                </main>
            </div>
        </div>
    );
}
