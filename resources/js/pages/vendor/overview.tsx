import { Head, Link } from '@inertiajs/react';
import {
    CalendarClock,
    MessageSquare,
    Package,
    PenLine,
    Plus,
    Send,
} from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { StatCard } from '@/components/stat-card';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { ShareLinks } from '@/components/vendor/share-links';
import { formatDate, formatDateTime } from '@/lib/format';
import { dashboard } from '@/routes';
import vendor from '@/routes/vendor';
import { create as createProduct } from '@/routes/vendor/products';

type Stats = {
    published: number;
    drafts: number;
    limit: number;
    plan: string | null;
    free: boolean;
    ends_at: string | null;
    can_publish: boolean;
    requests_this_week: number;
    requests_trend: number[];
};

type RecentRequest = {
    id: number;
    reference: string;
    customer: string;
    contact: string | null;
    lines: string[];
    total: string;
    sent_at: string | null;
};

export default function VendorOverview({
    store,
    stats,
    recentRequests,
}: {
    store: {
        name: string;
        web_url: string;
        telegram_url: string | null;
        telegram_connected: boolean;
    };
    stats: Stats;
    recentRequests: RecentRequest[];
}) {
    const left = Math.max(0, stats.limit - stats.published);

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={store.name}
                    description="How your store is doing this week."
                    actions={
                        <Button asChild>
                            <Link href={createProduct()}>
                                <Plus />
                                New product
                            </Link>
                        </Button>
                    }
                />

                {!store.telegram_connected ? (
                    <Card className="flex-row flex-wrap items-center gap-4 border-highlight/40 bg-highlight/10 p-5">
                        <Send
                            className="size-5 shrink-0 text-highlight-foreground dark:text-highlight"
                            aria-hidden="true"
                        />
                        <p className="min-w-0 flex-1 text-sm">
                            Buy requests only reach the Vendly admin until you
                            connect your Telegram chat.
                        </p>
                        <Button asChild size="sm" variant="highlight">
                            <Link href={vendor.telegram()}>
                                Connect Telegram
                            </Link>
                        </Button>
                    </Card>
                ) : null}

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        icon={Package}
                        label="Published"
                        value={`${stats.published} of ${stats.limit}`}
                        hint={
                            !stats.can_publish
                                ? 'Plan ended. Renew to publish.'
                                : left === 0
                                  ? 'Plan is full.'
                                  : `${left} more can go live.`
                        }
                        tone={
                            !stats.can_publish || left === 0
                                ? 'warning'
                                : 'default'
                        }
                    />
                    <StatCard
                        icon={PenLine}
                        label="Drafts"
                        value={stats.drafts}
                        hint={
                            stats.drafts === 0
                                ? 'Nothing waiting.'
                                : 'Only you can see drafts.'
                        }
                    />
                    <StatCard
                        icon={MessageSquare}
                        label="Requests this week"
                        value={stats.requests_this_week}
                        hint="Buy and cart requests sent to Telegram."
                        trend={stats.requests_trend}
                        trendLabel="Requests per day over the last 7 days"
                    />
                    <StatCard
                        icon={CalendarClock}
                        label="Plan"
                        value={stats.plan ?? 'None'}
                        hint={
                            stats.ends_at
                                ? `${stats.can_publish ? 'Paid until' : 'Ended'} ${formatDate(stats.ends_at)}`
                                : stats.free
                                  ? 'Free, no end date.'
                                  : ''
                        }
                    />
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    <Card className="gap-0 overflow-hidden p-0 lg:col-span-2">
                        <CardHeader className="flex flex-row items-center justify-between gap-3 border-b px-5 py-4">
                            <div>
                                <CardTitle>Recent requests</CardTitle>
                                <CardDescription>
                                    What customers asked for on Telegram.
                                </CardDescription>
                            </div>
                        </CardHeader>
                        {recentRequests.length === 0 ? (
                            <EmptyState
                                bare
                                icon={MessageSquare}
                                title="No requests yet"
                                description="Share your store link. Requests appear here when customers tap Buy or send a cart."
                            />
                        ) : (
                            <ul className="divide-y">
                                {recentRequests.map((request) => (
                                    <li
                                        key={request.id}
                                        className="flex flex-wrap items-start justify-between gap-3 px-5 py-4"
                                    >
                                        <div className="min-w-0">
                                            <p className="font-medium">
                                                {request.customer}{' '}
                                                <span className="text-muted-foreground">
                                                    {request.reference}
                                                </span>
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {request.lines.join(', ')}
                                            </p>
                                            {request.contact ? (
                                                <p className="text-sm text-muted-foreground">
                                                    {request.contact}
                                                </p>
                                            ) : null}
                                        </div>
                                        <div className="text-right">
                                            <p className="font-medium tabular-nums">
                                                {request.total}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {formatDateTime(
                                                    request.sent_at,
                                                )}
                                            </p>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Card>

                    <div className="flex flex-col gap-4">
                        <Card className="gap-4 p-5">
                            <div>
                                <CardTitle>Share your store</CardTitle>
                                <CardDescription className="mt-1">
                                    Send these links to customers.
                                </CardDescription>
                            </div>
                            <ShareLinks
                                webUrl={store.web_url}
                                telegramUrl={store.telegram_url}
                            />
                        </Card>
                    </div>
                </div>
            </div>
        </>
    );
}

VendorOverview.layout = {
    breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
};
