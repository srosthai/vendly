import { useId, useState } from 'react';
import type { KeyboardEvent } from 'react';
import {
    niceMax,
    shortDate,
    useShown,
    useWidth,
} from '@/components/charts/use-chart';
import { cn } from '@/lib/utils';

const height = 180;
const basePadding = { top: 12, right: 8, bottom: 28 };

/**
 * One bar per day. Point at a bar, or focus the chart and use the arrow
 * keys, to read it. Bars grow in when the chart is first seen.
 */
export function BarChart({
    label,
    days,
    values,
    format = (value) => String(value),
}: {
    label: string;
    days: string[];
    values: number[];
    format?: (value: number) => string;
}) {
    const id = useId();
    const [ref, width] = useWidth<HTMLDivElement>();
    const shown = useShown(ref);
    const [active, setActive] = useState<number | null>(null);
    const max = niceMax(Math.max(0, ...values));
    const longestLabel = Math.max(
        ...[0, 0.5, 1].map((step) => format(max * step).length),
    );
    const padding = {
        ...basePadding,
        left: Math.max(28, longestLabel * 7 + 14),
    };
    const innerWidth = Math.max(0, width - padding.left - padding.right);
    const innerHeight = height - padding.top - padding.bottom;
    const slot = values.length > 0 ? innerWidth / values.length : 0;
    const barWidth = Math.max(2, slot * 0.62);

    function onKeyDown(event: KeyboardEvent<HTMLDivElement>) {
        if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
            event.preventDefault();
            setActive((index) => {
                const current = index ?? values.length - 1;

                return event.key === 'ArrowLeft'
                    ? Math.max(0, current - 1)
                    : Math.min(values.length - 1, current + 1);
            });
        } else if (event.key === 'Escape') {
            setActive(null);
        }
    }

    const total = values.reduce((sum, value) => sum + value, 0);

    return (
        <figure className="m-0">
            <div
                ref={ref}
                role="group"
                aria-label={`${label}. ${format(total)} over ${values.length} days. Use the arrow keys to read each day.`}
                aria-describedby={`${id}-table`}
                tabIndex={0}
                onKeyDown={onKeyDown}
                onFocus={() => setActive((index) => index ?? values.length - 1)}
                onBlur={() => setActive(null)}
                onPointerLeave={() => setActive(null)}
                className="relative rounded-xl outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                style={{ height }}
            >
                {width > 0 ? (
                    <svg width={width} height={height} aria-hidden="true">
                        {[0, 0.5, 1].map((step) => {
                            const y =
                                padding.top + innerHeight - step * innerHeight;

                            return (
                                <g key={step}>
                                    <line
                                        x1={padding.left}
                                        x2={width - padding.right}
                                        y1={y}
                                        y2={y}
                                        className="stroke-border"
                                        strokeDasharray={
                                            step === 0 ? undefined : '3 4'
                                        }
                                    />
                                    <text
                                        x={padding.left - 8}
                                        y={y}
                                        textAnchor="end"
                                        dominantBaseline="middle"
                                        className="fill-muted-foreground text-[12px]"
                                    >
                                        {format(max * step)}
                                    </text>
                                </g>
                            );
                        })}
                        {values.map((value, index) => {
                            const barHeight = (value / max) * innerHeight;

                            return (
                                <rect
                                    key={days[index] ?? index}
                                    x={
                                        padding.left +
                                        index * slot +
                                        (slot - barWidth) / 2
                                    }
                                    y={
                                        padding.top +
                                        innerHeight -
                                        (shown ? barHeight : 0)
                                    }
                                    width={barWidth}
                                    height={
                                        shown
                                            ? Math.max(
                                                  value > 0 ? 2 : 0,
                                                  barHeight,
                                              )
                                            : 0
                                    }
                                    rx={Math.min(4, barWidth / 2)}
                                    onPointerEnter={() => setActive(index)}
                                    className={cn(
                                        'fill-primary transition-[y,height,opacity] duration-700 ease-out motion-reduce:transition-none',
                                        active !== null &&
                                            active !== index &&
                                            'opacity-40',
                                    )}
                                    style={{
                                        transitionDelay: shown
                                            ? `${index * 12}ms`
                                            : undefined,
                                    }}
                                />
                            );
                        })}
                        {[
                            0,
                            Math.floor((days.length - 1) / 2),
                            days.length - 1,
                        ].map((index) =>
                            days[index] ? (
                                <text
                                    key={index}
                                    x={padding.left + index * slot + slot / 2}
                                    y={height - 8}
                                    textAnchor={
                                        index === 0
                                            ? 'start'
                                            : index === days.length - 1
                                              ? 'end'
                                              : 'middle'
                                    }
                                    className="fill-muted-foreground text-[12px]"
                                >
                                    {shortDate(days[index])}
                                </text>
                            ) : null,
                        )}
                    </svg>
                ) : null}
                {active !== null && width > 0 ? (
                    <div
                        className="pointer-events-none absolute top-0 z-10 -translate-x-1/2 rounded-xl border bg-popover px-3 py-2 text-sm whitespace-nowrap text-popover-foreground shadow-md"
                        style={{
                            left: Math.min(
                                Math.max(
                                    padding.left + active * slot + slot / 2,
                                    60,
                                ),
                                width - 60,
                            ),
                        }}
                        aria-live="polite"
                    >
                        <span className="block text-muted-foreground">
                            {shortDate(days[active] ?? '')}
                        </span>
                        <span className="font-semibold tabular-nums">
                            {format(values[active] ?? 0)}
                        </span>
                    </div>
                ) : null}
            </div>
            <table id={`${id}-table`} className="sr-only">
                <caption>{label}</caption>
                <thead>
                    <tr>
                        <th scope="col">Day</th>
                        <th scope="col">Value</th>
                    </tr>
                </thead>
                <tbody>
                    {days.map((day, index) => (
                        <tr key={day}>
                            <td>{shortDate(day)}</td>
                            <td>{format(values[index] ?? 0)}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </figure>
    );
}
