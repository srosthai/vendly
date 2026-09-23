import { ArrowUp } from 'lucide-react';
import { useEffect, useState } from 'react';
import { cn } from '@/lib/utils';

/**
 * A round button in the corner that appears once the visitor has scrolled
 * past the first screen, and brings them back to the top.
 */
export function BackToTop() {
    const [visible, setVisible] = useState(false);

    useEffect(() => {
        const update = () => setVisible(window.scrollY > window.innerHeight);

        update();
        window.addEventListener('scroll', update, { passive: true });

        return () => window.removeEventListener('scroll', update);
    }, []);

    return (
        <button
            type="button"
            onClick={() => {
                const reduced = window.matchMedia(
                    '(prefers-reduced-motion: reduce)',
                ).matches;
                window.scrollTo({
                    top: 0,
                    behavior: reduced ? 'auto' : 'smooth',
                });
            }}
            aria-label="Back to top"
            title="Back to top"
            tabIndex={visible ? 0 : -1}
            aria-hidden={visible ? undefined : true}
            className={cn(
                'fixed right-4 bottom-[max(1rem,env(safe-area-inset-bottom))] z-40 flex size-12 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-lg shadow-primary/25 transition-[opacity,translate,background-color] duration-300 ease-out outline-none hover:bg-primary/90 focus-visible:ring-[3px] focus-visible:ring-ring/50 motion-reduce:transition-none md:right-6 md:bottom-6',
                visible
                    ? 'translate-y-0 opacity-100'
                    : 'pointer-events-none translate-y-3 opacity-0',
            )}
        >
            <ArrowUp className="size-5" aria-hidden="true" />
        </button>
    );
}
