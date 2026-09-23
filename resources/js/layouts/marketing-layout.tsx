import { Link, usePage } from '@inertiajs/react';
import { Menu } from 'lucide-react';
import type { ReactNode } from 'react';
import { useEffect } from 'react';
import AppLogo from '@/components/app-logo';
import { ThemeSwitch } from '@/components/theme-switch';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetClose,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn } from '@/lib/utils';
import {
    dashboard,
    features,
    home,
    howItWorks,
    login,
    pricing,
    register,
    testimonials,
} from '@/routes';

type NavLink = { title: string; href: ReturnType<typeof features> };

function useNavLinks(): NavLink[] {
    const { showTestimonials } = usePage<{ showTestimonials?: boolean }>()
        .props;

    return [
        { title: 'Features', href: features() },
        { title: 'How it works', href: howItWorks() },
        { title: 'Pricing', href: pricing() },
        ...(showTestimonials
            ? [{ title: 'Testimonials', href: testimonials() }]
            : []),
    ];
}

/**
 * The public website frame: a docs-style bar that stays at the top, and a
 * footer that links every page. The page scrollbar is hidden here, while
 * the page still scrolls by wheel, touch, and keyboard.
 */
export default function MarketingLayout({ children }: { children: ReactNode }) {
    const { auth } = usePage().props;
    const links = useNavLinks();
    const { isCurrentUrl } = useCurrentUrl();
    const startSelling = register({ query: { next: 'sell' } });

    useEffect(() => {
        document.documentElement.classList.add('hide-scrollbar');

        return () =>
            document.documentElement.classList.remove('hide-scrollbar');
    }, []);

    return (
        <div className="flex min-h-dvh flex-col bg-background text-foreground">
            <header className="sticky top-0 z-40 w-full border-b bg-background/80 backdrop-blur-lg supports-[backdrop-filter]:bg-background/70">
                <div className="mx-auto flex h-16 max-w-6xl items-center gap-6 px-4 md:px-6">
                    <Link
                        href={home()}
                        className="flex shrink-0 items-center gap-1 rounded-md"
                        aria-label="Vendly home"
                    >
                        <AppLogo />
                    </Link>
                    <nav
                        className="hidden items-center gap-1 md:flex"
                        aria-label="Main"
                    >
                        {links.map((link) => {
                            const active = isCurrentUrl(link.href);

                            return (
                                <Link
                                    key={link.title}
                                    href={link.href}
                                    aria-current={active ? 'page' : undefined}
                                    className={cn(
                                        'rounded-full px-3 py-1.5 text-sm font-medium transition-colors',
                                        active
                                            ? 'bg-secondary text-secondary-foreground'
                                            : 'text-muted-foreground hover:text-foreground',
                                    )}
                                >
                                    {link.title}
                                </Link>
                            );
                        })}
                    </nav>
                    <div className="ml-auto flex items-center gap-1.5">
                        <ThemeSwitch />
                        {auth.user ? (
                            <Button
                                asChild
                                size="sm"
                                className="hidden sm:inline-flex"
                            >
                                <Link href={dashboard()}>Dashboard</Link>
                            </Button>
                        ) : (
                            <>
                                <Button
                                    asChild
                                    variant="ghost"
                                    size="sm"
                                    className="hidden sm:inline-flex"
                                >
                                    <Link href={login()}>Log in</Link>
                                </Button>
                                <Button
                                    asChild
                                    size="sm"
                                    className="hidden sm:inline-flex"
                                >
                                    <Link href={startSelling}>
                                        Start selling
                                    </Link>
                                </Button>
                            </>
                        )}
                        <Sheet>
                            <SheetTrigger asChild>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="size-9 md:hidden"
                                    aria-label="Open menu"
                                >
                                    <Menu />
                                </Button>
                            </SheetTrigger>
                            <SheetContent>
                                <SheetHeader>
                                    <SheetTitle>Menu</SheetTitle>
                                </SheetHeader>
                                <nav
                                    className="flex flex-col gap-1 px-4"
                                    aria-label="Main"
                                >
                                    {links.map((link) => (
                                        <SheetClose asChild key={link.title}>
                                            <Link
                                                href={link.href}
                                                className="rounded-xl px-3 py-3 text-base font-medium hover:bg-accent"
                                            >
                                                {link.title}
                                            </Link>
                                        </SheetClose>
                                    ))}
                                </nav>
                                <div className="mt-auto flex flex-col gap-2 p-4">
                                    {auth.user ? (
                                        <Button asChild size="lg">
                                            <Link href={dashboard()}>
                                                Dashboard
                                            </Link>
                                        </Button>
                                    ) : (
                                        <>
                                            <Button asChild size="lg">
                                                <Link href={startSelling}>
                                                    Start selling
                                                </Link>
                                            </Button>
                                            <Button
                                                asChild
                                                size="lg"
                                                variant="outline"
                                            >
                                                <Link href={login()}>
                                                    Log in
                                                </Link>
                                            </Button>
                                        </>
                                    )}
                                </div>
                            </SheetContent>
                        </Sheet>
                    </div>
                </div>
            </header>

            <main className="flex-1">{children}</main>

            <footer className="border-t bg-card">
                <div className="mx-auto grid max-w-6xl gap-10 px-4 py-12 md:grid-cols-[minmax(0,1fr)_auto] md:px-6">
                    <div className="max-w-xs">
                        <Link
                            href={home()}
                            className="inline-flex items-center gap-1 rounded-md"
                            aria-label="Vendly home"
                        >
                            <AppLogo />
                        </Link>
                        <p className="mt-3 text-sm text-muted-foreground">
                            One link for your shop, on the web and inside
                            Telegram.
                        </p>
                    </div>
                    <div className="grid grid-cols-2 gap-10 text-sm sm:grid-cols-2">
                        <div>
                            <p className="font-semibold">Product</p>
                            <ul className="mt-3 space-y-2">
                                {links.map((link) => (
                                    <li key={link.title}>
                                        <Link
                                            href={link.href}
                                            className="text-muted-foreground hover:text-foreground"
                                        >
                                            {link.title}
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        </div>
                        <div>
                            <p className="font-semibold">Account</p>
                            <ul className="mt-3 space-y-2">
                                <li>
                                    <Link
                                        href={startSelling}
                                        className="text-muted-foreground hover:text-foreground"
                                    >
                                        Start selling
                                    </Link>
                                </li>
                                <li>
                                    <Link
                                        href={login()}
                                        className="text-muted-foreground hover:text-foreground"
                                    >
                                        Log in
                                    </Link>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div className="border-t">
                    <p className="mx-auto max-w-6xl px-4 py-5 text-xs text-muted-foreground md:px-6">
                        © {new Date().getFullYear()} Vendly. Small shops on the
                        web and in Telegram.
                    </p>
                </div>
            </footer>
        </div>
    );
}
