import type { BillingPeriod } from '@/lib/billing';
import { cn } from '@/lib/utils';

const periods: { value: BillingPeriod; label: string }[] = [
    { value: 'monthly', label: 'Monthly' },
    { value: 'yearly', label: 'Yearly' },
];

/**
 * Two segments, Monthly and Yearly, for comparing plan prices. `note` sits
 * beside Yearly, such as how much a year saves.
 */
export function BillingPeriodSwitch({
    value,
    onChange,
    note,
    className,
}: {
    value: BillingPeriod;
    onChange: (period: BillingPeriod) => void;
    note?: string;
    className?: string;
}) {
    return (
        <div
            role="group"
            aria-label="Billing period"
            className={cn(
                'inline-flex items-center gap-1 rounded-full border bg-card p-1',
                className,
            )}
        >
            {periods.map((period) => {
                const active = value === period.value;

                return (
                    <button
                        key={period.value}
                        type="button"
                        aria-pressed={active}
                        onClick={() => onChange(period.value)}
                        className={cn(
                            'inline-flex min-h-10 items-center gap-2 rounded-full px-4 text-sm font-medium transition-colors outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50',
                            active
                                ? 'bg-primary text-primary-foreground'
                                : 'text-muted-foreground hover:text-foreground',
                        )}
                    >
                        {period.label}
                        {period.value === 'yearly' && note ? (
                            <span
                                className={cn(
                                    'rounded-full px-2 py-0.5 text-sm',
                                    active
                                        ? 'bg-primary-foreground/15'
                                        : 'bg-highlight/20 text-foreground',
                                )}
                            >
                                {note}
                            </span>
                        ) : null}
                    </button>
                );
            })}
        </div>
    );
}
