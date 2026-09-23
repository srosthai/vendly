import * as React from 'react';
import { cn } from '@/lib/utils';

/**
 * A plain progress bar with the shadcn look, without a Radix dependency.
 */
function Progress({
    value,
    max = 100,
    className,
    indicatorClassName,
    ...props
}: Omit<React.ComponentProps<'div'>, 'children'> & {
    value: number;
    max?: number;
    indicatorClassName?: string;
}) {
    const percent = max <= 0 ? 0 : Math.min(100, Math.max(0, (value / max) * 100));

    return (
        <div
            data-slot="progress"
            role="progressbar"
            aria-valuemin={0}
            aria-valuemax={max}
            aria-valuenow={value}
            className={cn(
                'relative h-2 w-full overflow-hidden rounded-full bg-muted',
                className,
            )}
            {...props}
        >
            <div
                data-slot="progress-indicator"
                className={cn(
                    'h-full rounded-full bg-primary transition-[width] duration-500 ease-out',
                    indicatorClassName,
                )}
                style={{ width: `${percent}%` }}
            />
        </div>
    );
}

export { Progress };
