import { Quote } from 'lucide-react';

export type MarketingTestimonial = {
    id: number;
    name: string;
    role: string | null;
    quote: string;
};

/**
 * One quote with who said it. Pointing at the card lifts it and tints the
 * quote mark and initial.
 */
export function TestimonialCard({
    testimonial,
}: {
    testimonial: MarketingTestimonial;
}) {
    return (
        <figure className="group flex h-full flex-col gap-5 rounded-2xl border bg-background p-6 transition-[translate,box-shadow,border-color] duration-200 ease-out hover:-translate-y-1 hover:border-primary/40 hover:shadow-lg hover:shadow-primary/10 motion-reduce:transition-none motion-reduce:hover:translate-y-0">
            <Quote
                className="size-6 origin-left text-primary transition-[scale,color] duration-200 ease-out group-hover:scale-125 group-hover:text-highlight motion-reduce:transition-none motion-reduce:group-hover:scale-100"
                aria-hidden="true"
            />
            <blockquote className="flex-1 leading-relaxed">
                {testimonial.quote}
            </blockquote>
            <figcaption className="flex items-center gap-3">
                <span className="flex size-10 items-center justify-center rounded-full bg-secondary font-bold text-secondary-foreground transition-colors duration-200 group-hover:bg-primary group-hover:text-primary-foreground">
                    {testimonial.name.slice(0, 1).toUpperCase()}
                </span>
                <span>
                    <span className="block font-semibold">
                        {testimonial.name}
                    </span>
                    {testimonial.role ? (
                        <span className="block text-sm text-muted-foreground">
                            {testimonial.role}
                        </span>
                    ) : null}
                </span>
            </figcaption>
        </figure>
    );
}
