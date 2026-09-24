import { ChevronDown } from 'lucide-react';
import { useRef } from 'react';
import type { MouseEvent, ReactNode } from 'react';
import { cn } from '@/lib/utils';

/**
 * A question that opens to show its answer. It is a native details
 * element, so it works without JavaScript and search engines read every
 * answer; with JavaScript the answer slides open and closed instead of
 * jumping, and reduced motion keeps it instant.
 */
export function Disclosure({
    summary,
    children,
    className,
    summaryClassName,
}: {
    summary: ReactNode;
    children: ReactNode;
    className?: string;
    summaryClassName?: string;
}) {
    const details = useRef<HTMLDetailsElement>(null);
    const content = useRef<HTMLDivElement>(null);
    const running = useRef<Animation | null>(null);

    function toggle(event: MouseEvent<HTMLElement>) {
        const element = details.current;
        const panel = content.current;

        if (
            !element ||
            !panel ||
            typeof panel.animate !== 'function' ||
            window.matchMedia('(prefers-reduced-motion: reduce)').matches
        ) {
            return;
        }

        event.preventDefault();
        running.current?.cancel();

        if (!element.open) {
            element.open = true;
            const height = panel.scrollHeight;
            running.current = panel.animate(
                [
                    { height: '0px', opacity: 0 },
                    { height: `${height}px`, opacity: 1 },
                ],
                { duration: 280, easing: 'cubic-bezier(0.2, 0.8, 0.2, 1)' },
            );

            return;
        }

        const height = panel.offsetHeight;
        element.dataset.closing = 'true';
        const animation = panel.animate(
            [
                { height: `${height}px`, opacity: 1 },
                { height: '0px', opacity: 0 },
            ],
            { duration: 220, easing: 'cubic-bezier(0.4, 0, 1, 1)' },
        );
        running.current = animation;
        animation.onfinish = () => {
            element.open = false;
            delete element.dataset.closing;
        };
        animation.oncancel = () => {
            delete element.dataset.closing;
        };
    }

    return (
        <details
            ref={details}
            className={cn(
                'group [&_summary::-webkit-details-marker]:hidden',
                className,
            )}
        >
            <summary
                onClick={toggle}
                className={cn(
                    'flex min-h-11 cursor-pointer list-none items-center justify-between gap-4 rounded-md outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50',
                    summaryClassName,
                )}
            >
                {summary}
                <ChevronDown
                    className="size-5 shrink-0 text-muted-foreground transition-transform duration-300 ease-out group-open:rotate-180 group-data-[closing]:rotate-0 motion-reduce:transition-none"
                    aria-hidden="true"
                />
            </summary>
            <div ref={content} className="overflow-hidden">
                {children}
            </div>
        </details>
    );
}
