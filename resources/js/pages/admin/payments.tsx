import { Head } from '@inertiajs/react';
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

type Payment = {
    id: number;
    store: string | null;
    plan: string | null;
    amount_cents: number;
    status: string;
};

const labels: Record<string, string> = {
    pending: 'Pending',
    scanned: 'Opened in banking app',
    paid: 'Paid',
    expired: 'Expired',
    failed: 'Failed',
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
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Payments
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Opened in a banking app is not paid.
                    </p>
                </div>
                {payments.data.length === 0 ? (
                    <p className="text-muted-foreground">No payments yet.</p>
                ) : (
                    <>
                        <div className="hidden md:block">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Store</TableHead>
                                        <TableHead>Plan</TableHead>
                                        <TableHead>Amount</TableHead>
                                        <TableHead>Status</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {payments.data.map((payment) => (
                                        <TableRow key={payment.id}>
                                            <TableCell>
                                                {payment.store}
                                            </TableCell>
                                            <TableCell>
                                                {payment.plan}
                                            </TableCell>
                                            <TableCell>
                                                $
                                                {(
                                                    payment.amount_cents / 100
                                                ).toFixed(2)}
                                            </TableCell>
                                            <TableCell>
                                                {labels[payment.status] ??
                                                    payment.status}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                        <div className="flex flex-col gap-3 md:hidden">
                            {payments.data.map((payment) => (
                                <Card key={payment.id} className="gap-1 p-4">
                                    <p className="font-medium">
                                        {payment.store}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        {payment.plan} · $
                                        {(payment.amount_cents / 100).toFixed(
                                            2,
                                        )}{' '}
                                        ·{' '}
                                        {labels[payment.status] ??
                                            payment.status}
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
