import { Badge } from '@/components/ui/badge';

const labels: Record<string, string> = {
    pending: 'Pending',
    scanned: 'Opened in banking app',
    paid: 'Paid',
    expired: 'Expired',
    failed: 'Failed',
    canceled: 'Canceled',
};

const variants: Record<
    string,
    'success' | 'destructive' | 'warning' | 'secondary'
> = {
    paid: 'success',
    failed: 'destructive',
    expired: 'secondary',
    scanned: 'warning',
    pending: 'secondary',
    canceled: 'secondary',
};

/**
 * Scanned means the QR was opened, not paid, so it never looks like Paid.
 */
export function PaymentStatusBadge({ status }: { status: string }) {
    return (
        <Badge variant={variants[status] ?? 'secondary'}>
            {labels[status] ?? status}
        </Badge>
    );
}
