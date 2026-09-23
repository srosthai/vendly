import { Form } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import TestimonialController from '@/actions/App/Http/Controllers/Admin/TestimonialController';
import {
    FormSheet,
    FormSheetBody,
    FormSheetFooter,
} from '@/components/form-sheet';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SheetClose } from '@/components/ui/sheet';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';

export type AdminTestimonial = {
    id: number;
    name: string;
    role: string | null;
    quote: string;
    sort: number;
    published: boolean;
};

/**
 * Add a quote, or edit one when a testimonial is passed.
 */
export function TestimonialSheet({
    testimonial = null,
}: {
    testimonial?: AdminTestimonial | null;
}) {
    const [open, setOpen] = useState(false);
    const [published, setPublished] = useState(testimonial?.published ?? false);
    const key = testimonial?.id ?? 'new';

    return (
        <FormSheet
            open={open}
            onOpenChange={setOpen}
            title={testimonial ? `Edit ${testimonial.name}` : 'New testimonial'}
            description="Only add words a real person said, with their permission."
            trigger={
                testimonial ? (
                    <Button variant="ghost" size="sm">
                        Edit
                    </Button>
                ) : (
                    <Button>
                        <Plus />
                        New testimonial
                    </Button>
                )
            }
        >
            <Form
                {...(testimonial
                    ? TestimonialController.update.form(testimonial.id)
                    : TestimonialController.store.form())}
                options={{ preserveScroll: true }}
                resetOnSuccess={testimonial === null}
                onSuccess={() => setOpen(false)}
                className="flex min-h-0 flex-1 flex-col"
            >
                {({ processing, errors }) => (
                    <>
                        <FormSheetBody>
                            <div className="grid gap-2">
                                <Label htmlFor={`name-${key}`}>Name</Label>
                                <Input
                                    id={`name-${key}`}
                                    name="name"
                                    required
                                    defaultValue={testimonial?.name}
                                />
                                <InputError message={errors.name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor={`role-${key}`}>
                                    Role or store
                                </Label>
                                <Input
                                    id={`role-${key}`}
                                    name="role"
                                    placeholder="Owner, Smile Tea"
                                    defaultValue={testimonial?.role ?? ''}
                                />
                                <InputError message={errors.role} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor={`quote-${key}`}>Quote</Label>
                                <Textarea
                                    id={`quote-${key}`}
                                    name="quote"
                                    rows={6}
                                    required
                                    defaultValue={testimonial?.quote}
                                />
                                <InputError message={errors.quote} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor={`sort-${key}`}>Order</Label>
                                <Input
                                    id={`sort-${key}`}
                                    name="sort"
                                    type="number"
                                    min={0}
                                    defaultValue={testimonial?.sort ?? 0}
                                />
                                <p className="text-sm text-muted-foreground">
                                    Lower numbers show first.
                                </p>
                                <InputError message={errors.sort} />
                            </div>
                            <input
                                type="hidden"
                                name="published"
                                value={published ? '1' : '0'}
                            />
                            <div className="flex items-center gap-3">
                                <Checkbox
                                    id={`published-${key}`}
                                    checked={published}
                                    onCheckedChange={(checked) =>
                                        setPublished(checked === true)
                                    }
                                />
                                <Label htmlFor={`published-${key}`}>
                                    Show on the website
                                </Label>
                            </div>
                        </FormSheetBody>
                        <FormSheetFooter>
                            <SheetClose asChild>
                                <Button type="button" variant="outline">
                                    Cancel
                                </Button>
                            </SheetClose>
                            <Button type="submit" disabled={processing}>
                                {processing && <Spinner />}
                                {testimonial
                                    ? 'Save testimonial'
                                    : 'Add testimonial'}
                            </Button>
                        </FormSheetFooter>
                    </>
                )}
            </Form>
        </FormSheet>
    );
}
