import { Form, Head } from '@inertiajs/react';
import {
    CheckCircle2,
    ExternalLink,
    MessageSquare,
    RotateCcw,
    Send,
} from 'lucide-react';
import { useState } from 'react';
import RequestController from '@/actions/App/Http/Controllers/Vendor/RequestController';
import { ListToolbar } from '@/components/admin/list-toolbar';
import { EmptyState } from '@/components/empty-state';
import {
    FormSheet,
    FormSheetBody,
    FormSheetFooter,
} from '@/components/form-sheet';
import { PageHeader } from '@/components/page-header';
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
import { dollars, formatDateTime } from '@/lib/format';
import { timeAgo } from '@/lib/time-ago';
import vendor from '@/routes/vendor';

type RequestItem = {
    name: string;
    quantity: number;
    price_cents: number;
    url: string | null;
};

type VendorRequest = {
    id: number;
    reference: string;
    customer: string;
    contact: string | null;
    telegram: string | null;
    from_cart: boolean;
    items: RequestItem[];
    total_cents: number;
    sent_at: string | null;
    handled_at: string | null;
    delivered: boolean;
};

type Filters = { search: string; status: string; kind: string; sort: string };

const defaults: Filters = {
    search: '',
    status: 'all',
    kind: 'all',
    sort: 'newest',
};

function StatusBadge({ request }: { request: VendorRequest }) {
    return request.handled_at ? (
        <Badge variant="secondary">Handled</Badge>
    ) : (
        <Badge variant="default">New</Badge>
    );
}

function itemSummary(request: VendorRequest): string {
    const count = request.items.reduce((sum, item) => sum + item.quantity, 0);
    const names = request.items.map((item) => item.name).join(', ');

    return `${count} ${count === 1 ? 'item' : 'items'}: ${names}`;
}

/**
 * Mark a request handled once replied to, or open it again.
 */
function HandledToggle({
    request,
    size = 'sm',
}: {
    request: VendorRequest;
    size?: 'sm' | 'default';
}) {
    const handled = request.handled_at !== null;

    return (
        <Form
            {...RequestController.update.form(request.id)}
            options={{ preserveScroll: true }}
        >
            {({ processing }) => (
                <>
                    <input
                        type="hidden"
                        name="handled"
                        value={handled ? '0' : '1'}
                    />
                    <Button
                        type="submit"
                        size={size}
                        variant={handled ? 'outline' : 'default'}
                        disabled={processing}
                    >
                        {processing ? (
                            <Spinner />
                        ) : handled ? (
                            <RotateCcw />
                        ) : (
                            <CheckCircle2 />
                        )}
                        {handled ? 'Reopen' : 'Mark handled'}
                    </Button>
                </>
            )}
        </Form>
    );
}

