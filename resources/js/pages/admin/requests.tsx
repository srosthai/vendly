import { Form, Head, Link } from '@inertiajs/react';
import InquiryController from '@/actions/App/Http/Controllers/Admin/InquiryController';
import { SimplePagination } from '@/components/simple-pagination';
import type { Paginated } from '@/components/simple-pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

type Delivery = { delivered: boolean; error: string | null };

type Request = {
    id: number;
    reference: string;
    store: string;
    customer: string;
    contact: string | null;
    sent_at: string | null;
    lines: string[];
    total: string;
    admin: Delivery;
    vendor: Delivery & { connected: boolean };
    can_retry: boolean;
};

function DeliveryBadge({
    label,
    delivery,
    connected = true,
}: {
    label: string;
    delivery: Delivery;
    connected?: boolean;
}) {
    if (!connected) {
        return (
            <p className="text-sm text-muted-foreground">
                {label}: not connected
            </p>
        );
    }

    return (
        <div className="flex flex-col gap-1">
            <div className="flex items-center gap-2 text-sm">
                <span>{label}</span>
                <Badge
                    variant={delivery.delivered ? 'secondary' : 'destructive'}
                >
                    {delivery.delivered ? 'Delivered' : 'Not delivered'}
                </Badge>
            </div>
            {delivery.error ? (
                <p className="text-xs text-muted-foreground">
                    {delivery.error}
                </p>
            ) : null}
        </div>
    );
}

function RetryButton({ request }: { request: Request }) {
    if (!request.can_retry) {
        return null;
    }

    return (
        <Form
            {...InquiryController.retry.form(request.id)}
            options={{ preserveScroll: true }}
        >
            {({ processing }) => (
                <Button
                    type="submit"
                    size="sm"
                    variant="outline"
                    disabled={processing}
                >
                    {processing && <Spinner />}
                    Send again
                </Button>
            )}
        </Form>
    );
}

function sentAt(iso: string | null): string {
    return iso === null
        ? ''
        : new Date(iso).toLocaleString(undefined, {
              dateStyle: 'medium',
              timeStyle: 'short',
          });
}

export default function Requests({
    inquiries,
    filter,
}: {
    inquiries: Paginated<Request>;
    filter: 'all' | 'undelivered';
}) {
    return (
        <>
            <Head title="Requests" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Requests
                        </h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Buy and cart requests sent to Telegram. Send again
                            once a chat problem is fixed.
                        </p>
                    </div>
                    <div className="flex gap-2" role="group" aria-label="Show">
                        <Button
                            variant={filter === 'all' ? 'default' : 'outline'}
                            size="sm"
                            asChild
                        >
                            <Link href={InquiryController.index()}>All</Link>
                        </Button>
                        <Button
                            variant={
                                filter === 'undelivered' ? 'default' : 'outline'
                            }
                            size="sm"
                            asChild
                        >
                            <Link
                                href={InquiryController.index({
                                    query: { filter: 'undelivered' },
                                })}
                            >
                                Not delivered
                            </Link>
                        </Button>
                    </div>
                </div>
                {inquiries.data.length === 0 ? (
                    <p className="text-muted-foreground">
                        {filter === 'undelivered'
                            ? 'Every request reached Telegram.'
                            : 'No requests yet. They appear when customers tap Buy or send a cart.'}
                    </p>
                ) : (
                    <>
                        <div className="hidden md:block">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Request</TableHead>
                                        <TableHead>Customer</TableHead>
                                        <TableHead>Products</TableHead>
                                        <TableHead>Delivery</TableHead>
                                        <TableHead>
                                            <span className="sr-only">
                                                Actions
                                            </span>
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {inquiries.data.map((request) => (
                                        <TableRow
                                            key={request.id}
                                            className="align-top"
                                        >
                                            <TableCell>
                                                <p className="font-medium">
                                                    {request.store}{' '}
                                                    {request.reference}
                                                </p>
                                                <p className="text-sm text-muted-foreground">
                                                    {sentAt(request.sent_at)}
                                                </p>
                                            </TableCell>
                                            <TableCell>
                                                <p>{request.customer}</p>
                                                {request.contact ? (
                                                    <p className="text-sm text-muted-foreground">
                                                        {request.contact}
                                                    </p>
                                                ) : null}
                                            </TableCell>
                                            <TableCell className="whitespace-normal">
                                                {request.lines.map((line) => (
                                                    <p key={line}>{line}</p>
                                                ))}
                                                <p className="text-sm text-muted-foreground">
                                                    Total {request.total}
                                                </p>
                                            </TableCell>
                                            <TableCell className="whitespace-normal">
                                                <div className="flex flex-col gap-2">
                                                    <DeliveryBadge
                                                        label="Admin"
                                                        delivery={request.admin}
                                                    />
                                                    <DeliveryBadge
                                                        label="Vendor"
                                                        delivery={
                                                            request.vendor
                                                        }
                                                        connected={
                                                            request.vendor
                                                                .connected
                                                        }
                                                    />
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <RetryButton
                                                    request={request}
                                                />
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                        <div className="flex flex-col gap-3 md:hidden">
                            {inquiries.data.map((request) => (
                                <Card key={request.id} className="gap-3 p-4">
                                    <div>
                                        <p className="font-medium">
                                            {request.store} {request.reference}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {request.customer}
                                            {request.contact
                                                ? ` (${request.contact})`
                                                : ''}{' '}
                                            · {sentAt(request.sent_at)}
                                        </p>
                                    </div>
                                    <div className="text-sm">
                                        {request.lines.map((line) => (
                                            <p key={line}>{line}</p>
                                        ))}
                                        <p className="text-muted-foreground">
                                            Total {request.total}
                                        </p>
                                    </div>
                                    <DeliveryBadge
                                        label="Admin"
                                        delivery={request.admin}
                                    />
                                    <DeliveryBadge
                                        label="Vendor"
                                        delivery={request.vendor}
                                        connected={request.vendor.connected}
                                    />
                                    <RetryButton request={request} />
                                </Card>
                            ))}
                        </div>
                        <SimplePagination page={inquiries} />
                    </>
                )}
            </div>
        </>
    );
}
