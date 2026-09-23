import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { Card } from '@/components/ui/card';
import { cn } from '@/lib/utils';

/**
 * A small line of real daily values, drawn as plain SVG. It is only shown
 * when there is a series to draw.
 */
function TrendLine({ points, label }: { points: number[]; label: string }) {
    const width = 96;
    const height = 36;
    const max = Math.max(...points, 1);
    const step = points.length > 1 ? width / (points.length - 1) : width;
    const path = points
        .map((point, index) => {
            const x = index * step;
            const y = height - 3 - (point / max) * (height - 6);

            return `${index === 0 ? 'M' : 'L'}${x.toFixed(1)},${y.toFixed(1)}`;
        })
        .join(' ');

    return (
        <svg
            viewBox={`0 0 ${width} ${height}`}
            width={width}
            height={height}
            role="img"
            aria-label={label}
            className="shrink-0 overflow-visible text-primary"
        >
            <path
                d={`${path} L${width},${height} L0,${height} Z`}
                className="fill-primary/10"
            />
            <path
                d={path}
                fill="none"
                stroke="currentColor"
                strokeWidth={2}
                strokeLinecap="round"
                strokeLinejoin="round"
            />
        </svg>
    );
}

export function StatCard({
    label,
    value,
    hint,
    icon: Icon,
    trend,
    trendLabel,
    tone = 'default',
}: {
    label: string;
    value: ReactNode;
    hint?: ReactNode;
    icon?: LucideIcon;
    trend?: number[];
    trendLabel?: string;
    tone?: 'default' | 'warning';
}) {
    return (
        <Card className="gap-3 p-5">
            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                {Icon ? (
                    <span className="flex size-8 items-center justify-center rounded-full bg-secondary text-secondary-foreground">
                        <Icon className="size-4" aria-hidden="true" />
                    </span>
                ) : null}
                {label}
            </div>
            <div className="flex items-end justify-between gap-3">
                <div className="min-w-0">
                    <p className="text-2xl font-bold tracking-tight tabular-nums">
                        {value}
                    </p>
                    {hint ? (
                        <p
                            className={cn(
                                'mt-1 text-sm',
                                tone === 'warning'
                                    ? 'text-warning'
                                    : 'text-muted-foreground',
                            )}
                        >
                            {hint}
                        </p>
                    ) : null}
                </div>
                {trend && trend.some((point) => point > 0) ? (
                    <TrendLine points={trend} label={trendLabel ?? label} />
                ) : null}
            </div>
        </Card>
    );
}