function RequestSheet({
    request,
    onClose,
}: {
    request: VendorRequest | null;
    onClose: () => void;
}) {
    const username = request?.telegram ?? null;

    return (
        <FormSheet
            open={request !== null}
            onOpenChange={(open) => (open ? undefined : onClose())}
            title={request ? `Request ${request.reference}` : 'Request'}
            description={
                request?.sent_at
                    ? `${request.from_cart ? 'Cart' : 'Buy now'}, sent ${formatDateTime(request.sent_at)}.`
                    : undefined
            }
        >
            {request ? (
                <>
                    <FormSheetBody className="gap-6">
                        <section className="grid gap-1">
                            <div className="flex items-center justify-between gap-3">
                                <h3 className="text-sm font-medium text-muted-foreground">
                                    Customer
                                </h3>
                                <StatusBadge request={request} />
                            </div>
                            <p className="text-lg font-semibold">
                                {request.customer}
                            </p>
                            {request.contact ? (
                                username ? (
                                    <a
                                        href={`https://t.me/${username}`}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="text-primary hover:underline"
                                    >
                                        @{username}
                                    </a>
                                ) : (
                                    <p className="text-muted-foreground">
                                        {request.contact}
                                    </p>
                                )
                            ) : (
                                <p className="text-muted-foreground">
                                    No contact shared. Reply in the Telegram
                                    chat where the request arrived.
                                </p>
                            )}
                        </section>
                        <section className="grid gap-2">
                            <h3 className="text-sm font-medium text-muted-foreground">
                                Items
                            </h3>
                            <ul className="divide-y rounded-2xl border">
                                {request.items.map((item) => (
                                    <li
                                        key={`${item.name}-${item.price_cents}`}
                                        className="flex items-center justify-between gap-3 px-4 py-3"
                                    >
                                        <span className="min-w-0">
                                            <span className="text-muted-foreground tabular-nums">
                                                {item.quantity} ×{' '}
                                            </span>
                                            {item.url ? (
                                                <a
                                                    href={item.url}
                                                    target="_blank"
                                                    rel="noreferrer"
                                                    className="font-medium hover:underline"
                                                >
                                                    {item.name}
                                                </a>
                                            ) : (
                                                <span className="font-medium">
                                                    {item.name}
                                                </span>
                                            )}
                                        </span>
                                        <span className="shrink-0 tabular-nums">
                                            {dollars(
                                                item.price_cents *
                                                    item.quantity,
                                            )}
                                        </span>
                                    </li>
                                ))}
                                <li className="flex items-center justify-between gap-3 px-4 py-3 font-semibold">
                                    <span>Total</span>
                                    <span className="tabular-nums">
                                        {dollars(request.total_cents)}
                                    </span>
                                </li>
                            </ul>
                        </section>
                        <p className="text-sm text-muted-foreground">
                            {request.delivered
                                ? 'This request reached your Telegram chat.'
                                : 'This request did not reach your Telegram chat; the Vendly admin has a copy.'}
                            {request.handled_at
                                ? ` Marked handled ${formatDateTime(request.handled_at)}.`
                                : ''}
                        </p>
                    </FormSheetBody>
                    <FormSheetFooter>
                        {username ? (
                            <Button asChild variant="outline">
                                <a
                                    href={`https://t.me/${username}`}
                                    target="_blank"
                                    rel="noreferrer"
                                >
                                    <Send />
                                    Message customer
                                </a>
                            </Button>
                        ) : null}
                        <HandledToggle request={request} size="default" />
                    </FormSheetFooter>
                </>
            ) : null}
        </FormSheet>
    );
}

