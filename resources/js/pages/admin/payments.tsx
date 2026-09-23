import { Head } from '@inertiajs/react';
import { CreditCard } from 'lucide-react';
import { PaymentStatusBadge } from '@/components/admin/payment-status-badge';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
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
import { dollars, formatDate } from '@/lib/format';
import admin from '@/routes/admin';

type Payment = {
    id: number;
    store: string | null;
    plan: string | null;
    amount_cents: number;
    status: string;
    created_at: string | null;
    paid_at: string | null;
};

export default function Payments({
    payments,
}: {
    payments: Paginated<Payment>;
}) {
    return (
        <>
            <Head title="Payments" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Payments"
                    description="Plan payments through CutLuy. A plan only turns on when CutLuy reports the payment as paid; opened in a banking app is not paid."
                />
                {payments.data.length === 0 ? (
                    <EmptyState
                        icon={CreditCard}
                        title="No payments yet"
                        description="Payments appear when a vendor chooses a paid plan."
                    />
                ) : (
                    <>
                        <Card className="hidden gap-0 overflow-hidden p-0 md:flex">
                            <Table>
                                <TableHeader>
                                    <TableRow className="hover:bg-transparent">
                                        <TableHead className="pl-5">
                                            Store
                                        </TableHead>
                                        <TableHead>Plan</TableHead>
                                        <TableHead>Amount</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Started</TableHead>
                                        <TableHead className="pr-5">
                                            Paid
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {payments.data.map((payment) => (
                                        <TableRow key={payment.id}>
                                            <TableCell className="pl-5 font-medium">
                                                {payment.store ??
                                                    'Deleted store'}
                                            </TableCell>
                                            <TableCell>
                                                {payment.plan}
                                            </TableCell>
                                            <TableCell className="tabular-nums">
                                                {dollars(payment.amount_cents)}
                                            </TableCell>
                                            <TableCell>
                                                <PaymentStatusBadge
                                                    status={payment.status}
                                                />
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {formatDate(payment.created_at)}
                                            </TableCell>
                                            <TableCell className="pr-5 text-muted-foreground">
                                                {formatDate(payment.paid_at) ||
                                                    'Not paid'}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </Card>
                        <div className="flex flex-col gap-3 md:hidden">
                            {payments.data.map((payment) => (
                                <Card key={payment.id} className="gap-2 p-4">
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="min-w-0">
                                            <p className="font-medium">
                                                {payment.store ??
                                                    'Deleted store'}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {payment.plan},{' '}
                                                {dollars(payment.amount_cents)}
                                            </p>
                                        </div>
                                        <PaymentStatusBadge
                                            status={payment.status}
                                        />
                                    </div>
                                    <p className="text-sm text-muted-foreground">
                                        Started {formatDate(payment.created_at)}
                                        {payment.paid_at
                                            ? `, paid ${formatDate(payment.paid_at)}`
                                            : ''}
                                    </p>
                                </Card>
                            ))}
                        </div>
                        <SimplePagination page={payments} />
                    </>
                )}
            </div>
        </>
    );
}

Payments.layout = {
    breadcrumbs: [{ title: 'Payments', href: admin.payments() }],
};
