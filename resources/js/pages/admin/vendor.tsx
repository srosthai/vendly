import { Head, Link, setLayoutProps } from '@inertiajs/react';
import {
    ChevronLeft,
    ExternalLink,
    MessageSquare,
    Package,
    Send,
    ShoppingBag,
} from 'lucide-react';
import type { ReactNode } from 'react';
import {
    StoreStatusBadge,
    StoreSuspendAction,
} from '@/components/admin/store-suspension';
import { EmptyState } from '@/components/empty-state';
import { SimplePagination } from '@/components/simple-pagination';
import type { Paginated } from '@/components/simple-pagination';
import { StoreMark } from '@/components/storefront/store-header';
import { mapLink, StoreContact } from '@/components/storefront/store-profile';
import type { StoreProfile } from '@/components/storefront/store-profile';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardDescription, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useInitials } from '@/hooks/use-initials';
import { dollars, formatDate, formatDateTime } from '@/lib/format';
import admin from '@/routes/admin';

type VendorStore = {
    id: number;
    name: string;
    slug: string;
    url: string;
    telegram_url: string | null;
    logo: string | null;
    description: string | null;
    created_at: string | null;
    telegram_connected: boolean;
    suspended: boolean;
    suspended_at: string | null;
} & StoreProfile;

type Owner = {
    name: string;
    email: string | null;
    phone: string | null;
    telegram_username: string | null;
    avatar: string | null;
    joined_at: string | null;
    email_verified: boolean;
};

type Plan = {
    name: string;
    free: boolean;
    status: string;
    ends_at: string | null;
    limit: number;
};

type Counts = {
    total: number;
    published: number;
    drafts: number;
    sold_out: number;
};

type ProductRow = {
    id: number;
    name: string;
    price_cents: number;
    status: string;
    sold_out: boolean;
    image: string | null;
    category: string | null;
    brand: string | null;
    url: string | null;
};

type Named = { name: string; products_count: number };

type PaymentRow = {
    id: number;
    plan: string | null;
    amount_cents: number;
    period: string;
    status: string;
    created_at: string | null;
    paid_at: string | null;
};

type RequestRow = {
    id: number;
    number: number | null;
    customer: string;
    from_cart: boolean;
    items_count: number;
    delivered: boolean;
    failed: boolean;
    created_at: string | null;
};

const paymentStatus: Record<
    string,
    { label: string; variant: 'success' | 'secondary' | 'destructive' }
> = {
    paid: { label: 'Paid', variant: 'success' },
    pending: { label: 'Pending', variant: 'secondary' },
    scanned: { label: 'Opened', variant: 'secondary' },
    expired: { label: 'Expired', variant: 'destructive' },
    failed: { label: 'Failed', variant: 'destructive' },
};

/**
 * A titled card for one part of the vendor. `aside` sits beside the title,
 * such as a count.
 */
function Section({
    title,
    description,
    aside,
    children,
}: {
    title: string;
    description?: string;
    aside?: ReactNode;
    children: ReactNode;
}) {
    return (
        <Card className="gap-4 p-5 sm:p-6">
            <div className="flex flex-wrap items-start justify-between gap-2">
                <div>
                    <CardTitle>{title}</CardTitle>
                    {description ? (
                        <CardDescription className="mt-1">
                            {description}
                        </CardDescription>
                    ) : null}
                </div>
                {aside}
            </div>
            {children}
        </Card>
    );
}

/**
 * One label and value in a details list. Empty values show a dash so the
 * rows stay aligned.
 */
function Detail({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div className="flex items-start justify-between gap-4 py-2.5">
            <dt className="text-sm text-muted-foreground">{label}</dt>
            <dd className="text-right text-sm font-medium break-all">
                {children || <span className="text-muted-foreground">—</span>}
            </dd>
        </div>
    );
}

function Tile({ label, value }: { label: string; value: ReactNode }) {
    return (
        <div className="rounded-2xl border bg-card p-4">
            <p className="text-sm text-muted-foreground">{label}</p>
            <p className="mt-1 text-2xl font-bold tracking-tight tabular-nums">
                {value}
            </p>
        </div>
    );
}