export default function Requests({
    requests,
    filters,
    newCount,
    open,
}: {
    requests: Paginated<VendorRequest>;
    filters: Filters;
    newCount: number;
    open: number | null;
}) {
    const [openId, setOpenId] = useState<number | null>(open);
    const selected =
        requests.data.find((request) => request.id === openId) ?? null;
    const filtered = (Object.keys(defaults) as (keyof Filters)[]).some(
        (key) => key !== 'sort' && filters[key] !== defaults[key],
    );

    return (
        <>
            <Head title="Requests" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Requests"
                    description="Buy and cart requests from your store. Reply in Telegram, then mark the request handled."
                >
                    {newCount > 0 ? <Badge>{newCount} new</Badge> : null}
                </PageHeader>
                <ListToolbar
                    url={vendor.requests.url()}
                    values={filters}
                    defaults={defaults}
                    searchPlaceholder="Search customer, contact, or #number"
                    filters={[
                        {
                            key: 'status',
                            label: 'Status',
                            options: [
                                { value: 'all', label: 'New and handled' },
                                { value: 'new', label: 'New' },
                                { value: 'handled', label: 'Handled' },
                            ],
                        },
                        {
                            key: 'kind',
                            label: 'Kind',
                            options: [
                                { value: 'all', label: 'Buy and cart' },
                                { value: 'buy', label: 'Buy now' },
                                { value: 'cart', label: 'Cart' },
                            ],
                        },
                    ]}
                    sorts={[
                        { value: 'newest', label: 'Newest first' },
                        { value: 'oldest', label: 'Oldest first' },
                    ]}
                    total={requests.total ?? requests.data.length}
                    noun={['request', 'requests']}
                />
                {requests.data.length === 0 ? (
                    <EmptyState
                        icon={MessageSquare}
                        title={
                            filtered
                                ? 'No requests match these filters'
                                : 'No requests yet'
                        }
                        description={
                            filtered
                                ? 'Try another search or filter, or clear them to see every request.'
                                : 'Share your store link. Requests appear here when customers tap Buy or send a cart.'
                        }
                    />
                ) : (
                    <>
                        <Card className="hidden gap-0 overflow-hidden p-0 md:flex">
                            <Table>
                                <TableHeader>
                                    <TableRow className="hover:bg-transparent">
                                        <TableHead className="pl-5">
                                            Request
                                        </TableHead>
                                        <TableHead>Customer</TableHead>
                                        <TableHead>Items</TableHead>
                                        <TableHead>Total</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="pr-5">
                                            <span className="sr-only">
                                                Actions
                                            </span>
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {requests.data.map((request) => (
                                        <TableRow key={request.id}>
                                            <TableCell className="pl-5">
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        setOpenId(request.id)
                                                    }
                                                    className="rounded-md text-left font-semibold hover:text-primary hover:underline"
                                                >
                                                    {request.reference}
                                                </button>
                                                <p className="text-sm text-muted-foreground">
                                                    {timeAgo(request.sent_at)}
                                                </p>
                                            </TableCell>
                                            <TableCell>
                                                <p className="font-medium">
                                                    {request.customer}
                                                </p>
                                                <p className="text-sm text-muted-foreground">
                                                    {request.contact ??
                                                        'No contact'}
                                                </p>
                                            </TableCell>
                                            <TableCell className="max-w-72">
                                                <p className="truncate text-sm">
                                                    {itemSummary(request)}
                                                </p>
                                                <p className="text-sm text-muted-foreground">
                                                    {request.from_cart
                                                        ? 'Cart'
                                                        : 'Buy now'}
                                                </p>
                                            </TableCell>
                                            <TableCell className="font-semibold tabular-nums">
                                                {dollars(request.total_cents)}
                                            </TableCell>
                                            <TableCell>
                                                <StatusBadge
                                                    request={request}
                                                />
                                            </TableCell>
                                            <TableCell className="pr-5">
                                                <div className="flex justify-end gap-1">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() =>
                                                            setOpenId(
                                                                request.id,
                                                            )
                                                        }
                                                    >
                                                        <ExternalLink />
                                                        View
                                                    </Button>
                                                    <HandledToggle
                                                        request={request}
                                                    />
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </Card>
                        <div className="flex flex-col gap-3 md:hidden">
                            {requests.data.map((request) => (
                                <Card key={request.id} className="gap-3 p-4">
                                    <button
                                        type="button"
                                        onClick={() => setOpenId(request.id)}
                                        className="flex items-start justify-between gap-3 text-left"
                                    >
                                        <span className="min-w-0">
                                            <span className="block font-semibold">
                                                {request.reference} ·{' '}
                                                {request.customer}
                                            </span>
                                            <span className="block truncate text-sm text-muted-foreground">
                                                {itemSummary(request)}
                                            </span>
                                        </span>
                                        <StatusBadge request={request} />
                                    </button>
                                    <div className="flex items-center justify-between gap-3">
                                        <span className="text-sm text-muted-foreground">
                                            <span className="font-semibold text-foreground tabular-nums">
                                                {dollars(request.total_cents)}
                                            </span>
                                            {', '}
                                            {timeAgo(request.sent_at)}
                                        </span>
                                        <HandledToggle request={request} />
                                    </div>
                                </Card>
                            ))}
                        </div>
                        <SimplePagination page={requests} />
                    </>
                )}
            </div>
            <RequestSheet request={selected} onClose={() => setOpenId(null)} />
        </>
    );
}

Requests.layout = {
    breadcrumbs: [{ title: 'Requests', href: vendor.requests() }],
};
