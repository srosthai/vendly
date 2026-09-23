import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { Reveal } from '@/components/marketing/reveal';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { register } from '@/routes';

export function SectionHeading({
    title,
    description,
    align = 'left',
    as: Heading = 'h2',
}: {
    title: string;
    description?: ReactNode;
    align?: 'left' | 'center';
    as?: 'h1' | 'h2';
}) {
    return (
        <Reveal
            className={cn(
                'max-w-2xl',
                align === 'center' && 'mx-auto text-center',
            )}
        >
            <Heading
                className={cn(
                    'font-bold tracking-[-0.03em]',
                    Heading === 'h1'
                        ? 'text-4xl sm:text-5xl'
                        : 'text-3xl sm:text-4xl',
                )}
            >
                {title}
            </Heading>
            {description ? (
                <p className="mt-4 text-lg text-muted-foreground">
                    {description}
                </p>
            ) : null}
        </Reveal>
    );
}

/**
 * The closing band on every marketing page: one clear next step.
 */
export function CtaBand({
    title = 'Open your store today',
    description = 'The free plan needs no payment. Add products, share one link, and read requests in Telegram.',
}: {
    title?: string;
    description?: string;
}) {
    return (
        <section className="px-4 py-20 md:px-6">
            <Reveal className="mx-auto flex max-w-6xl flex-col items-start gap-6 rounded-3xl bg-[var(--brand-navy)] p-8 text-white sm:p-12 md:flex-row md:items-center md:justify-between dark:bg-secondary dark:text-secondary-foreground">
                <div className="max-w-xl">
                    <h2 className="text-3xl font-bold tracking-tight">
                        {title}
                    </h2>
                    <p className="mt-3 text-white/75 dark:text-muted-foreground">
                        {description}
                    </p>
                </div>
                <Button asChild size="lg" variant="highlight">
                    <Link href={register({ query: { next: 'sell' } })}>
                        Start selling for free
                    </Link>
                </Button>
            </Reveal>
        </section>
    );
}