export default function Vendor({
    store,
    owner,
    plan,
    counts,
    products,
    categories,
    brands,
    payments,
    paidTotalCents,
    requests,
    requestsCount,
}: {
    store: VendorStore;
    owner: Owner | null;
    plan: Plan | null;
    counts: Counts;
    products: Paginated<ProductRow>;
    categories: Named[];
    brands: Named[];
    payments: PaymentRow[];
    paidTotalCents: number;
    requests: RequestRow[];
    requestsCount: number;
}) {
    const getInitials = useInitials();

    setLayoutProps({
        breadcrumbs: [
            { title: 'Vendors', href: admin.vendors() },
            { title: store.name, href: admin.vendors.show(store.id) },
        ],
    });

    return (
        <>
            <Head title={store.name} />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <Link
                    href={admin.vendors()}
                    className="inline-flex min-h-11 items-center gap-1 self-start rounded-full pr-3 text-sm font-medium text-muted-foreground hover:text-foreground"
                >
                    <ChevronLeft className="size-4" aria-hidden="true" />
                    All vendors
                </Link>

                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div className="flex min-w-0 items-center gap-4">
                        <StoreMark store={store} className="size-16 text-2xl" />
                        <div className="min-w-0">
                            <h1 className="truncate text-2xl font-bold tracking-tight sm:text-3xl">
                                {store.name}
                            </h1>
                            <div className="mt-1.5 flex flex-wrap items-center gap-2">
                                <StoreStatusBadge suspended={store.suspended} />
                                {store.telegram_connected ? (
                                    <Badge variant="secondary">
                                        <Send aria-hidden="true" />
                                        Telegram connected
                                    </Badge>
                                ) : (
                                    <Badge variant="outline">
                                        Telegram not connected
                                    </Badge>
                                )}
                                <span className="text-sm text-muted-foreground">
                                    /s/{store.slug}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {!store.suspended ? (
                            <Button asChild variant="outline">
                                <a
                                    href={store.url}
                                    target="_blank"
                                    rel="noreferrer"
                                >
                                    <ExternalLink />
                                    Open store
                                </a>
                            </Button>
                        ) : null}
                        <StoreSuspendAction store={store} size="default" />
                    </div>
                </div>

                {store.suspended && store.suspended_at ? (
                    <p
                        role="status"
                        className="rounded-2xl bg-destructive/10 px-4 py-3 text-sm text-destructive"
                    >
                        Suspended on {formatDateTime(store.suspended_at)}.
                        Customers cannot see the store and the vendor cannot
                        change it.
                    </p>
                ) : null}

                <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
                    <Tile
                        label="Published"
                        value={
                            plan ? (
                                <>
                                    {counts.published}
                                    <span className="text-base font-normal text-muted-foreground">
                                        {' '}
                                        of {plan.limit}
                                    </span>
                                </>
                            ) : (
                                counts.published
                            )
                        }
                    />
                    <Tile label="Drafts" value={counts.drafts} />
                    <Tile label="Requests" value={requestsCount} />
                    <Tile
                        label="Paid to Vendly"
                        value={dollars(paidTotalCents)}
                    />
                </div>

                <div className="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
                    <div className="flex min-w-0 flex-col gap-6">
                        <Section
                            title="Products"
                            description={`${counts.total} in total, ${counts.sold_out} sold out.`}
                        >
                            {products.data.length === 0 ? (
                                <EmptyState
                                    bare
                                    icon={Package}
                                    title="No products yet"
                                    description="Products appear here as soon as the vendor adds them."
                                />
                            ) : (
                                <>
                                    <div className="-mx-5 overflow-x-auto sm:-mx-6">
                                        <Table>
                                            <TableHeader>
                                                <TableRow className="hover:bg-transparent">
                                                    <TableHead className="pl-5 sm:pl-6">
                                                        Product
                                                    </TableHead>
                                                    <TableHead>Price</TableHead>
                                                    <TableHead>
                                                        Status
                                                    </TableHead>
                                                    <TableHead className="pr-5 sm:pr-6">
                                                        Category
                                                    </TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {products.data.map(
                                                    (product) => (
                                                        <TableRow
                                                            key={product.id}
                                                        >
                                                            <TableCell className="pl-5 sm:pl-6">
                                                                <span className="flex items-center gap-3">
                                                                    <span className="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-muted">
                                                                        {product.image ? (
                                                                            <img
                                                                                src={
                                                                                    product.image
                                                                                }
                                                                                alt=""
                                                                                className="size-full object-cover"
                                                                            />
                                                                        ) : (
                                                                            <Package
                                                                                className="size-4 text-muted-foreground"
                                                                                aria-hidden="true"
                                                                            />
                                                                        )}
                                                                    </span>
                                                                    {product.url ? (
                                                                        <a
                                                                            href={
                                                                                product.url
                                                                            }
                                                                            target="_blank"
                                                                            rel="noreferrer"
                                                                            className="font-medium hover:underline"
                                                                        >
                                                                            {
                                                                                product.name
                                                                            }
                                                                        </a>
                                                                    ) : (
                                                                        <span className="font-medium">
                                                                            {
                                                                                product.name
                                                                            }
                                                                        </span>
                                                                    )}
                                                                </span>
                                                            </TableCell>
                                                            <TableCell className="tabular-nums">
                                                                {dollars(
                                                                    product.price_cents,
                                                                )}
                                                            </TableCell>
                                                            <TableCell>
                                                                {product.sold_out ? (
                                                                    <Badge variant="outline">
                                                                        Sold out
                                                                    </Badge>
                                                                ) : product.status ===
                                                                  'published' ? (
                                                                    <Badge variant="success">
                                                                        Published
                                                                    </Badge>
                                                                ) : (
                                                                    <Badge variant="secondary">
                                                                        Draft
                                                                    </Badge>
                                                                )}
                                                            </TableCell>
                                                            <TableCell className="pr-5 text-muted-foreground sm:pr-6">
                                                                {[
                                                                    product.category,
                                                                    product.brand,
                                                                ]
                                                                    .filter(
                                                                        Boolean,
                                                                    )
                                                                    .join(
                                                                        ', ',
                                                                    ) || '—'}
                                                            </TableCell>
                                                        </TableRow>
                                                    ),
                                                )}
                                            </TableBody>
                                        </Table>
                                    </div>
                                    <SimplePagination page={products} />
                                </>
                            )}
                        </Section>

                        <Section
                            title="Requests"
                            description="The latest ten buy and cart requests."
                        >
                            {requests.length === 0 ? (
                                <EmptyState
                                    bare
                                    icon={MessageSquare}
                                    title="No requests yet"
                                    description="Requests appear here when customers send products to this store."
                                />
                            ) : (
                                <ul className="divide-y">
                                    {requests.map((request) => (
                                        <li
                                            key={request.id}
                                            className="flex flex-wrap items-center justify-between gap-3 py-3"
                                        >
                                            <div className="min-w-0">
                                                <p className="font-medium">
                                                    {request.number
                                                        ? `#${request.number} `
                                                        : ''}
                                                    {request.customer}
                                                </p>
                                                <p className="text-sm text-muted-foreground">
                                                    {request.from_cart
                                                        ? `Cart, ${request.items_count} ${request.items_count === 1 ? 'item' : 'items'}`
                                                        : 'Buy now'}
                                                    {request.created_at
                                                        ? `, ${formatDateTime(request.created_at)}`
                                                        : ''}
                                                </p>
                                            </div>
                                            {request.delivered ? (
                                                <Badge variant="success">
                                                    Reached the vendor
                                                </Badge>
                                            ) : request.failed ? (
                                                <Badge variant="destructive">
                                                    Not delivered
                                                </Badge>
                                            ) : (
                                                <Badge variant="secondary">
                                                    Admin copy only
                                                </Badge>
                                            )}
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </Section>

                        <Section
                            title="Plan payments"
                            description="The latest ten payments through CutLuy."
                        >
                            {payments.length === 0 ? (
                                <EmptyState
                                    bare
                                    icon={ShoppingBag}
                                    title="No payments yet"
                                    description="Payments appear here when the vendor pays for a plan."
                                />
                            ) : (
                                <ul className="divide-y">
                                    {payments.map((payment) => {
                                        const status = paymentStatus[
                                            payment.status
                                        ] ?? {
                                            label: payment.status,
                                            variant: 'secondary' as const,
                                        };

                                        return (
                                            <li
                                                key={payment.id}
                                                className="flex flex-wrap items-center justify-between gap-3 py-3"
                                            >
                                                <div>
                                                    <p className="font-medium tabular-nums">
                                                        {dollars(
                                                            payment.amount_cents,
                                                        )}{' '}
                                                        <span className="font-normal text-muted-foreground">
                                                            for {payment.plan},{' '}
                                                            {payment.period ===
                                                            'yearly'
                                                                ? 'one year'
                                                                : 'one month'}
                                                        </span>
                                                    </p>
                                                    <p className="text-sm text-muted-foreground">
                                                        {payment.paid_at
                                                            ? `Paid ${formatDateTime(payment.paid_at)}`
                                                            : `Started ${formatDateTime(payment.created_at)}`}
                                                    </p>
                                                </div>
                                                <Badge variant={status.variant}>
                                                    {status.label}
                                                </Badge>
                                            </li>
                                        );
                                    })}
                                </ul>
                            )}
                        </Section>
                    </div>

                    <div className="flex flex-col gap-6">
                        <Section title="Owner">
                            {owner ? (
                                <>
                                    <div className="flex items-center gap-3">
                                        <Avatar className="size-12">
                                            {owner.avatar ? (
                                                <AvatarImage
                                                    src={owner.avatar}
                                                    alt=""
                                                />
                                            ) : null}
                                            <AvatarFallback className="bg-secondary text-secondary-foreground">
                                                {getInitials(owner.name)}
                                            </AvatarFallback>
                                        </Avatar>
                                        <div className="min-w-0">
                                            <p className="truncate font-semibold">
                                                {owner.name}
                                            </p>
                                            <p className="truncate text-sm text-muted-foreground">
                                                {owner.email ?? 'No email'}
                                            </p>
                                        </div>
                                    </div>
                                    <dl className="divide-y">
                                        <Detail label="Email">
                                            {owner.email
                                                ? owner.email_verified
                                                    ? 'Verified'
                                                    : 'Not verified'
                                                : null}
                                        </Detail>
                                        <Detail label="Phone">
                                            {owner.phone}
                                        </Detail>
                                        <Detail label="Telegram">
                                            {owner.telegram_username
                                                ? `@${owner.telegram_username}`
                                                : null}
                                        </Detail>
                                        <Detail label="Joined">
                                            {formatDate(owner.joined_at)}
                                        </Detail>
                                    </dl>
                                </>
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    The owner account no longer exists.
                                </p>
                            )}
                        </Section>

                        <Section
                            title="Plan"
                            aside={
                                plan ? (
                                    <Badge
                                        variant={
                                            plan.status === 'active'
                                                ? 'success'
                                                : 'destructive'
                                        }
                                    >
                                        {plan.status === 'active'
                                            ? 'Active'
                                            : 'Ended'}
                                    </Badge>
                                ) : null
                            }
                        >
                            {plan ? (
                                <>
                                    <div>
                                        <p className="font-semibold">
                                            {plan.name}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {plan.free
                                                ? 'Free plan, no end date.'
                                                : plan.ends_at
                                                  ? `${plan.status === 'active' ? 'Paid until' : 'Ended'} ${formatDate(plan.ends_at)}.`
                                                  : ''}
                                        </p>
                                    </div>
                                    <div className="grid gap-2">
                                        <p className="text-sm text-muted-foreground">
                                            {counts.published} of {plan.limit}{' '}
                                            published
                                        </p>
                                        <Progress
                                            value={counts.published}
                                            max={plan.limit}
                                            aria-label="Published products"
                                        />
                                    </div>
                                </>
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    No plan yet.
                                </p>
                            )}
                        </Section>

                        <Section title="Store">
                            {store.description ? (
                                <p className="text-sm text-muted-foreground">
                                    {store.description}
                                </p>
                            ) : null}
                            <dl className="divide-y">
                                <Detail label="Web link">
                                    <a
                                        href={store.url}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="text-primary hover:underline"
                                    >
                                        /s/{store.slug}
                                    </a>
                                </Detail>
                                <Detail label="Telegram link">
                                    {store.telegram_url ? (
                                        <a
                                            href={store.telegram_url}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="text-primary hover:underline"
                                        >
                                            Open mini app
                                        </a>
                                    ) : null}
                                </Detail>
                                <Detail label="Opened">
                                    {formatDate(store.created_at)}
                                </Detail>
                                <Detail label="Phone">
                                    {store.phone ? (
                                        <a
                                            href={`tel:${store.phone.replace(/[^\d+]/g, '')}`}
                                            className="text-primary hover:underline"
                                        >
                                            {store.phone}
                                        </a>
                                    ) : null}
                                </Detail>
                                <Detail label="Address">
                                    {store.address ? (
                                        <a
                                            href={mapLink(store.address)}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="text-primary hover:underline"
                                        >
                                            {store.address}
                                        </a>
                                    ) : null}
                                </Detail>
                                <Detail label="Hours">{store.hours}</Detail>
                            </dl>
                            {Object.keys(store.socials).length > 0 ? (
                                <StoreContact
                                    profile={{
                                        ...store,
                                        phone: null,
                                        address: null,
                                        hours: null,
                                    }}
                                />
                            ) : null}
                        </Section>

                        <Section title="Categories and brands">
                            <NamedList label="Categories" items={categories} />
                            <NamedList label="Brands" items={brands} />
                        </Section>
                    </div>
                </div>
            </div>
        </>
    );
}

function NamedList({ label, items }: { label: string; items: Named[] }) {
    return (
        <div>
            <p className="mb-2 text-sm font-medium">{label}</p>
            {items.length === 0 ? (
                <p className="text-sm text-muted-foreground">None yet.</p>
            ) : (
                <ul className="flex flex-wrap gap-2">
                    {items.map((item) => (
                        <li
                            key={item.name}
                            className="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-sm"
                        >
                            {item.name}
                            <span className="text-muted-foreground tabular-nums">
                                {item.products_count}
                            </span>
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}

Vendor.layout = {
    breadcrumbs: [{ title: 'Vendors', href: admin.vendors() }],
};
