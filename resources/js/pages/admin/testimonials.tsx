import { Head } from '@inertiajs/react';
import { Quote } from 'lucide-react';
import TestimonialController from '@/actions/App/Http/Controllers/Admin/TestimonialController';
import { TestimonialSheet } from '@/components/admin/testimonial-sheet';
import type { AdminTestimonial } from '@/components/admin/testimonial-sheet';
import { ListToolbar } from '@/components/admin/list-toolbar';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { SimplePagination } from '@/components/simple-pagination';
import type { Paginated } from '@/components/simple-pagination';
import { Badge } from '@/components/ui/badge';
import { Card } from '@/components/ui/card';
import { ConfirmDeleteDialog } from '@/components/vendor/confirm-delete-dialog';
import admin from '@/routes/admin';

type Filters = { search: string; status: string };

const defaults: Filters = { search: '', status: 'all' };

export default function Testimonials({
    testimonials,
    filters,
}: {
    testimonials: Paginated<AdminTestimonial>;
    filters: Filters;
}) {
    const filtered =
        filters.search !== defaults.search ||
        filters.status !== defaults.status;

    return (
        <>
            <Head title="Testimonials" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Testimonials"
                    description="Real quotes from sellers and customers. Published ones appear on the website, and the Testimonials page only exists once one is published."
                    actions={<TestimonialSheet />}
                />
                <ListToolbar
                    url={admin.testimonials.url()}
                    values={filters}
                    defaults={defaults}
                    searchPlaceholder="Search name, role, or quote"
                    filters={[
                        {
                            key: 'status',
                            label: 'Status',
                            options: [
                                { value: 'all', label: 'Shown or hidden' },
                                {
                                    value: 'published',
                                    label: 'On the website',
                                },
                                { value: 'draft', label: 'Hidden' },
                            ],
                        },
                    ]}
                    total={testimonials.total ?? testimonials.data.length}
                    noun={['testimonial', 'testimonials']}
                />
                {testimonials.data.length === 0 && filtered ? (
                    <EmptyState
                        icon={Quote}
                        title="No testimonials match these filters"
                        description="Try another search or filter, or clear them to see every quote."
                    />
                ) : testimonials.data.length === 0 ? (
                    <EmptyState
                        icon={Quote}
                        title="No testimonials yet"
                        description="Ask a seller or customer if you may quote them, then add their words here."
                        action={<TestimonialSheet />}
                    />
                ) : (
                    <>
                        <div className="grid gap-4 md:grid-cols-2">
                            {testimonials.data.map((testimonial) => (
                                <Card
                                    key={testimonial.id}
                                    className="gap-4 p-5"
                                >
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
                        <SimplePagination page={testimonials} />
                    </>
                )}
            </div>
        </>
    );
}

Testimonials.layout = {
    breadcrumbs: [{ title: 'Testimonials', href: admin.testimonials() }],
};
