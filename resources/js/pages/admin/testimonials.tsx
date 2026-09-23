import { Head } from '@inertiajs/react';
import { Quote } from 'lucide-react';
import TestimonialController from '@/actions/App/Http/Controllers/Admin/TestimonialController';
import { TestimonialSheet } from '@/components/admin/testimonial-sheet';
import type { AdminTestimonial } from '@/components/admin/testimonial-sheet';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Card } from '@/components/ui/card';
import { ConfirmDeleteDialog } from '@/components/vendor/confirm-delete-dialog';
import admin from '@/routes/admin';

export default function Testimonials({
    testimonials,
}: {
    testimonials: AdminTestimonial[];
}) {
    return (
        <>
            <Head title="Testimonials" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Testimonials"
                    description="Real quotes from sellers and customers. Published ones appear on the website, and the Testimonials page only exists once one is published."
                    actions={<TestimonialSheet />}
                />
                {testimonials.length === 0 ? (
                    <EmptyState
                        icon={Quote}
                        title="No testimonials yet"
                        description="Ask a seller or customer if you may quote them, then add their words here."
                        action={<TestimonialSheet />}
                    />
                ) : (
                    <div className="grid gap-4 md:grid-cols-2">
                        {testimonials.map((testimonial) => (
                            <Card key={testimonial.id} className="gap-4 p-5">
                                <div className="flex items-start justify-between gap-3">
                                    <div className="min-w-0">
                                        <p className="font-semibold">
                                            {testimonial.name}
                                        </p>
                                        {testimonial.role ? (
                                            <p className="text-sm text-muted-foreground">
                                                {testimonial.role}
                                            </p>
                                        ) : null}
                                    </div>
                                    {testimonial.published ? (
                                        <Badge variant="success">
                                            On the website
                                        </Badge>
                                    ) : (
                                        <Badge variant="secondary">
                                            Hidden
                                        </Badge>
                                    )}
                                </div>
                                <p className="line-clamp-4 text-muted-foreground">
                                    “{testimonial.quote}”
                                </p>
                                <div className="flex justify-end gap-1">
                                    <TestimonialSheet
                                        testimonial={testimonial}
                                    />
                                    <ConfirmDeleteDialog
                                        name={`the quote from ${testimonial.name}`}
                                        description="It is removed from the website at once."
                                        action={TestimonialController.destroy.form(
                                            testimonial.id,
                                        )}
                                    />
                                </div>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

Testimonials.layout = {
    breadcrumbs: [{ title: 'Testimonials', href: admin.testimonials() }],
};
