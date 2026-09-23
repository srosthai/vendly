import { Badge } from '@/components/ui/badge';
import { dollars } from '@/lib/format';

export type MarketingPlan = {
    id: number;
    name: string;
    price_cents: number;
    product_limit: number;
    is_default: boolean;
};

export function PlanCard({ plan }: { plan: MarketingPlan }) {
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
