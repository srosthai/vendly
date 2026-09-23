import { Form, Head } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { SimplePagination } from '@/components/simple-pagination';
import type { Paginated } from '@/components/simple-pagination';
import { Card } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

type Vendor = {
    id: number;
    name: string;
    slug: string;
    owner: string | null;
    plan: string | null;
    published_count: number;
    suspended: boolean;
};

export default function Vendors({ vendors }: { vendors: Paginated<Vendor> }) {
    return (
        <>
            <Head title="Vendors" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <h1 className="text-2xl font-semibold tracking-tight">
                    Vendors
                </h1>
                {vendors.data.length === 0 ? (
                    <p className="text-muted-foreground">No stores yet.</p>
                ) : (
                    <>
                        <div className="hidden md:block">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Store</TableHead>
                                        <TableHead>Owner</TableHead>
                                        <TableHead>Plan</TableHead>
                                        <TableHead>Published</TableHead>
                                        <TableHead />
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {vendors.data.map((vendor) => (
                                        <VendorRow
                                            key={vendor.id}
                                            vendor={vendor}
                                        />
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                        <div className="flex flex-col gap-3 md:hidden">
                            {vendors.data.map((vendor) => (
                                <Card key={vendor.id} className="gap-3 p-4">
                                    <VendorDetails vendor={vendor} />
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

function VendorRow({ vendor }: { vendor: Vendor }) {
    return (
        <TableRow>
            <TableCell>
                <div className="font-medium">{vendor.name}</div>
                <div className="text-muted-foreground">{vendor.slug}</div>
            </TableCell>
            <TableCell>{vendor.owner}</TableCell>
            <TableCell>{vendor.plan}</TableCell>
            <TableCell>{vendor.published_count}</TableCell>
            <TableCell className="text-right">
                <Suspend vendor={vendor} />
            </TableCell>
        </TableRow>
    );
}

function VendorDetails({ vendor }: { vendor: Vendor }) {
    return (
        <div className="flex flex-col gap-3">
            <div>
                <div className="font-medium">{vendor.name}</div>
                <p className="text-sm text-muted-foreground">
                    {vendor.owner} · {vendor.plan} · {vendor.published_count}{' '}
                    published
                </p>
            </div>
            <Suspend vendor={vendor} />
        </div>
    );
}

function Suspend({ vendor }: { vendor: Vendor }) {
    return (
        <Form
            action={
                vendor.suspended
                    ? `/admin/stores/${vendor.id}/suspend`
                    : `/admin/stores/${vendor.id}/suspend`
            }
            method={vendor.suspended ? 'delete' : 'post'}
        >
            {({ processing }) => (
                <Button
                    type="submit"
                    variant={vendor.suspended ? 'outline' : 'destructive'}
                    size="sm"
                    disabled={processing}
                >
                    {vendor.suspended ? 'Restore' : 'Suspend'}
                </Button>
            )}
        </Form>
    );
}
