import { ChevronDown } from 'lucide-react';
import { Reveal } from '@/components/marketing/reveal';

export type FaqItem = { question: string; answer: string };

/**
 * Plain disclosure elements: they open without JavaScript and search
 * engines read every answer.
 */
export function Faq({ items }: { items: FaqItem[] }) {
    return (
        <div className="divide-y rounded-2xl border bg-card">
            {items.map((item, index) => (
                <Reveal key={item.question} delay={Math.min(index, 4) * 60}>
                    <details className="group px-5 py-2 [&_summary::-webkit-details-marker]:hidden">
                        <summary className="flex min-h-11 cursor-pointer list-none items-center justify-between gap-4 rounded-md py-2 font-semibold outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50">
                            {item.question}
                            <ChevronDown
                                className="size-5 shrink-0 text-muted-foreground transition-transform duration-300 group-open:rotate-180"
                                aria-hidden="true"
                            />
                        </summary>
                        <p className="mt-1 mb-2 max-w-prose text-muted-foreground">
                            {item.answer}
                        </p>
                    </details>
                </Reveal>
            ))}
        </div>
    );
}
