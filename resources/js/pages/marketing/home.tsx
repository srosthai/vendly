import { Link } from '@inertiajs/react';
import {
    ArrowRight,
    Boxes,
    Link2,
    MessageSquareText,
    Moon,
    QrCode,
    Smartphone,
} from 'lucide-react';
import { HowItLooks } from '@/components/marketing/how-it-looks';
import { MarketingHead } from '@/components/marketing/marketing-head';
import type { PageMeta } from '@/components/marketing/marketing-head';
import { RecentlyJoined } from '@/components/marketing/recently-joined';
import type { RecentStore } from '@/components/marketing/recently-joined';
import { Reveal } from '@/components/marketing/reveal';
import { CtaBand, SectionHeading } from '@/components/marketing/section';
import { TestimonialCard } from '@/components/marketing/testimonial-card';
import type { MarketingTestimonial } from '@/components/marketing/testimonial-card';
import { Button } from '@/components/ui/button';
import {
    features,
    howItWorks,
    pricing,
    register,
    testimonials as testimonialsPage,
} from '@/routes';

const highlights = [
    {
        icon: Link2,
        title: 'One link, two places',
        body: 'Your store opens in any browser and inside the Telegram mini app.',
    },
    {
        icon: MessageSquareText,
        title: 'Requests in Telegram',
        body: 'Buy and cart requests arrive in your chat, numbered and ready to answer.',
    },
    {
        icon: Boxes,
        title: 'A simple catalog',
        body: 'Categories, brands, photos, stock, and drafts you publish when ready.',
    },
    {
        icon: QrCode,
        title: 'Plans by QR',
        body: 'Start free. Pay for more products by Cambodia QR in your banking app.',
    },
    {
        icon: Smartphone,
        title: 'Made for phones',
        body: 'Your store and your dashboard both work on a small screen.',
    },
    {
        icon: Moon,
        title: 'Light and dark',
        body: 'Every page follows the theme your customers prefer, Telegram included.',
    },
];

const steps = [
    { title: 'Open a store', body: 'Name it, pick its link, add products.' },
    { title: 'Share the link', body: 'Web or Telegram, the same store.' },
    {
        title: 'Read the request',
        body: 'Customers send, you reply in Telegram.',
    },
];

