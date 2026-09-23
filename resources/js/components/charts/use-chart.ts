import { useEffect, useRef, useState } from 'react';
import type { RefObject } from 'react';

/**
 * The width of an element, kept up to date as it resizes, so charts draw in
 * real pixels and their text never stretches.
 */
export function useWidth<T extends HTMLElement>(): [
    RefObject<T | null>,
    number,
] {
    const ref = useRef<T>(null);
    const [width, setWidth] = useState(0);

    useEffect(() => {
        const element = ref.current;

        if (!element) {
            return;
        }

        const observer = new ResizeObserver(([entry]) =>
            setWidth(Math.round(entry.contentRect.width)),
        );
        observer.observe(element);

        return () => observer.disconnect();
    }, []);

    return [ref, width];
}

/**
 * True once the element has scrolled into view, so a chart animates when
 * it is seen, not while it is off screen. Reduced motion shows it at once.
 */
export function useShown(ref: RefObject<HTMLElement | null>): boolean {
    const [shown, setShown] = useState(false);

    useEffect(() => {
        const element = ref.current;

        if (
            !element ||
            window.matchMedia('(prefers-reduced-motion: reduce)').matches
        ) {
            setShown(true);

            return;
        }

        const observer = new IntersectionObserver(
            ([entry]) => {
                if (entry.isIntersecting) {
                    setShown(true);
                    observer.disconnect();
                }
            },
            { threshold: 0.25 },
        );
        observer.observe(element);

        return () => observer.disconnect();
    }, [ref]);

    return shown;
}

/**
 * A round top for an axis: 1, 2, 2.5, or 5 times a power of ten.
 */
export function niceMax(value: number): number {
    if (value <= 0) {
        return 1;
    }

    const power = 10 ** Math.floor(Math.log10(value));

    for (const step of [1, 2, 2.5, 5, 10]) {
        if (value <= step * power) {
            return step * power;
        }
    }

    return 10 * power;
}

export function shortDate(iso: string): string {
    return new Date(`${iso}T00:00:00`).toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
    });
}
