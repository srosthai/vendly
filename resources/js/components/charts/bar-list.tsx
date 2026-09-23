import { useRef } from 'react';
import { useShown } from '@/components/charts/use-chart';

/**
 * A ranked list with a bar behind each count, for things like the most
 * requested products. It is a plain list, so screen readers read it as is.
 */
export function BarList({
    label,
    items,
    unit,
}: {
    label: string;
    items: { name: string; value: number }[];
    unit: [string, string];
}) {
    const ref = useRef<HTMLOListElement>(null);
    const shown = useShown(ref);
    const max = Math.max(1, ...items.map((item) => item.value));

    return (
        <ol ref={ref} aria-label={label} className="grid gap-2">
            {items.map((item, index) => (
                <li
                    key={item.name}
                    className="relative overflow-hidden rounded-xl"
                >
                    <span
                        aria-hidden="true"
                        className="absolute inset-y-0 left-0 rounded-xl bg-primary/15 transition-[width] duration-700 ease-out motion-reduce:transition-none dark:bg-primary/30"
                        style={{
                            width: shown
                                ? `${Math.max(6, (item.value / max) * 100)}%`
                                : '0%',
                            transitionDelay: shown
                                ? `${index * 80}ms`
                                : undefined,
                        }}
                    />
                    <span className="relative flex items-center justify-between gap-3 px-3 py-2.5 text-sm">
                        <span className="truncate font-medium">
                            {item.name}
                        </span>
                        <span className="shrink-0 text-muted-foreground tabular-nums">
                            {item.value} {item.value === 1 ? unit[0] : unit[1]}
                        </span>
                    </span>
                </li>
            ))}
        </ol>
    );
}
