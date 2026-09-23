import { Head, Link } from '@inertiajs/react';
import { CreditCard, MessageSquare, Store, WalletCards } from 'lucide-react';
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
import { formatDate } from '@/lib/format';
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
    recentPayments,
}: {
    stats: Stats;
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
