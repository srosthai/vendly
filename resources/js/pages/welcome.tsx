import { Head, Link } from '@inertiajs/react';
import {
    Link2,
    ListChecks,
    Menu,
    MessageSquareText,
    Send,
    ShoppingBag,
    Smartphone,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetClose,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { dollars } from '@/lib/format';
import { home, login, register } from '@/routes';

type Plan = {
    id: number;
    name: string;
    price_cents: number;
    product_limit: number;
    is_default: boolean;
};

const sections = [
    { href: '#how', label: 'How it works' },
    { href: '#why', label: 'Why Vendly' },
    { href: '#pricing', label: 'Pricing' },
];

const steps = [
    {
        title: 'Open a store',
        body: 'Name the shop, pick its link, and add the products you sell. New products start as drafts.',
    },
    {
        title: 'Share the link',
        body: 'Send the web address or the Telegram mini app link. Both open the same store.',
    },
    {
        title: 'Read the request',
        body: 'When a customer taps Buy or sends a cart, the product list arrives in your Telegram chat.',
    },
];

const reasons = [
    {
        icon: Link2,
        title: 'One link, two places',
        body: 'The same store opens in a browser and inside the Telegram mini app. Customers never install anything.',
    },
    {
        icon: MessageSquareText,
        title: 'Requests, not checkout',
        body: 'Customers send what they want to buy. You reply and agree on payment in Telegram, the way you already sell.',
    },
    {
        icon: ListChecks,
        title: 'A clear product limit',
        body: 'Each plan sets how many products you can publish. Drafts do not count, so you can prepare ahead.',
    },
    {
        icon: Smartphone,
        title: 'Made for the phone',
        body: 'The store, the cart, and your dashboard all work on a small screen, inside Telegram or out.',
    },
];

const startSelling = register({ query: { next: 'sell' } });

function NavLinks({ inSheet = false }: { inSheet?: boolean }) {
    return (
        <>
            {sections.map((section) =>
                inSheet ? (
                    <SheetClose asChild key={section.href}>
                        <a
                            href={section.href}
                            className="rounded-xl px-3 py-3 text-base font-medium hover:bg-accent"
                        >
                            {section.label}
                        </a>
                    </SheetClose>
                ) : (
                    <a
                        key={section.href}
                        href={section.href}
                        className="text-sm font-medium text-muted-foreground hover:text-foreground"
                    >
                        {section.label}
                    </a>
                ),
            )}
        </>
    );
}

/**
 * The hero picture shows how Vendly works rather than describing it: a
 * product card from an example store, and the Telegram message the vendor
 * gets when a customer taps Buy.
 */
function HowItLooks() {
    return (
        <div className="relative mx-auto w-full max-w-lg lg:max-w-none">
            <img
                src="/images/shop-counter.jpg"
                alt="Jars of loose tea and paper bags of cakes on a shop counter."
                className="aspect-[4/3] w-full rounded-3xl object-cover"
                width={1400}
                height={933}
            />
            <div className="absolute top-4 left-4 w-44 rounded-2xl border bg-card p-3 shadow-[var(--brand-navy)]/10 shadow-lg sm:top-6 sm:left-6 sm:w-52">
                <p className="text-xs text-muted-foreground">
                    Example store: Smile Tea
                </p>
                <p className="mt-1 font-semibold">Jasmine tea</p>
                <p className="text-lg font-bold tabular-nums">$2.50</p>
                <div className="mt-2 flex items-center gap-2">
                    <span className="inline-flex h-8 flex-1 items-center justify-center rounded-full bg-primary text-xs font-medium text-primary-foreground">
                        Buy
                    </span>
                    <span className="inline-flex size-8 items-center justify-center rounded-full border text-muted-foreground">
                        <ShoppingBag className="size-4" aria-hidden="true" />
                    </span>
                </div>
            </div>
            <div className="hero-message absolute right-4 -bottom-6 w-60 rounded-2xl rounded-br-md border bg-card p-3 shadow-[var(--brand-navy)]/10 shadow-lg sm:right-6 sm:w-72">
                <div className="flex items-center gap-2 text-xs text-muted-foreground">
                    <span className="flex size-6 items-center justify-center rounded-full bg-primary text-primary-foreground">
                        <Send className="size-3" aria-hidden="true" />
                    </span>
                    Telegram, just now
                </div>
                <p className="mt-2 text-sm font-semibold">
                    New request #42 from Smile Tea
                </p>
                <p className="text-sm text-muted-foreground">
                    From: Ada (@ada)
                </p>
                <p className="text-sm">1 × Jasmine tea, $2.50</p>
            </div>
        </div>
    );
}

function PlanCard({ plan }: { plan: Plan }) {
    const free = plan.price_cents === 0;

    return (
        <article
            className={
                plan.is_default
                    ? 'flex flex-col gap-4 rounded-2xl border-2 border-primary bg-card p-6'
                    : 'flex flex-col gap-4 rounded-2xl border bg-card p-6'
            }
        >
            <div className="flex items-center justify-between gap-2">
                <h3 className="text-lg font-semibold">{plan.name}</h3>
                {plan.is_default ? (
                    <Badge>Every store starts here</Badge>
                ) : null}
            </div>
            <p className="text-4xl font-bold tracking-tight tabular-nums">
                {free ? '$0' : dollars(plan.price_cents)}
                <span className="text-base font-normal text-muted-foreground">
                    {' '}
                    / month
                </span>
            </p>
            <p className="text-muted-foreground">
                Publish up to {plan.product_limit} products.{' '}
                {free
                    ? 'No payment needed.'
                    : 'Paid monthly by Cambodia QR through CutLuy.'}
            </p>
        </article>
    );
}

export default function Welcome({ plans }: { plans: Plan[] }) {
    return (
        <>
            <Head title="Shops on the web and in Telegram">
                <meta
                    name="description"
                    content="Vendly gives your shop one link that opens on the web and inside Telegram. Customers send what they want to buy straight to your chat."
                />
            </Head>
            <div className="min-h-dvh bg-background text-foreground">
                <header className="sticky top-0 z-40 px-4 pt-3 md:px-6">
                    <div className="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 rounded-2xl border bg-card/90 px-3 backdrop-blur-md sm:px-4">
                        <Link
                            href={home()}
                            className="flex items-center gap-1 rounded-md"
                            aria-label="Vendly home"
                        >
                            <AppLogo />
                        </Link>
                        <nav
                            className="hidden items-center gap-7 lg:flex"
                            aria-label="Sections"
                        >
                            <NavLinks />
                        </nav>
                        <div className="hidden items-center gap-2 lg:flex">
                            <Button variant="ghost" asChild>
                                <Link href={login()}>Log in</Link>
                            </Button>
                            <Button asChild>
                                <Link href={startSelling}>Start selling</Link>
                            </Button>
                        </div>
                        <Sheet>
                            <SheetTrigger asChild>
                                <Button
                                    variant="outline"
                                    size="icon"
                                    className="size-11 lg:hidden"
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
                                    aria-label="Sections"
                                >
                                    <NavLinks inSheet />
                                </nav>
                                <div className="mt-auto flex flex-col gap-2 p-4">
                                    <Button asChild size="lg">
                                        <Link href={startSelling}>
                                            Start selling
                                        </Link>
                                    </Button>
                                    <Button asChild size="lg" variant="outline">
                                        <Link href={login()}>Log in</Link>
                                    </Button>
                                </div>
                            </SheetContent>
                        </Sheet>
                    </div>
                </header>

                <main>
                    <section className="mx-auto grid max-w-6xl items-center gap-14 px-4 pt-12 pb-20 md:px-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.05fr)] lg:pt-20 lg:pb-28">
                        <div className="max-w-xl">
                            <h1 className="text-4xl leading-[1.04] font-bold tracking-[-0.03em] sm:text-5xl lg:text-6xl">
                                A shop your customers open from a link.
                            </h1>
                            <p className="mt-5 max-w-[46ch] text-lg leading-relaxed text-muted-foreground">
                                Put your products on one page that works in any
                                browser and inside Telegram. When a customer
                                wants something, the request lands in your chat.
                            </p>
                            <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                                <Button asChild size="lg">
                                    <Link href={startSelling}>
                                        Start selling for free
                                    </Link>
                                </Button>
                                <Button asChild size="lg" variant="outline">
                                    <a href="#how">See how it works</a>
                                </Button>
                            </div>
                            <p className="mt-4 text-sm text-muted-foreground">
                                The free plan needs no payment. Open a store in
                                a minute.
                            </p>
                        </div>
                        <HowItLooks />
                    </section>

                    <section
                        id="how"
                        className="scroll-mt-24 bg-card px-4 py-20 md:px-6"
                    >
                        <div className="mx-auto max-w-6xl">
                            <h2 className="max-w-xl text-3xl font-bold tracking-tight sm:text-4xl">
                                Three steps from products to requests
                            </h2>
                            <ol className="mt-12 grid gap-8 md:grid-cols-3">
                                {steps.map((step, index) => (
                                    <li key={step.title} className="flex gap-4">
                                        <span
                                            className="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-bold text-primary-foreground"
                                            aria-hidden="true"
                                        >
                                            {index + 1}
                                        </span>
                                        <div>
                                            <h3 className="text-lg font-semibold">
                                                {step.title}
                                            </h3>
                                            <p className="mt-1.5 max-w-[34ch] text-muted-foreground">
                                                {step.body}
                                            </p>
                                        </div>
                                    </li>
                                ))}
                            </ol>
                        </div>
                    </section>

                    <section
                        id="why"
                        className="scroll-mt-24 px-4 py-20 md:px-6"
                    >
                        <div className="mx-auto grid max-w-6xl gap-12 lg:grid-cols-[minmax(0,20rem)_1fr]">
                            <div>
                                <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">
                                    Built for how small shops sell
                                </h2>
                                <p className="mt-4 text-muted-foreground">
                                    No card checkout and no order inbox. Your
                                    customers message you, and you sell the way
                                    you already do.
                                </p>
                            </div>
                            <ul className="grid gap-x-10 gap-y-10 sm:grid-cols-2">
                                {reasons.map((reason) => (
                                    <li
                                        key={reason.title}
                                        className="flex gap-4"
                                    >
                                        <span className="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-secondary text-secondary-foreground">
                                            <reason.icon
                                                className="size-5"
                                                aria-hidden="true"
                                            />
                                        </span>
                                        <div>
                                            <h3 className="font-semibold">
                                                {reason.title}
                                            </h3>
                                            <p className="mt-1.5 text-muted-foreground">
                                                {reason.body}
                                            </p>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </section>

                    {plans.length > 0 ? (
                        <section
                            id="pricing"
                            className="scroll-mt-24 bg-card px-4 py-20 md:px-6"
                        >
                            <div className="mx-auto max-w-6xl">
                                <h2 className="text-3xl font-bold tracking-tight sm:text-4xl">
                                    Pay for more room, not for sales
                                </h2>
                                <p className="mt-3 max-w-[56ch] text-muted-foreground">
                                    Plans only change how many products you can
                                    publish. Vendly takes nothing from what you
                                    sell.
                                </p>
                                <div className="mt-10 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                    {plans.map((plan) => (
                                        <PlanCard key={plan.id} plan={plan} />
                                    ))}
                                </div>
                            </div>
                        </section>
                    ) : null}

                    <section className="px-4 py-20 md:px-6">
                        <div className="mx-auto grid max-w-6xl gap-10 rounded-3xl bg-[var(--brand-navy)] p-8 text-white sm:p-12 md:grid-cols-2 dark:bg-secondary dark:text-secondary-foreground">
                            <div>
                                <h2 className="text-2xl font-bold tracking-tight">
                                    For sellers
                                </h2>
                                <p className="mt-3 max-w-[42ch] text-white/75 dark:text-muted-foreground">
                                    Register, confirm the emailed code, and name
                                    your store. Add products, share the link,
                                    and read requests in Telegram.
                                </p>
                                <Button
                                    asChild
                                    size="lg"
                                    variant="highlight"
                                    className="mt-6"
                                >
                                    <Link href={startSelling}>
                                        Start selling
                                    </Link>
                                </Button>
                            </div>
                            <div>
                                <h2 className="text-2xl font-bold tracking-tight">
                                    For customers
                                </h2>
                                <p className="mt-3 max-w-[42ch] text-white/75 dark:text-muted-foreground">
                                    Open the store link a seller sent you. In
                                    Telegram you are signed in already. On the
                                    web, log in, then send one product or your
                                    cart.
                                </p>
                            </div>
                        </div>
                    </section>
                </main>

                <footer className="px-4 pb-10 md:px-6">
                    <div className="mx-auto flex max-w-6xl flex-col gap-4 border-t pt-8 sm:flex-row sm:items-center sm:justify-between">
                        <Link
                            href={home()}
                            className="flex items-center gap-1 rounded-md"
                            aria-label="Vendly home"
                        >
                            <AppLogo />
                        </Link>
                        <p className="text-sm text-muted-foreground">
                            Small shops on the web and in Telegram.
                        </p>
                    </div>
                </footer>
            </div>
        </>
    );
}
