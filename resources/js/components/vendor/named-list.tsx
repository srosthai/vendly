import { Form } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import { EmptyState } from '@/components/empty-state';
import {
    FormSheet,
    FormSheetBody,
    FormSheetFooter,
} from '@/components/form-sheet';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SheetClose } from '@/components/ui/sheet';
import { Spinner } from '@/components/ui/spinner';
import { ConfirmDeleteDialog } from '@/components/vendor/confirm-delete-dialog';
import type { RouteFormDefinition } from '@/wayfinder';

export type NamedRecord = { id: number; name: string };

type Routes = {
    store: RouteFormDefinition<'post'>;
    update: (id: number) => RouteFormDefinition<'post'>;
    destroy: (id: number) => RouteFormDefinition<'post'>;
};

/**
 * Add a category or brand, or rename one when a record is passed.
 */
function NamedRecordSheet({
    record = null,
    noun,
    action,
    variant = 'default',
}: {
    record?: NamedRecord | null;
    noun: string;
    action: RouteFormDefinition<'post'>;
    variant?: 'default' | 'row';
}) {
    const [open, setOpen] = useState(false);
    const lowerNoun = noun.toLowerCase();
    const id = `name-${record?.id ?? 'new'}`;

    return (
        <FormSheet
            open={open}
            onOpenChange={setOpen}
            title={record ? `Rename ${record.name}` : `New ${lowerNoun}`}
            description={
                record
                    ? 'The link customers use to filter by it stays the same.'
                    : undefined
            }
            trigger={
                variant === 'row' ? (
                    <Button variant="ghost" size="sm">
                        Rename
                    </Button>
                ) : (
                    <Button>
                        <Plus />
                        New {lowerNoun}
                    </Button>
                )
            }
        >
            <Form
                {...action}
                options={{ preserveScroll: true }}
                resetOnSuccess={record === null}
                onSuccess={() => setOpen(false)}
                className="flex min-h-0 flex-1 flex-col"
            >
                {({ processing, errors }) => (
                    <>
                        <FormSheetBody>
                            <div className="grid gap-2">
                                <Label htmlFor={id}>{noun} name</Label>
                                <Input
                                    id={id}
                                    name="name"
                                    required
                                    autoFocus
                                    defaultValue={record?.name}
                                />
                                <InputError message={errors.name} />
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
                                {record ? 'Save name' : `Add ${lowerNoun}`}
                            </Button>
                        </FormSheetFooter>
                    </>
                )}
            </Form>
        </FormSheet>
    );
}

/**
 * The shared screen for a store's categories and brands: add, rename, and
 * delete, where deleting leaves the products in place without that label.
 */
export function NamedList({
    title,
    noun,
    description,
    items,
    routes,
    icon: Icon,
}: {
    icon: LucideIcon;
    title: string;
    noun: string;
    description: string;
    items: NamedRecord[];
    routes: Routes;
}) {
    const lowerNoun = noun.toLowerCase();
    const addSheet = <NamedRecordSheet noun={noun} action={routes.store} />;

    return (
        <div className="flex max-w-3xl flex-col gap-6 p-4 md:p-6">
            <PageHeader
                title={title}
                description={description}
                actions={items.length > 0 ? addSheet : undefined}
            />
            {items.length === 0 ? (
                <EmptyState
                    icon={Icon}
                    title={`No ${title.toLowerCase()} yet`}
                    description={`Add your first ${lowerNoun} to group your products.`}
                    action={addSheet}
                />
            ) : (
                <Card className="gap-0 divide-y p-0">
                    {items.map((item) => (
                        <div
                            key={item.id}
                            className="flex items-center justify-between gap-3 px-4 py-2"
                        >
                            <span className="truncate font-medium">
                                {item.name}
                            </span>
                            <div className="flex shrink-0 gap-1">
                                <NamedRecordSheet
                                    record={item}
                                    noun={noun}
                                    action={routes.update(item.id)}
                                    variant="row"
                                />
                                <ConfirmDeleteDialog
                                    name={item.name}
                                    description={`Products in ${item.name} stay in your store without a ${lowerNoun}.`}
                                    action={routes.destroy(item.id)}
                                />
                            </div>
                        </div>
                    ))}
                </Card>
            )}
        </div>
    );
}
