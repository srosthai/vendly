import { Form, Head } from '@inertiajs/react';
import QRCode from 'qrcode';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

type Payment = {
    status: string;
    checkout_url: string | null;
    qr_string: string | null;
    amount_cents: number;
};

const statusLabel: Record<string, string> = {
    pending: 'Pending',
    scanned: 'Opened in banking app',
    paid: 'Paid',
};

export default function Plan({
    usage,
    plans,
    payment,
}: {
    usage: { published: number; limit: number; plan: string | null };
    plans: {
        id: number;
        name: string;
        price_cents: number;
        product_limit: number;
    }[];
    payment: Payment | null;
}) {
    const [open, setOpen] = useState(payment !== null);
    const [qr, setQr] = useState<string | null>(null);
    const width =
        usage.limit === 0
            ? 0
            : Math.min(100, Math.round((usage.published / usage.limit) * 100));

    useEffect(() => {
        if (!payment?.qr_string) {
            return;
        }

        void QRCode.toDataURL(payment.qr_string, {
            margin: 1,
            width: 240,
        }).then(setQr);
    }, [payment?.qr_string]);

    return (
        <>
            <Head title="Plan" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {usage.plan ?? 'Plan'}
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        {usage.published} of {usage.limit} published
                    </p>
                    <div className="mt-3 h-2 w-full max-w-sm overflow-hidden rounded-full bg-muted">
                        <div
                            className="h-full bg-primary"
                            style={{ width: `${width}%` }}
                        />
                    </div>
                </div>
                <div className="grid gap-3 md:grid-cols-2">
                    {plans.map((plan) => (
                        <Card key={plan.id} className="gap-4 p-4">
                            <div>
                                <h2 className="font-medium">{plan.name}</h2>
                                <p className="text-sm text-muted-foreground">
                                    ${(plan.price_cents / 100).toFixed(2)} /
                                    month · {plan.product_limit} products
                                </p>
                            </div>
                            <Form
                                action={`/plans/${plan.id}/payments`}
                                method="post"
                            >
                                {({ processing }) => (
                                    <Button type="submit" disabled={processing}>
                                        Pay $
                                        {(plan.price_cents / 100).toFixed(2)}
                                    </Button>
                                )}
                            </Form>
                        </Card>
                    ))}
                </div>
            </div>
            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {payment
                                ? (statusLabel[payment.status] ??
                                  payment.status)
                                : 'Payment'}
                        </DialogTitle>
                    </DialogHeader>
                    {payment ? (
                        <div className="flex flex-col items-center gap-4">
                            <p>${(payment.amount_cents / 100).toFixed(2)}</p>
                            {qr ? (
                                <img
                                    src={qr}
                                    alt="Payment QR code"
                                    className="size-60"
                                />
                            ) : null}
                            {payment.checkout_url ? (
                                <Button variant="outline" asChild>
                                    <a href={payment.checkout_url}>
                                        Open payment page
                                    </a>
                                </Button>
                            ) : null}
                        </div>
                    ) : null}
                </DialogContent>
            </Dialog>
        </>
    );
}
