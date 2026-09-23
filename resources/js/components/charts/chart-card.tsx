import { ArrowDownRight, ArrowUpRight } from 'lucide-react';
import type { ReactNode } from 'react';
import { Card, CardDescription, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';

/**
 * How this period compares with the one before, from real totals only. It
 * says so plainly when there is nothing to compare with.
 */
export function Delta({
    current,
    previous,
}: {
    current: number;
    previous: number;
}) {
    if (previous === 0) {
        return (
            <span className="text-sm text-muted-foreground">
                {current === 0 ? 'Nothing yet' : 'New this period'}
            </span>
        );
    }

    const change = Math.round(((current - previous) / previous) * 100);
    const up = change >= 0;

    return (
        <span
            className={cn(
                'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-sm font-medium tabular-nums',
                up
                    ? 'bg-success/12 text-success'
                    : 'bg-destructive/10 text-destructive',
            )}
        >
            {up ? (
                <ArrowUpRight className="size-3.5" aria-hidden="true" />
            ) : (
                <ArrowDownRight className="size-3.5" aria-hidden="true" />
            )}
            {up ? '+' : ''}
            {change}%
            <span className="sr-only"> compared with the 30 days before</span>
        </span>
    );
}

/**
 * A card that frames one chart: a title, the headline number, and how it
 * compares.
 */
export function ChartCard({
    title,
    description,
    value,
    delta,
    className,
    children,
}: {
    title: string;
    description?: string;
    value?: ReactNode;
    delta?: ReactNode;
    className?: string;
    children: ReactNode;
}) {
    return (
        <Card className={cn('gap-4 p-5 sm:p-6', className)}>
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <CardTitle>{title}</CardTitle>
                    {description ? (
                        <CardDescription className="mt-1">
                            {description}
                        </CardDescription>
                    ) : null}
                </div>
                {value !== undefined || delta ? (
                    <div className="flex flex-col items-end gap-1">
                        {value !== undefined ? (
                            <span className="text-2xl font-bold tracking-tight tabular-nums">
                                {value}
                            </span>
                        ) : null}
                        {delta}
                    </div>
                ) : null}
            </div>
            {children}
        </Card>
    );
}
