import { useRef, useState } from 'react';
import { useShown } from '@/components/charts/use-chart';
import { cn } from '@/lib/utils';

export type Slice = {
    key: string;
    label: string;
    value: number;
    color: string;
};

/**
 * Parts of a whole as a ring, with the total in the middle and a legend
 * that names each part with its count and share. Hovering or focusing a
 * legend row highlights its part.
 */
export function DonutChart({
    label,
    slices,
    centerLabel,
}: {
    label: string;
    slices: Slice[];
    centerLabel: string;
}) {
    const ref = useRef<HTMLDivElement>(null);
    const shown = useShown(ref);
    const [active, setActive] = useState<string | null>(null);
    const total = slices.reduce((sum, slice) => sum + slice.value, 0);
    const radius = 48;
    const circumference = 2 * Math.PI * radius;
    const gap = slices.length > 1 ? 2 : 0;
    let offset = 0;

    return (
        <figure
            className="@container m-0"
            aria-label={`${label}: ${slices.map((slice) => `${slice.label} ${slice.value}`).join(', ')}`}
        >
            <div className="flex flex-col items-center gap-5 @sm:flex-row">
                <div ref={ref} className="relative size-40 shrink-0">
                    <svg
                        viewBox="0 0 120 120"
                        className="size-full -rotate-90"
                        aria-hidden="true"
                    >
                        <circle
                            cx="60"
                            cy="60"
                            r={radius}
                            fill="none"
                            strokeWidth="14"
                            className="stroke-muted"
                        />
                        {slices.map((slice) => {
                            const length =
                                total > 0
                                    ? (slice.value / total) * circumference
                                    : 0;
                            const dash = Math.max(0, length - gap);
                            const start = offset;
                            offset += length;

                            return (
                                <circle
                                    key={slice.key}
                                    cx="60"
                                    cy="60"
                                    r={radius}
                                    fill="none"
                                    stroke={slice.color}
                                    strokeWidth={active === slice.key ? 18 : 14}
                                    strokeDasharray={`${shown ? dash : 0} ${circumference}`}
                                    strokeDashoffset={-start}
                                    className={cn(
                                        'transition-[stroke-dasharray,stroke-width,opacity] duration-700 ease-out motion-reduce:transition-none',
                                        active !== null &&
                                            active !== slice.key &&
                                            'opacity-40',
                                    )}
                                />
                            );
                        })}
                    </svg>
                    <div className="absolute inset-0 flex flex-col items-center justify-center text-center">
                        <span className="text-2xl font-bold tabular-nums">
                            {total}
                        </span>
                        <span className="text-sm text-muted-foreground">
                            {centerLabel}
                        </span>
                    </div>
                </div>
                <ul className="grid w-full gap-1">
                    {slices.map((slice) => (
                        <li
                            key={slice.key}
                            tabIndex={0}
                            onPointerEnter={() => setActive(slice.key)}
                            onPointerLeave={() => setActive(null)}
                            onFocus={() => setActive(slice.key)}
                            onBlur={() => setActive(null)}
                            className={cn(
                                'flex items-center justify-between gap-3 rounded-xl px-3 py-2 text-sm outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50',
                                active === slice.key && 'bg-accent',
                            )}
                        >
                            <span className="flex items-center gap-2">
                                <span
                                    className="size-2.5 rounded-full"
                                    style={{ background: slice.color }}
                                    aria-hidden="true"
                                />
                                {slice.label}
                            </span>
                            <span className="tabular-nums">
                                <span className="font-semibold">
                                    {slice.value}
                                </span>
                                <span className="ml-2 text-muted-foreground">
                                    {total > 0
                                        ? Math.round(
                                              (slice.value / total) * 100,
                                          )
                                        : 0}
                                    %
                                </span>
                            </span>
                        </li>
                    ))}
                </ul>
            </div>
        </figure>
    );
}
