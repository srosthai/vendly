import { Disclosure } from '@/components/disclosure';
import { Reveal } from '@/components/marketing/reveal';

export type FaqItem = { question: string; answer: string };

/**
 * Questions that slide open to their answers. Each is a native details
 * element underneath, so the answers are in the page for search engines.
 */
export function Faq({ items }: { items: FaqItem[] }) {
    return (
        <div className="divide-y rounded-2xl border bg-card">
            {items.map((item, index) => (
                <Reveal key={item.question} delay={Math.min(index, 4) * 60}>
                    <Disclosure
                        summary={item.question}
                        className="px-5 py-2"
                        summaryClassName="py-2 font-semibold"
                    >
                        <p className="max-w-prose pt-1 pb-3 text-muted-foreground">
                            {item.answer}
                        </p>
                    </Disclosure>
                </Reveal>
            ))}
        </div>
    );
}
