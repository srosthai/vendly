import { Quote } from 'lucide-react';

export type MarketingTestimonial = {
    id: number;
    name: string;
    role: string | null;
    quote: string;
};

export function TestimonialCard({
    testimonial,
}: {
    testimonial: MarketingTestimonial;
}) {
    return (
        <figure className="flex h-full flex-col gap-5 rounded-2xl border bg-background p-6">
            <Quote className="size-6 text-primary" aria-hidden="true" />
            <blockquote className="flex-1 leading-relaxed">
                {testimonial.quote}
            </blockquote>
            <figcaption className="flex items-center gap-3">
                <span className="flex size-10 items-center justify-center rounded-full bg-secondary font-bold text-secondary-foreground">
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
