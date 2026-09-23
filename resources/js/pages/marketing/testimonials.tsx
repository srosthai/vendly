import { MarketingHead } from '@/components/marketing/marketing-head';
import type { PageMeta } from '@/components/marketing/marketing-head';
import { Reveal } from '@/components/marketing/reveal';
import { CtaBand, SectionHeading } from '@/components/marketing/section';
import { TestimonialCard } from '@/components/marketing/testimonial-card';
import type { MarketingTestimonial } from '@/components/marketing/testimonial-card';

export default function Testimonials({
    meta,
    testimonials,
}: {
    meta: PageMeta;
    testimonials: MarketingTestimonial[];
}) {
    return (
        <>
            <MarketingHead meta={meta} />
            <section className="px-4 pt-16 pb-16 md:px-6 lg:pt-24">
                <div className="mx-auto max-w-6xl">
                    <SectionHeading
                        as="h1"
                        title="What people say about Vendly"
                        description="In their own words, from sellers and customers who use Vendly."
                    />
                    <div className="mt-12 columns-1 gap-4 sm:columns-2 lg:columns-3">
                        {testimonials.map((testimonial, index) => (
                            <Reveal
                                key={testimonial.id}
                                delay={(index % 3) * 90}
                                className="mb-4 break-inside-avoid"
                            >
                                <TestimonialCard testimonial={testimonial} />
                            </Reveal>
                        ))}
                    </div>
                </div>
            </section>
            <CtaBand />
        </>
    );
}