export default function Home({
    recentStores,
    freePlan,
    testimonials,
    meta,
}: {
    recentStores: RecentStore[];
    freePlan: { name: string; product_limit: number } | null;
    testimonials: MarketingTestimonial[];
    meta: PageMeta;
}) {
    const startSelling = register({ query: { next: 'sell' } });

    return (
        <>
            <MarketingHead meta={meta} />

            <section className="relative overflow-hidden">
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[520px] bg-[radial-gradient(60%_60%_at_20%_0%,color-mix(in_oklab,var(--primary)_14%,transparent),transparent),radial-gradient(40%_50%_at_90%_10%,color-mix(in_oklab,var(--highlight)_14%,transparent),transparent)]"
                />
                <div className="mx-auto grid max-w-6xl items-center gap-14 px-4 pt-14 pb-16 md:px-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.05fr)] lg:pt-20">
                    <div className="hero-copy max-w-xl">
                        <h1 className="text-4xl leading-[1.04] font-bold tracking-[-0.03em] sm:text-5xl lg:text-6xl">
                            A shop your customers open from a link.
                        </h1>
                        <p className="mt-5 max-w-[46ch] text-lg leading-relaxed text-muted-foreground">
                            Put your products on one page that works in any
                            browser and inside Telegram. When a customer wants
                            something, the request lands in your chat.
                        </p>
                        <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                            <Button asChild size="lg">
                                <Link href={startSelling}>
                                    Start selling for free
                                </Link>
                            </Button>
                            <Button asChild size="lg" variant="outline">
                                <Link href={howItWorks()}>
                                    See how it works
                                </Link>
                            </Button>
                        </div>
                        <p className="mt-4 text-sm text-muted-foreground">
                            {freePlan
                                ? `The ${freePlan.name} plan publishes up to ${freePlan.product_limit} products with no payment.`
                                : 'Open a store in a minute.'}
                        </p>
                    </div>
                    <HowItLooks />
                </div>
            </section>

            <div className="pb-16">
                <RecentlyJoined stores={recentStores} />
            </div>

            <section className="bg-card px-4 py-20 md:px-6">
                <div className="mx-auto max-w-6xl">
                    <div className="flex flex-wrap items-end justify-between gap-6">
                        <SectionHeading
                            title="Everything a small shop needs, nothing it does not"
                            description="No checkout to set up and no order inbox to manage. Sell the way you already do, from one link."
                        />
                        <Reveal>
                            <Button asChild variant="outline">
                                <Link href={features()}>
                                    All features
                                    <ArrowRight />
                                </Link>
                            </Button>
                        </Reveal>
                    </div>
                    <ul className="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {highlights.map((item, index) => (
                            <Reveal
                                as="li"
                                key={item.title}
                                delay={(index % 3) * 90}
                                className="flex gap-4 rounded-2xl border bg-background p-5"
                            >
                                <span className="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-secondary text-secondary-foreground">
                                    <item.icon
                                        className="size-5"
                                        aria-hidden="true"
                                    />
                                </span>
                                <div>
                                    <h3 className="font-semibold">
                                        {item.title}
                                    </h3>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        {item.body}
                                    </p>
                                </div>
                            </Reveal>
                        ))}
                    </ul>
                </div>
            </section>

            <section className="px-4 py-20 md:px-6">
                <div className="mx-auto grid max-w-6xl items-center gap-12 lg:grid-cols-2">
                    <div>
                        <SectionHeading
                            title="From products to requests in three steps"
                            description="Most sellers open a store and share it the same day."
                        />
                        <Reveal delay={120} className="mt-8">
                            <Button asChild variant="outline">
                                <Link href={howItWorks()}>
                                    How it works
                                    <ArrowRight />
                                </Link>
                            </Button>
                        </Reveal>
                    </div>
                    <ol className="flex flex-col gap-3">
                        {steps.map((step, index) => (
                            <Reveal
                                as="li"
                                key={step.title}
                                delay={index * 120}
                                className="flex items-center gap-4 rounded-2xl border bg-card p-5"
                            >
                                <span
                                    className="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-bold text-primary-foreground"
                                    aria-hidden="true"
                                >
                                    {index + 1}
                                </span>
                                <div>
                                    <h3 className="font-semibold">
                                        {step.title}
                                    </h3>
                                    <p className="text-sm text-muted-foreground">
                                        {step.body}
                                    </p>
                                </div>
                            </Reveal>
                        ))}
                    </ol>
                </div>
            </section>

            {testimonials.length > 0 ? (
                <section className="bg-card px-4 py-20 md:px-6">
                    <div className="mx-auto max-w-6xl">
                        <div className="flex flex-wrap items-end justify-between gap-6">
                            <SectionHeading title="What sellers say" />
                            <Reveal>
                                <Button asChild variant="outline">
                                    <Link href={testimonialsPage()}>
                                        Read more
                                        <ArrowRight />
                                    </Link>
                                </Button>
                            </Reveal>
                        </div>
                        <div className="mt-10 grid gap-4 md:grid-cols-3">
                            {testimonials.map((testimonial, index) => (
                                <Reveal key={testimonial.id} delay={index * 90}>
                                    <TestimonialCard
                                        testimonial={testimonial}
                                    />
                                </Reveal>
                            ))}
                        </div>
                    </div>
                </section>
            ) : null}

            <section className="px-4 md:px-6">
                <div className="mx-auto max-w-6xl">
                    <Reveal className="flex flex-col items-start justify-between gap-6 rounded-3xl border bg-card p-8 sm:flex-row sm:items-center sm:p-10">
                        <div>
                            <h2 className="text-2xl font-bold tracking-tight">
                                Pay for more room, not for sales
                            </h2>
                            <p className="mt-2 max-w-xl text-muted-foreground">
                                Plans only change how many products you can
                                publish. Vendly takes nothing from what you
                                sell.
                            </p>
                        </div>
                        <Button asChild size="lg" variant="outline">
                            <Link href={pricing()}>
                                See pricing
                                <ArrowRight />
                            </Link>
                        </Button>
                    </Reveal>
                </div>
            </section>

            <CtaBand />
        </>
    );
}
