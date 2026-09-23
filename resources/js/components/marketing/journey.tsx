import { Reveal } from '@/components/marketing/reveal';
import { cn } from '@/lib/utils';

export type JourneyStep = { title: string; body: string; detail?: string };

/**
 * Steps as one connected path: numbered stops on a line, each with what
 * happens and, where it helps, the thing the person will see. Vertical on
 * every screen, so the order is never in doubt.
 */
export function Journey({
    steps,
    tone = 'primary',
    className,
}: {
    steps: JourneyStep[];
    tone?: 'primary' | 'highlight';
    className?: string;
}) {
    return (
        <ol className={cn('relative', className)}>
            <span
                aria-hidden="true"
                className={cn(
                    'absolute top-5 bottom-5 left-5 w-px',
                    tone === 'primary'
                        ? 'bg-gradient-to-b from-primary via-primary/40 to-transparent'
                        : 'bg-gradient-to-b from-highlight via-highlight/40 to-transparent',
                )}
            />
            {steps.map((step, index) => (
                <Reveal
                    as="li"
                    key={step.title}
                    delay={index * 90}
                    className="relative flex gap-5 pb-10 last:pb-0"
                >
                    <span
                        className={cn(
                            'relative z-10 flex size-10 shrink-0 items-center justify-center rounded-full text-sm font-bold ring-8 ring-background',
                            tone === 'primary'
                                ? 'bg-primary text-primary-foreground'
                                : 'bg-highlight text-highlight-foreground',
                        )}
                        aria-hidden="true"
                    >
                        {index + 1}
                    </span>
                    <div className="pt-1.5">
                        <h3 className="text-lg font-semibold">
                            <span className="sr-only">Step {index + 1}: </span>
                            {step.title}
                        </h3>
                        <p className="mt-1 max-w-prose text-muted-foreground">
                            {step.body}
                        </p>
                        {step.detail ? (
                            <p className="mt-3 inline-flex rounded-full border bg-card px-3 py-1 text-sm text-muted-foreground">
                                {step.detail}
                            </p>
                        ) : null}
                    </div>
                </Reveal>
            ))}
        </ol>
    );
}
