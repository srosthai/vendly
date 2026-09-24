import { Link } from '@inertiajs/react';
import { Check } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { offersYearly, yearlySaving } from '@/lib/billing';
import type { BillingPeriod } from '@/lib/billing';
import { dollars } from '@/lib/format';
import { cn } from '@/lib/utils';
import { register } from '@/routes';

export type MarketingPlan = {
    id: number;
    name: string;
    price_cents: number;
    yearly_price_cents: number | null;
    product_limit: number;
    is_default: boolean;
};

/**
 * One plan. The capacity bar shows its product limit against the largest
 * plan (on a square-root scale, so small plans stay visible), which is the
 * only thing plans differ on, so the choice reads at a glance. The first paid plan is lifted as the usual next step.
 */
export function PlanCard({
    plan,
    largestLimit,
    period = 'monthly',
    featured = false,
}: {
    plan: MarketingPlan;
    largestLimit: number;
    period?: BillingPeriod;
    featured?: boolean;
}) {
    const free = plan.price_cents === 0;
    const yearly = period === 'yearly' && offersYearly(plan);
    const saving = yearlySaving(plan);
    const muted = featured
        ? 'text-background/70 dark:text-muted-foreground'
        : 'text-muted-foreground';
    const share = Math.max(
        4,
        Math.round(
            Math.sqrt(plan.product_limit / Math.max(largestLimit, 1)) * 100,
        ),
    );

    return (
        <article
            className={cn(
                'relative flex h-full flex-col rounded-3xl border p-7 transition-[translate,box-shadow,border-color] duration-200 ease-out hover:-translate-y-1.5 motion-reduce:transition-none motion-reduce:hover:translate-y-0',
                featured
                    ? 'border-transparent bg-foreground text-background shadow-xl hover:shadow-2xl hover:shadow-primary/25 dark:border-primary/60 dark:bg-card dark:text-foreground'
                    : 'bg-card hover:border-primary/40 hover:shadow-xl hover:shadow-primary/10',
            )}
        >
            <div className="flex items-center justify-between gap-3">
                <h3 className="text-lg font-semibold">{plan.name}</h3>
                {plan.is_default ? (
                    <span className="rounded-full bg-success/12 px-3 py-1 text-sm font-medium text-success">
                        Every store starts here
                    </span>
                ) : featured ? (
                    <span className="rounded-full bg-highlight px-3 py-1 text-sm font-medium text-highlight-foreground">
                        Next step up
                    </span>
                ) : null}
            </div>

            <div className="mt-6">
                <p className="flex items-baseline gap-1.5">
                    <span className="text-5xl font-bold tracking-tight tabular-nums">
                        {free
                            ? '$0'
                            : dollars(
                                  yearly
                                      ? (plan.yearly_price_cents ?? 0)
                                      : plan.price_cents,
                              )}
                    </span>
                    <span className={muted}>
                        {yearly ? 'per year' : 'per month'}
                    </span>
                </p>
                <p className={cn('mt-2 min-h-6 text-sm', muted)}>
                    {yearly ? (
                        <>
                            About{' '}
                            {dollars(
                                Math.round((plan.yearly_price_cents ?? 0) / 12),
                            )}{' '}
                            a month
                            {saving > 0 ? (
                                <span className="ml-2 rounded-full bg-success/15 px-2 py-0.5 font-medium text-success">
                                    Save {dollars(saving)}
                                </span>
                            ) : null}
                        </>
                    ) : period === 'yearly' && !free ? (
                        'Monthly only'
                    ) : null}
                </p>
            </div>

            <div className="mt-8">
                <p className="flex items-baseline justify-between gap-3">
                    <span className="text-3xl font-bold tabular-nums">
                        {plan.product_limit}
                    </span>
                    <span
                        className={cn(
                            'text-sm',
                            featured
                                ? 'text-background/70 dark:text-muted-foreground'
                                : 'text-muted-foreground',
                        )}
                    >
                        live products
                    </span>
                </p>
                <div
                    className={cn(
                        'mt-3 h-2 overflow-hidden rounded-full',
                        featured
                            ? 'bg-background/15 dark:bg-muted'
                            : 'bg-muted',
                    )}
                    aria-hidden="true"
                >
                    <div
                        className={cn(
                            'h-full rounded-full',
                            featured ? 'bg-highlight' : 'bg-primary',
                        )}
                        style={{ width: `${share}%` }}
                    />
                </div>
            </div>

            <ul
                className={cn(
                    'mt-8 flex flex-1 flex-col gap-3 text-sm',
                    featured
                        ? 'text-background/80 dark:text-muted-foreground'
                        : 'text-muted-foreground',
                )}
            >
                <li className="flex gap-2.5">
                    <Check
                        className="size-4 shrink-0 translate-y-0.5 text-success"
                        aria-hidden="true"
                    />
                    Unlimited drafts
                </li>
                <li className="flex gap-2.5">
                    <Check
                        className="size-4 shrink-0 translate-y-0.5 text-success"
                        aria-hidden="true"
                    />
                    {free
                        ? 'No payment needed'
                        : `About ${dollars(Math.round(plan.price_cents / Math.max(plan.product_limit, 1)))} per live product a month`}
                </li>
                <li className="flex gap-2.5">
                    <Check
                        className="size-4 shrink-0 translate-y-0.5 text-success"
                        aria-hidden="true"
                    />
                    {free
                        ? 'Upgrade any time from your dashboard'
                        : yearly
                          ? 'Paid yearly by Cambodia QR'
                          : 'Paid monthly by Cambodia QR'}
                </li>
            </ul>

            <Button
                asChild
                size="lg"
                variant={featured ? 'default' : 'outline'}
                className={cn(
                    'mt-8',
                    featured &&
                        'bg-background text-foreground hover:bg-background/90 dark:bg-primary dark:text-primary-foreground dark:hover:bg-primary/90',
                )}
            >
                <Link href={register({ query: { next: 'sell' } })}>
                    {free ? 'Start free' : `Start with ${plan.name}`}
                </Link>
            </Button>
        </article>
    );
}
