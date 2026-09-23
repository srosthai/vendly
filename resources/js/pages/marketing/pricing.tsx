import { Check } from 'lucide-react';
import { useState } from 'react';
import { BillingPeriodSwitch } from '@/components/billing-period-switch';
import { Faq } from '@/components/marketing/faq';
import { MarketingHead } from '@/components/marketing/marketing-head';
import type { PageMeta } from '@/components/marketing/marketing-head';
import { PlanCard } from '@/components/marketing/plan-card';
import type { MarketingPlan } from '@/components/marketing/plan-card';
import { Reveal } from '@/components/marketing/reveal';
import { CtaBand, SectionHeading } from '@/components/marketing/section';
import { offersYearly, yearlySaving } from '@/lib/billing';
import type { BillingPeriod } from '@/lib/billing';
import { dollars } from '@/lib/format';

const everyPlan = [
    'A web store and a Telegram mini app link',
    'Unlimited drafts',
    'Categories, brands, photos, and stock',
    'Buy and cart requests in your Telegram chat',
    'A copy of every request kept by the Vendly admin',
    'Light and dark themes for your customers',
];

const faq = [
    {
        question: 'How do I pay for a plan?',
        answer: 'Choose a plan in your dashboard and scan the QR in your banking app, or open the payment page. Payments go through CutLuy and are in US dollars.',
    },
    {
        question: 'When does a paid plan start?',
        answer: 'As soon as CutLuy confirms the payment. Opening the QR in your banking app is not enough; the plan starts once the payment is complete.',
    },
    {
        question: 'How long does a plan last?',
        answer: 'One month from a monthly payment, or twelve months from a yearly one where a plan offers it. Paying again before it ends adds the new time on top.',
    },
    {
        question: 'What happens if I do not renew?',
        answer: 'Your store stays online and published products stay visible. You cannot publish new products until you pay again.',
    },
    {
        question: 'Does Vendly take a share of my sales?',
        answer: 'No. Customers pay you directly, the way you agree in Telegram. Plans only change how many products you can publish.',
    },
];

export default function Pricing({
    meta,
    plans,
}: {
    meta: PageMeta;
    plans: MarketingPlan[];
}) {
    const [period, setPeriod] = useState<BillingPeriod>('monthly');
    const largestLimit = Math.max(
        0,
        ...plans.map((plan) => plan.product_limit),
    );
    const anyYearly = plans.some(offersYearly);
    const bestSaving = Math.max(0, ...plans.map(yearlySaving));
    const featuredId = plans
        .filter((plan) => plan.price_cents > 0)
        .sort((a, b) => a.price_cents - b.price_cents)[0]?.id;

    return (
        <>
            <MarketingHead meta={meta} />
            <section className="px-4 pt-16 pb-10 md:px-6 lg:pt-24">
                <div className="mx-auto max-w-6xl">
                    <SectionHeading
                        as="h1"
                        title="Pay for more room, not for sales"
                        description="Every store starts free. Plans only change how many products you can publish, and Vendly takes nothing from what you sell."
                    />
                    {plans.length === 0 ? (
                        <p className="mt-10 text-muted-foreground">
                            Plans are being set up. Start selling and you will
                            see them in your dashboard.
                        </p>
                    ) : (
                        <>
                            {anyYearly ? (
                                <BillingPeriodSwitch
                                    value={period}
                                    onChange={setPeriod}
                                    note={
                                        bestSaving > 0
                                            ? `Save up to ${dollars(bestSaving)}`
                                            : undefined
                                    }
                                    className="mt-10"
                                />
                            ) : null}
                            <div className="mt-8 flex flex-wrap justify-center gap-4">
                                {plans.map((plan, index) => (
                                    <Reveal
                                        key={plan.id}
                                        delay={(index % 3) * 90}
                                        className="w-full md:w-[calc(50%-0.5rem)] lg:w-[calc(33.333%-0.667rem)]"
                                    >
                                        <PlanCard
                                            plan={plan}
                                            largestLimit={largestLimit}
                                            period={period}
                                            featured={plan.id === featuredId}
                                        />
                                    </Reveal>
                                ))}
                            </div>
                        </>
                    )}
                </div>
            </section>
            <section className="bg-card px-4 py-16 md:px-6">
                <div className="mx-auto grid max-w-6xl gap-10 lg:grid-cols-[minmax(0,20rem)_1fr]">
                    <SectionHeading
                        title="Every plan includes"
                        description="Plans differ only in how many products can be live."
                    />
                    <ul className="grid gap-3 sm:grid-cols-2">
                        {everyPlan.map((item, index) => (
                            <Reveal
                                as="li"
                                key={item}
                                delay={(index % 2) * 80}
                                className="flex items-start gap-3 rounded-2xl border bg-background p-4"
                            >
                                <span className="flex size-6 shrink-0 items-center justify-center rounded-full bg-success/12 text-success">
                                    <Check
                                        className="size-4"
                                        aria-hidden="true"
                                    />
                                </span>
                                {item}
                            </Reveal>
                        ))}
                    </ul>
                </div>
            </section>
            <section className="px-4 py-16 md:px-6">
                <div className="mx-auto grid max-w-6xl gap-10 lg:grid-cols-[minmax(0,20rem)_1fr]">
                    <SectionHeading title="Billing questions" />
                    <Faq items={faq} />
                </div>
            </section>
            <CtaBand />
        </>
    );
}
