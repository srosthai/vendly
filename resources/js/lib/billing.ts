export type BillingPeriod = 'monthly' | 'yearly';

type PricedPlan = { price_cents: number; yearly_price_cents: number | null };

/**
 * Whether a plan can be bought for a year.
 */
export function offersYearly(plan: PricedPlan): boolean {
    return (
        plan.price_cents > 0 &&
        plan.yearly_price_cents !== null &&
        plan.yearly_price_cents > 0
    );
}

/**
 * What paying yearly saves against twelve monthly payments, from the
 * admin's own prices. Zero when there is no saving.
 */
export function yearlySaving(plan: PricedPlan): number {
    if (!offersYearly(plan) || plan.yearly_price_cents === null) {
        return 0;
    }

    return Math.max(0, plan.price_cents * 12 - plan.yearly_price_cents);
}
