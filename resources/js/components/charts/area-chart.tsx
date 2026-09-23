import { useId, useState } from 'react';
import type { KeyboardEvent } from 'react';
import {
    niceMax,
    shortDate,
    useShown,
    useWidth,
} from '@/components/charts/use-chart';
import { cn } from '@/lib/utils';

const height = 200;
const basePadding = { top: 12, right: 8, bottom: 28 };

/**
 * A daily series as a filled line. Point at it, or focus it and use the
 * arrow keys, to read each day. Screen readers get a summary and the full
 * table of days.
 */
export function AreaChart({
    label,
    days,
    values,
    format = (value) => String(value),
    tone = 'primary',
}: {
    label: string;
    days: string[];
    values: number[];
    format?: (value: number) => string;
    tone?: 'primary' | 'highlight';
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
    const x = (index: number) =>
        padding.left +
        (values.length > 1 ? (index / (values.length - 1)) * innerWidth : 0);
    const y = (value: number) =>
        padding.top + innerHeight - (value / max) * innerHeight;

    const line = values
        .map(
            (value, index) =>
                `${index === 0 ? 'M' : 'L'}${x(index)},${y(value)}`,
        )
        .join(' ');
    const area = `${line} L${x(values.length - 1)},${padding.top + innerHeight} L${x(0)},${padding.top + innerHeight} Z`;
    const stroke = tone === 'primary' ? 'var(--primary)' : 'var(--highlight)';

    function pick(clientX: number, element: HTMLElement) {
        const box = element.getBoundingClientRect();
        const ratio = (clientX - box.left - padding.left) / innerWidth;
        setActive(
            Math.max(
                0,
                Math.min(
                    values.length - 1,
                    Math.round(ratio * (values.length - 1)),
                ),
            ),
        );
    }

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
                onPointerMove={(event) =>
                    pick(event.clientX, event.currentTarget)
                }
                onPointerLeave={() => setActive(null)}
                className="relative rounded-xl outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                style={{ height }}
            >
                {width > 0 ? (
                    <svg width={width} height={height} aria-hidden="true">
                        <defs>
                            <linearGradient
                                id={`${id}-fill`}
                                x1="0"
                                x2="0"
                                y1="0"
                                y2="1"
                            >
                                <stop
                                    offset="0%"
                                    stopColor={stroke}
                                    stopOpacity="0.28"
                                />
                                <stop
                                    offset="100%"
                                    stopColor={stroke}
                                    stopOpacity="0"
                                />
                            </linearGradient>
                        </defs>
                        {[0, 0.5, 1].map((step) => (
                            <g key={step}>
                                <line
                                    x1={padding.left}
                                    x2={width - padding.right}
                                    y1={y(max * step)}
                                    y2={y(max * step)}
                                    className="stroke-border"
                                    strokeDasharray={
                                        step === 0 ? undefined : '3 4'
                                    }
                                />
                                <text
                                    x={padding.left - 8}
                                    y={y(max * step)}
                                    textAnchor="end"
                                    dominantBaseline="middle"
                                    className="fill-muted-foreground text-[12px]"
                                >
                                    {format(max * step)}
                                </text>
                            </g>
                        ))}
                        {[
                            0,
                            Math.floor((days.length - 1) / 2),
                            days.length - 1,
                        ].map((index) =>
                            days[index] ? (
                                <text
                                    key={index}
                                    x={x(index)}
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
                        <path
                            d={area}
                            fill={`url(#${id}-fill)`}
                            className={cn(
                                'transition-opacity duration-700 motion-reduce:transition-none',
                                shown ? 'opacity-100' : 'opacity-0',
                            )}
                        />
                        <path
                            d={line}
                            fill="none"
                            stroke={stroke}
                            strokeWidth={2.5}
                            strokeLinejoin="round"
                            strokeLinecap="round"
                            pathLength={1}
                            strokeDasharray="1 1"
                            strokeDashoffset={shown ? 0 : 1}
                            className="transition-[stroke-dashoffset] duration-1000 ease-out motion-reduce:transition-none"
                        />
                        {active !== null ? (
                            <g>
                                <line
                                    x1={x(active)}
                                    x2={x(active)}
                                    y1={padding.top}
                                    y2={padding.top + innerHeight}
                                    className="stroke-muted-foreground/40"
                                />
                                <circle
                                    cx={x(active)}
                                    cy={y(values[active] ?? 0)}
                                    r={5}
                                    fill={stroke}
                                    className="stroke-card"
                                    strokeWidth={2}
                                />
                            </g>
                        ) : null}
                    </svg>
                ) : null}
                {active !== null && width > 0 ? (
                    <div
                        className="pointer-events-none absolute top-0 z-10 -translate-x-1/2 rounded-xl border bg-popover px-3 py-2 text-sm whitespace-nowrap text-popover-foreground shadow-md"
                        style={{
                            left: Math.min(Math.max(x(active), 70), width - 70),
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
