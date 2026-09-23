import { Reveal } from '@/components/marketing/reveal';
import { cn } from '@/lib/utils';

export type JourneyStep = { title: string; body: string; detail?: string };

/**
 * Steps as one connected path: numbered stops on a line, each with what
 * happens and, where it helps, the thing the person will see. Vertical on
 * every screen, so the order is never in doubt. Pointing at a step lifts
 * its stop and tints its title and detail.
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
                    className="group relative flex gap-5 pb-10 last:pb-0"
                >
                    <span
                        className={cn(
                            'relative z-10 flex size-10 shrink-0 items-center justify-center rounded-full text-sm font-bold ring-8 ring-background transition-[scale,box-shadow] duration-200 ease-out group-hover:scale-110 motion-reduce:transition-none motion-reduce:group-hover:scale-100',
                            tone === 'primary'
                                ? 'bg-primary text-primary-foreground group-hover:shadow-[0_0_0_4px_color-mix(in_oklab,var(--primary)_25%,transparent)]'
                                : 'bg-highlight text-highlight-foreground group-hover:shadow-[0_0_0_4px_color-mix(in_oklab,var(--highlight)_30%,transparent)]',
                        )}
                        aria-hidden="true"
                    >
                        {index + 1}
                    </span>
                    <div className="pt-1.5">
                        <h3
                            className={cn(
                                'text-lg font-semibold transition-colors duration-200',
                                tone === 'primary'
                                    ? 'group-hover:text-primary'
                                    : 'group-hover:text-highlight-foreground dark:group-hover:text-highlight',
                            )}
                        >
                            <span className="sr-only">Step {index + 1}: </span>
                            {step.title}
                        </h3>
                        <p className="mt-1 max-w-prose text-muted-foreground">
                            {step.body}
                        </p>
                        {step.detail ? (
                            <p
                                className={cn(
                                    'mt-3 inline-flex rounded-full border bg-card px-3 py-1 text-sm text-muted-foreground transition-colors duration-200',
                                    tone === 'primary'
                                        ? 'group-hover:border-primary/40 group-hover:text-primary'
                                        : 'group-hover:border-highlight/60 group-hover:text-foreground',
                                )}
                            >
                                {step.detail}
                            </p>
                        ) : null}
                    </div>
                </Reveal>
            ))}
        </ol>
    );
}
