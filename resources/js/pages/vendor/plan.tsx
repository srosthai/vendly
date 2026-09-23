import { Form, Head, useHttp } from '@inertiajs/react';
import QRCode from 'qrcode';
import { useCallback, useEffect, useState } from 'react';
import PlanPaymentController from '@/actions/App/Http/Controllers/Billing/PlanPaymentController';
import InputError from '@/components/input-error';
import { PageHeader } from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Progress } from '@/components/ui/progress';
import { Spinner } from '@/components/ui/spinner';
import { dollars, formatDate } from '@/lib/format';
import vendor from '@/routes/vendor';

type Payment = {
    public_id: string;
    status: string;
    checkout_url: string | null;
    qr_string: string | null;
    amount_cents: number;
    notice?: string | null;
};

type Usage = {
    published: number;
    limit: number;
    plan: string | null;
    free: boolean;
    status: string | null;
    ends_at: string | null;
    can_publish: boolean;
};

const statusLabel: Record<string, string> = {
    pending: 'Pending',
    scanned: 'Opened in banking app',
    paid: 'Paid',
    expired: 'Expired',
    failed: 'Failed',
};

const finalStatuses = ['paid', 'expired', 'failed'];

export default function Plan({
    usage,
    plans,
    payment: flashedPayment,
}: {
    usage: Usage;
    plans: {
        id: number;
        name: string;
        price_cents: number;
        product_limit: number;
    }[];
    payment: Payment | null;
}) {
    const [payment, setPayment] = useState<Payment | null>(flashedPayment);
    const [open, setOpen] = useState(flashedPayment !== null);
    const [qr, setQr] = useState<string | null>(null);
    const http = useHttp<Record<string, never>, Payment>();
    const expired = usage.status === 'expired';

    useEffect(() => {
        setPayment(flashedPayment);
        setOpen(flashedPayment !== null);
    }, [flashedPayment]);

    useEffect(() => {
        if (!payment?.qr_string) {
            setQr(null);

            return;
        }

        void QRCode.toDataURL(payment.qr_string, {
            margin: 1,
            width: 240,
        }).then(setQr);
    }, [payment?.qr_string]);

    const check = useCallback(
        (refresh: boolean) => {
            if (!payment) {
                return;
            }

            void http
                .get(
                    PlanPaymentController.show.url(payment.public_id, {
                        query: refresh ? { refresh: 1 } : {},
                    }),
                )
                .then(setPayment)
                .catch(() =>
                    setPayment((current) =>
                        current
                            ? {
                                  ...current,
                                  notice: 'The payment status is unavailable right now.',
                              }
                            : current,
                    ),
                );
        },
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [payment?.public_id],
    );

    const settled = payment ? finalStatuses.includes(payment.status) : true;

    useEffect(() => {
        if (!open || settled) {
            return;
        }

        const timer = window.setInterval(() => check(false), 10000);

        return () => window.clearInterval(timer);
    }, [open, settled, check]);

    return (
        <>
            <Head title="Plan" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Plan"
                    description="Your plan sets how many products can be published. Drafts never count."
                />
                <Card className="gap-4 p-5 sm:p-6">
                    <div className="flex flex-wrap items-center gap-2">
                        <h2 className="text-lg font-semibold">
                            {usage.plan ?? 'No plan'}
                        </h2>
                        {expired ? (
                            <Badge variant="destructive">Ended</Badge>
                        ) : (
                            <Badge variant="success">Active</Badge>
                        )}
                    </div>
                    <p className="text-sm text-muted-foreground">
                        {usage.published} of {usage.limit} published
                        {usage.ends_at
                            ? expired
                                ? `. Ended ${formatDate(usage.ends_at)}.`
                                : `. Paid until ${formatDate(usage.ends_at)}.`
                            : usage.free
                              ? '. Free plan, no end date.'
                              : '.'}
                    </p>
                    <Progress
                        value={usage.published}
                        max={usage.limit}
                        aria-label="Published products"
                        className="max-w-md"
                    />
                    {!usage.can_publish ? (
                        <p className="text-sm text-destructive" role="alert">
                            {expired
                                ? 'Your plan has ended, so new products cannot be published. Published products stay visible. Pay for a plan to publish again.'
                                : 'Publishing is paused for this store.'}
                        </p>
                    ) : null}
                </Card>
                <h2 className="text-lg font-semibold">Paid plans</h2>
                <div className="grid gap-3 md:grid-cols-2">
                    {plans.map((plan) => (
                        <Card key={plan.id} className="gap-4 p-5">
                            <div>
                                <h3 className="font-semibold">{plan.name}</h3>
                                <p className="mt-1 text-2xl font-bold tracking-tight tabular-nums">
                                    {dollars(plan.price_cents)}
                                    <span className="text-sm font-normal text-muted-foreground">
                                        {' '}
                                        / month
                                    </span>
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    Up to {plan.product_limit} published
                                    products
                                </p>
                            </div>
                            <Form
                                {...PlanPaymentController.store.form(plan.id)}
                                options={{ preserveScroll: true }}
                            >
                                {({ processing, errors }) => (
                                    <div className="grid gap-2">
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            className="justify-self-start"
                                        >
                                            {processing && <Spinner />}
                                            Pay {dollars(plan.price_cents)}
                                        </Button>
                                        <InputError message={errors.plan} />
                                    </div>
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
                                ? `Pay ${dollars(payment.amount_cents)}`
                                : 'Payment'}
                        </DialogTitle>
                        <DialogDescription>
                            Scan the QR code with your banking app, or open the
                            payment page.
                        </DialogDescription>
                    </DialogHeader>
                    {payment ? (
                        <div className="flex flex-col items-center gap-4">
                            <p
                                role="status"
                                className="flex items-center gap-2 text-sm font-medium"
                            >
                                <Badge
                                    variant={
                                        payment.status === 'paid'
                                            ? 'default'
                                            : payment.status === 'expired' ||
                                                payment.status === 'failed'
                                              ? 'destructive'
                                              : 'secondary'
                                    }
                                >
                                    {statusLabel[payment.status] ??
                                        payment.status}
                                </Badge>
                                {payment.status === 'paid'
                                    ? 'Your plan is active.'
                                    : null}
                            </p>
                            {qr && !settled ? (
                                <img
                                    src={qr}
                                    alt="Payment QR code"
                                    className="size-60"
                                />
                            ) : null}
                            {payment.notice ? (
                                <p className="text-sm text-muted-foreground">
                                    {payment.notice}
                                </p>
                            ) : null}
                            <div className="flex flex-wrap justify-center gap-2">
                                {payment.checkout_url && !settled ? (
                                    <Button variant="outline" asChild>
                                        <a href={payment.checkout_url}>
                                            Open payment page
                                        </a>
                                    </Button>
                                ) : null}
                                {!settled ? (
                                    <Button
                                        variant="secondary"
                                        onClick={() => check(true)}
                                        disabled={http.processing}
                                    >
                                        {http.processing && <Spinner />}
                                        Refresh
                                    </Button>
                                ) : null}
                            </div>
                        </div>
                    ) : null}
                </DialogContent>
            </Dialog>
        </>
    );
}

Plan.layout = {
    breadcrumbs: [{ title: 'Plan', href: vendor.plan() }],
};
