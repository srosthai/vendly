import { Form } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import { ListToolbar } from '@/components/admin/list-toolbar';
import { EmptyState } from '@/components/empty-state';
import {
    FormSheet,
    FormSheetBody,
    FormSheetFooter,
} from '@/components/form-sheet';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { SimplePagination } from '@/components/simple-pagination';
import type { Paginated } from '@/components/simple-pagination';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SheetClose } from '@/components/ui/sheet';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
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

type NamedFilters = { search: string; sort: string };

const defaults: NamedFilters = { search: '', sort: 'default' };

/**
 * The shared screen for a store's categories and brands: a table with how
 * many products use each, search and sort, and add, rename, and delete in
 * a sheet. Deleting leaves the products in place without that label.
 */
export function NamedList({
    title,
    noun,
    description,
    items,
    filters,
    url,
    defaultSortLabel,
    defaultSortIsName = false,
    routes,
    icon: Icon,
}: {
    icon: LucideIcon;
    title: string;
    noun: string;
    description: string;
    items: Paginated<NamedRecord & { products_count: number }>;
    filters: NamedFilters;
    url: string;
    defaultSortLabel: string;
    defaultSortIsName?: boolean;
    routes: Routes;
}) {
    const lowerNoun = noun.toLowerCase();
    const addSheet = <NamedRecordSheet noun={noun} action={routes.store} />;
    const searching = filters.search !== '';
    const empty = items.data.length === 0;

    return (
        <div className="flex max-w-4xl flex-col gap-6 p-4 md:p-6">
            <PageHeader
                title={title}
                description={description}
                actions={!empty || searching ? addSheet : undefined}
            />
            {!empty || searching ? (
                <ListToolbar
                    url={url}
                    values={filters}
                    defaults={defaults}
                    searchPlaceholder={`Search ${title.toLowerCase()} by name`}
                    sorts={[
                        { value: 'default', label: defaultSortLabel },
                        ...(defaultSortIsName
                            ? []
                            : [{ value: 'name', label: 'Name A to Z' }]),
                        { value: 'products', label: 'Most products' },
                        { value: 'newest', label: 'Newest first' },
                    ]}
                    total={items.total ?? items.data.length}
                    noun={[lowerNoun, title.toLowerCase()]}
                />
            ) : null}
            {empty && searching ? (
                <EmptyState
                    icon={Icon}
                    title={`No ${title.toLowerCase()} match “${filters.search}”`}
                    description="Check the spelling, or clear the search to see them all."
                />
            ) : empty ? (
                <EmptyState
                    icon={Icon}
                    title={`No ${title.toLowerCase()} yet`}
                    description={`Add your first ${lowerNoun} to group your products.`}
                    action={addSheet}
                />
            ) : (
                <>
                    <Card className="gap-0 overflow-hidden p-0">
                        <Table>
                            <TableHeader>
                                <TableRow className="hover:bg-transparent">
                                    <TableHead className="pl-5">
                                        {noun}
                                    </TableHead>
                                    <TableHead>Products</TableHead>
                                    <TableHead className="pr-5">
                                        <span className="sr-only">Actions</span>
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {items.data.map((item) => (
                                    <TableRow key={item.id}>
                                        <TableCell className="pl-5 font-medium">
                                            {item.name}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground tabular-nums">
                                            {item.products_count}
                                        </TableCell>
                                        <TableCell className="pr-5">
                                            <div className="flex justify-end gap-1">
                                                <NamedRecordSheet
                                                    record={item}
                                                    noun={noun}
                                                    action={routes.update(
                                                        item.id,
                                                    )}
                                                    variant="row"
                                                />
                                                <ConfirmDeleteDialog
                                                    name={item.name}
                                                    description={
                                                        item.products_count > 0
                                                            ? `${item.products_count} ${item.products_count === 1 ? 'product stays' : 'products stay'} in your store without a ${lowerNoun}.`
                                                            : `No products use this ${lowerNoun}.`
                                                    }
                                                    action={routes.destroy(
                                                        item.id,
                                                    )}
                                                />
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </Card>
                    <SimplePagination page={items} />
                </>
            )}
        </div>
    );
}
