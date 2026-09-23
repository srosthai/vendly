import type { ElementType, ReactNode } from 'react';
import { useEffect, useRef, useState } from 'react';
import { cn } from '@/lib/utils';

/**
 * Fades and lifts its content in once, the first time it scrolls into view.
 * `delay` staggers items in a grid. With reduced motion the content is
 * simply there.
 */
export function Reveal({
    children,
    as: Tag = 'div',
    delay = 0,
    className,
}: {
    children: ReactNode;
    as?: ElementType;
    delay?: number;
    className?: string;
}) {
    const ref = useRef<HTMLElement | null>(null);
    const [visible, setVisible] = useState(false);

    useEffect(() => {
        const element = ref.current;

        if (!element || typeof IntersectionObserver === 'undefined') {
            setVisible(true);

            return;
        }

        const observer = new IntersectionObserver(
            (entries) => {
                if (entries.some((entry) => entry.isIntersecting)) {
                    setVisible(true);
                    observer.disconnect();
                }
            },
            { rootMargin: '0px 0px -10% 0px', threshold: 0.1 },
        );

        observer.observe(element);

        return () => observer.disconnect();
    }, []);

    return (
        <Tag
            ref={ref}
            className={cn('reveal', visible && 'is-visible', className)}
            style={delay ? { transitionDelay: `${delay}ms` } : undefined}
        >
            {children}
        </Tag>
    );
}
