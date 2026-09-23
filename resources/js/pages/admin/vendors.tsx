import { Head, Link } from '@inertiajs/react';
import { Store } from 'lucide-react';
import {
    StoreStatusBadge,
    StoreSuspendAction,
} from '@/components/admin/store-suspension';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { SimplePagination } from '@/components/simple-pagination';
import type { Paginated } from '@/components/simple-pagination';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import admin from '@/routes/admin';

type Vendor = {
    id: number;
    name: string;
    slug: string;
    owner: string | null;
    plan: string | null;
    published_count: number;
    suspended: boolean;
};

export default function Vendors({
    vendors,
    search,
}: {
    vendors: Paginated<Vendor>;
    search: string;
}) {
    return (
        <>
            <Head title="Vendors" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Vendors"
                    description="Every store on Vendly. Suspending hides the store and stops its vendor from making changes."
                />
                {vendors.data.length === 0 ? (
                    search !== '' ? (
                        <EmptyState
                            icon={Store}
                            title={`No vendors match "${search}"`}
                            description="Search by store name, link, or the owner's email."
                            action={
                                <Button asChild variant="outline">
                                    <Link href={admin.vendors()}>
                                        Clear search
                                    </Link>
                                </Button>
                            }
                        />
                    ) : (
                        <EmptyState
                            icon={Store}
                            title="No vendors yet"
                            description="Stores appear here as soon as someone taps Start selling."
                        />
                    )
                ) : (
                    <>
                        <Card className="hidden gap-0 overflow-hidden p-0 md:flex">
                            <Table>
                                <TableHeader>
                                    <TableRow className="hover:bg-transparent">
                                        <TableHead className="pl-5">
                                            Store
                                        </TableHead>
                                        <TableHead>Owner</TableHead>
                                        <TableHead>Plan</TableHead>
                                        <TableHead>Published</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="pr-5">
                                            <span className="sr-only">
                                                Actions
                                            </span>
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {vendors.data.map((vendor) => (
                                        <TableRow key={vendor.id}>
                                            <TableCell className="pl-5">
                                                <Link
                                                    href={admin.vendors.show(
                                                        vendor.id,
                                                    )}
                                                    className="font-medium hover:text-primary hover:underline"
                                                >
                                                    {vendor.name}
                                                </Link>
                                                <p className="text-muted-foreground">
                                                    /s/{vendor.slug}
                                                </p>
                                            </TableCell>
                                            <TableCell>
                                                {vendor.owner}
                                            </TableCell>
                                            <TableCell>{vendor.plan}</TableCell>
                                            <TableCell className="tabular-nums">
                                                {vendor.published_count}
                                            </TableCell>
                                            <TableCell>
                                                <StoreStatusBadge
                                                    suspended={vendor.suspended}
                                                />
                                            </TableCell>
                                            <TableCell className="pr-5 text-right">
                                                <StoreSuspendAction
                                                    store={vendor}
                                                />
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </Card>
                        <div className="flex flex-col gap-3 md:hidden">
                            {vendors.data.map((vendor) => (
                                <Card key={vendor.id} className="gap-3 p-4">
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="min-w-0">
                                            <Link
                                                href={admin.vendors.show(
                                                    vendor.id,
                                                )}
                                                className="font-medium hover:text-primary hover:underline"
                                            >
                                                {vendor.name}
                                            </Link>
                                            <p className="text-sm text-muted-foreground">
                                                {vendor.owner}, {vendor.plan},{' '}
                                                {vendor.published_count}{' '}
                                                published
                                            </p>
                                        </div>
                                        <StoreStatusBadge
                                            suspended={vendor.suspended}
                                        />
                                    </div>
                                    <StoreSuspendAction store={vendor} />
                                </Card>
                            ))}
                        </div>
                        <SimplePagination page={vendors} />
                    </>
                )}
            </div>
        </>
    );
}

Vendors.layout = {
    breadcrumbs: [{ title: 'Vendors', href: admin.vendors() }],
};
