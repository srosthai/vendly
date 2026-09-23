import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { Reveal } from '@/components/marketing/reveal';
import { cn } from '@/lib/utils';

export type ShowcaseItem = { icon: LucideIcon; title: string; body: string };

/**
 * One feature group: a heading and a short list on one side, and on the
 * other a large panel showing the real screen the list talks about. Groups
 * alternate sides so the page reads as a sequence, not a grid of boxes.
 */
export function FeatureShowcase({
    title,
    description,
    items,
    visual,
    flip = false,
}: {
    title: string;
    description: string;
    items: ShowcaseItem[];
    visual: ReactNode;
    flip?: boolean;
}) {
    return (
        <div className="grid items-center gap-10 lg:grid-cols-2 lg:gap-16">
            <Reveal className={cn(flip && 'lg:order-2')}>
                <h2 className="text-3xl font-bold tracking-[-0.03em] sm:text-4xl">
                    {title}
                </h2>
                <p className="mt-3 max-w-md text-lg text-muted-foreground">
                    {description}
                </p>
                <ul className="-mx-4 mt-8 flex flex-col gap-1">
                    {items.map((item) => (
                        <li
                            key={item.title}
                            className="group flex gap-4 rounded-2xl border border-transparent p-4 transition-[background-color,border-color] duration-200 ease-out hover:border-border hover:bg-card motion-reduce:transition-none"
                        >
                            <span className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary transition-[background-color,color,scale] duration-200 ease-out group-hover:scale-105 group-hover:bg-primary group-hover:text-primary-foreground motion-reduce:transition-none motion-reduce:group-hover:scale-100">
                                <item.icon
                                    className="size-5"
                                    aria-hidden="true"
                                />
                            </span>
                            <div className="pt-0.5">
                                <h3 className="font-semibold">{item.title}</h3>
                                <p className="mt-0.5 text-muted-foreground">
                                    {item.body}
                                </p>
                            </div>
                        </li>
                    ))}
                </ul>
            </Reveal>
            <Reveal delay={120} className={cn(flip && 'lg:order-1')}>
                <div className="relative overflow-hidden rounded-3xl border bg-card p-6 sm:p-10">
                    <div
                        aria-hidden="true"
                        className="pointer-events-none absolute -top-24 -right-24 size-72 rounded-full bg-primary/10 blur-3xl"
                    />
                    <div
                        aria-hidden="true"
                        className="pointer-events-none absolute -bottom-24 -left-16 size-64 rounded-full bg-highlight/10 blur-3xl"
                    />
                    <div className="relative mx-auto max-w-sm">{visual}</div>
                </div>
            </Reveal>
        </div>
    );
}
