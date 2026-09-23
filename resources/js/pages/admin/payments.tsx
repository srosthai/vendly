import { Head } from '@inertiajs/react';
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

export default function Payments({ payments }: { payments: Payment[] }) {
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
                {payments.length === 0 ? (
                    <p className="text-muted-foreground">No payments yet.</p>
                ) : (
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
                            {payments.map((payment) => (
                                <TableRow key={payment.id}>
                                    <TableCell>{payment.store}</TableCell>
                                    <TableCell>{payment.plan}</TableCell>
                                    <TableCell>
                                        $
                                        {(payment.amount_cents / 100).toFixed(
                                            2,
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        {labels[payment.status] ??
                                            payment.status}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                )}
            </div>
        </>
    );
}
