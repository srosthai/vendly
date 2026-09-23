import { Form } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { ConfirmDeleteDialog } from '@/components/vendor/confirm-delete-dialog';
import type { RouteFormDefinition } from '@/wayfinder';

export type NamedRecord = { id: number; name: string; products_count?: number };

type Routes = {
    store: RouteFormDefinition<'post'>;
    update: (id: number) => RouteFormDefinition<'post'>;
    destroy: (id: number) => RouteFormDefinition<'post'>;
};

function RenameDialog({
    record,
    noun,
    action,
}: {
    record: NamedRecord;
    noun: string;
    action: RouteFormDefinition<'post'>;
}) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="ghost" size="sm">
                    Rename
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Rename {record.name}</DialogTitle>
                </DialogHeader>
                <Form
                    {...action}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    className="grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor={`name-${record.id}`}>
                                    {noun} name
                                </Label>
                                <Input
                                    id={`name-${record.id}`}
                                    name="name"
                                    required
                                    defaultValue={record.name}
                                />
                                <InputError message={errors.name} />
                            </div>
                            <DialogFooter>
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    Save name
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
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
}: {
    title: string;
    noun: string;
    description: string;
    items: NamedRecord[];
    routes: Routes;
}) {
    const lowerNoun = noun.toLowerCase();

    return (
        <div className="flex max-w-2xl flex-col gap-6 p-4 md:p-6">
            <div>
                <h1 className="text-2xl font-semibold tracking-tight">
                    {title}
                </h1>
                <p className="mt-1 text-sm text-muted-foreground">
                    {description}
                </p>
            </div>
            <Form
                {...routes.store}
                options={{ preserveScroll: true }}
                resetOnSuccess
                className="flex flex-col gap-2 sm:flex-row sm:items-start"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid flex-1 gap-2">
                            <Label htmlFor="new-name" className="sr-only">
                                New {lowerNoun} name
                            </Label>
                            <Input
                                id="new-name"
                                name="name"
                                required
                                placeholder={`New ${lowerNoun} name`}
                            />
                            <InputError message={errors.name} />
                        </div>
                        <Button type="submit" disabled={processing}>
                            {processing && <Spinner />}
                            Add {lowerNoun}
                        </Button>
                    </>
                )}
            </Form>
            {items.length === 0 ? (
                <p className="text-sm text-muted-foreground">
                    No {title.toLowerCase()} yet. Add the first one above.
                </p>
            ) : (
                <Card className="gap-0 divide-y p-0">
                    {items.map((item) => (
                        <div
                            key={item.id}
                            className="flex items-center justify-between gap-3 px-4 py-2"
                        >
                            <span className="truncate">{item.name}</span>
                            <div className="flex shrink-0 gap-1">
                                <RenameDialog
                                    record={item}
                                    noun={noun}
                                    action={routes.update(item.id)}
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
