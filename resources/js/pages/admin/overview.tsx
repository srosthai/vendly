import { Head, Link } from '@inertiajs/react';
import { CreditCard, MessageSquare, Store, WalletCards } from 'lucide-react';
import { AreaChart } from '@/components/charts/area-chart';
import { BarChart } from '@/components/charts/bar-chart';
import { ChartCard, Delta } from '@/components/charts/chart-card';
import { DonutChart } from '@/components/charts/donut-chart';
import type { Slice } from '@/components/charts/donut-chart';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { StatCard } from '@/components/stat-card';
import { PaymentStatusBadge } from '@/components/admin/payment-status-badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { dollars, formatDate } from '@/lib/format';
import { dashboard } from '@/routes';
import admin from '@/routes/admin';

type Stats = {
    vendors: number;
    new_vendors: number;
    suspended: number;
    paid_plans: number;
    revenue_this_month: string;
    revenue_trend: number[];
    undelivered: number;
};

type Series = { values: number[]; total: number; previous: number };

type Charts = {
    days: string[];
    revenue: Series;
    vendors: Series;
    payments_by_status: { key: string; value: number }[];
    delivery: { key: string; value: number }[];
};

const paymentSlices: Record<string, { label: string; color: string }> = {
    paid: { label: 'Paid', color: 'var(--success)' },
    pending: { label: 'Pending', color: 'var(--primary)' },
    scanned: { label: 'Opened', color: 'var(--highlight)' },
    expired: { label: 'Expired', color: 'var(--muted-foreground)' },
    failed: { label: 'Failed', color: 'var(--destructive)' },
};

const deliverySlices: Record<string, { label: string; color: string }> = {
    delivered: { label: 'Reached the vendor', color: 'var(--success)' },
    admin_only: { label: 'Admin copy only', color: 'var(--highlight)' },
    failed: { label: 'Not delivered', color: 'var(--destructive)' },
};

function slices(
    rows: { key: string; value: number }[],
    names: Record<string, { label: string; color: string }>,
): Slice[] {
    return rows.map((row) => ({
        key: row.key,
        value: row.value,
        label: names[row.key]?.label ?? row.key,
        color: names[row.key]?.color ?? 'var(--muted-foreground)',
    }));
}

type RecentPayment = {
    id: number;
    store: string | null;
    plan: string | null;
    amount: string;
    status: string;
    created_at: string | null;
};

export default function AdminOverview({
    stats,
    charts,
    recentPayments,
}: {
    stats: Stats;
    charts: Charts;
    recentPayments: RecentPayment[];
}) {
    return (
        <>
            <Head title="Dashboard" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Overview"
                    description="Vendors, plan payments, and Telegram delivery across Vendly."
                    actions={
                        <Button asChild variant="outline">
                            <Link href={admin.plans()}>Manage plans</Link>
                        </Button>
                    }
                />
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        icon={Store}
                        label="Vendors"
                        value={stats.vendors}
                        hint={`${stats.new_vendors} new this week${stats.suspended > 0 ? `, ${stats.suspended} suspended` : ''}.`}
                    />
                    <StatCard
                        icon={WalletCards}
                        label="Active paid plans"
                        value={stats.paid_plans}
                        hint="Stores on a paid plan today."
                    />
                    <StatCard
                        icon={CreditCard}
                        label="Paid this month"
                        value={stats.revenue_this_month}
                        hint="CutLuy payments marked paid."
                        trend={stats.revenue_trend}
                        trendLabel="Paid amount per day over the last 7 days"
                    />
                    <StatCard
                        icon={MessageSquare}
                        label="Not delivered"
                        value={stats.undelivered}
                        hint={
                            stats.undelivered === 0
                                ? 'Every request reached the admin chat.'
                                : 'Requests the admin chat did not receive.'
                        }
                        tone={stats.undelivered > 0 ? 'warning' : 'default'}
                    />
                </div>
                <div className="grid gap-4 lg:grid-cols-2">
                    <ChartCard
                        title="Paid to Vendly"
                        description="Plan payments marked paid, last 30 days."
                        value={dollars(charts.revenue.total)}
                        delta={
                            <Delta
                                current={charts.revenue.total}
                                previous={charts.revenue.previous}
                            />
                        }
                    >
                        <AreaChart
                            label="Paid to Vendly per day, last 30 days"
                            days={charts.days}
                            values={charts.revenue.values}
                            format={(cents) => dollars(cents)}
                        />
                    </ChartCard>
                    <ChartCard
                        title="New vendors"
                        description="Stores opened, last 30 days."
                        value={charts.vendors.total}
                        delta={
                            <Delta
                                current={charts.vendors.total}
                                previous={charts.vendors.previous}
                            />
                        }
                    >
                        <BarChart
                            label="New vendors per day, last 30 days"
                            days={charts.days}
                            values={charts.vendors.values}
                        />
                    </ChartCard>
                    <ChartCard
                        title="Payments by status"
                        description="Payments started in the last 30 days."
                    >
                        {charts.payments_by_status.length === 0 ? (
                            <p className="py-6 text-sm text-muted-foreground">
                                No payments in the last 30 days.
                            </p>
                        ) : (
                            <DonutChart
                                label="Payments by status"
                                centerLabel="payments"
                                slices={slices(
                                    charts.payments_by_status,
                                    paymentSlices,
                                )}
                            />
                        )}
                    </ChartCard>
                    <ChartCard
                        title="Request delivery"
                        description="Where requests from the last 30 days ended up."
                    >
                        {charts.delivery.length === 0 ? (
                            <p className="py-6 text-sm text-muted-foreground">
                                No requests in the last 30 days.
                            </p>
                        ) : (
                            <DonutChart
                                label="Request delivery"
                                centerLabel="requests"
                                slices={slices(charts.delivery, deliverySlices)}
                            />
                        )}
                    </ChartCard>
                </div>
                <Card className="gap-0 overflow-hidden p-0">
                    <CardHeader className="flex flex-row flex-wrap items-center justify-between gap-3 border-b px-5 py-4">
                        <div>
                            <CardTitle>Recent payments</CardTitle>
                            <CardDescription>
                                Opened in a banking app is not paid.
                            </CardDescription>
                        </div>
                        <Button asChild variant="outline" size="sm">
                            <Link href={admin.payments()}>All payments</Link>
                        </Button>
                    </CardHeader>
                    {recentPayments.length === 0 ? (
                        <EmptyState
                            bare
                            icon={CreditCard}
                            title="No payments yet"
                            description="Payments appear when a vendor pays for a plan."
                        />
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow className="hover:bg-transparent">
                                    <TableHead className="pl-5">
                                        Store
                                    </TableHead>
                                    <TableHead className="hidden sm:table-cell">
                                        Plan
                                    </TableHead>
                                    <TableHead>Amount</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="hidden pr-5 md:table-cell">
                                        Started
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {recentPayments.map((payment) => (
                                    <TableRow key={payment.id}>
                                        <TableCell className="pl-5 font-medium">
                                            {payment.store ?? 'Deleted store'}
                                        </TableCell>
                                        <TableCell className="hidden sm:table-cell">
                                            {payment.plan}
                                        </TableCell>
                                        <TableCell className="tabular-nums">
                                            {payment.amount}
                                        </TableCell>
                                        <TableCell>
                                            <PaymentStatusBadge
                                                status={payment.status}
                                            />
                                        </TableCell>
                                        <TableCell className="hidden pr-5 text-muted-foreground md:table-cell">
                                            {formatDate(payment.created_at)}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </Card>
            </div>
        </>
    );
}

AdminOverview.layout = {
    breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
};
