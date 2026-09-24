import { Form, Head, router, useHttp } from '@inertiajs/react';
import { useCallback, useEffect, useState } from 'react';
import PlanPaymentController from '@/actions/App/Http/Controllers/Billing/PlanPaymentController';
import { BillingPeriodSwitch } from '@/components/billing-period-switch';
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
import { Skeleton } from '@/components/ui/skeleton';
import { Spinner } from '@/components/ui/spinner';
import { offersYearly, yearlySaving } from '@/lib/billing';
import type { BillingPeriod } from '@/lib/billing';
import { dollars, formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';
import vendor from '@/routes/vendor';

type Payment = {
    public_id: string;
    status: string;
    qr_string: string | null;
    qr_image: string | null;
    expires_in: number | null;
    plan_id: number;
    amount_cents: number;
    period?: BillingPeriod;
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
    period: BillingPeriod | null;
    plan_id: number | null;
};

/**
 * A ring that fills with how much of the plan is in use. The number in the
 * middle is what is left, which is what a seller plans around.
 */
function UsageRing({ used, limit }: { used: number; limit: number }) {
    const share = limit > 0 ? Math.min(1, used / limit) : 1;
    const radius = 52;
    const circumference = 2 * Math.PI * radius;
    const full = used >= limit;

    return (
        <div className="relative size-36 shrink-0">
            <svg
                viewBox="0 0 120 120"
                className="size-full -rotate-90"
                aria-hidden="true"
            >
                <circle
                    cx="60"
                    cy="60"
                    r={radius}
                    fill="none"
                    strokeWidth="10"
                    className="stroke-muted"
                />
                <circle
                    cx="60"
                    cy="60"
                    r={radius}
                    fill="none"
                    strokeWidth="10"
                    strokeLinecap="round"
                    strokeDasharray={circumference}
                    strokeDashoffset={circumference * (1 - share)}
                    className={cn(
                        'transition-[stroke-dashoffset] duration-700 ease-out motion-reduce:transition-none',
                        full ? 'stroke-warning' : 'stroke-primary',
                    )}
                />
            </svg>
            <div className="absolute inset-0 flex flex-col items-center justify-center text-center">
                <span className="text-3xl font-bold tabular-nums">
                    {Math.max(0, limit - used)}
                </span>
                <span className="text-sm text-muted-foreground">left</span>
            </div>
        </div>
    );
}

const statusLabel: Record<string, string> = {
    pending: 'Pending',
    scanned: 'Opened in banking app',
    paid: 'Paid',
    expired: 'Expired',
    failed: 'Failed',
    canceled: 'Canceled',
};

const finalStatuses = ['paid', 'expired', 'failed', 'canceled'];

/**
 * Seconds left on the QR, counted down every second from when the server
 * last said. Null when the payment has no expiry.
 */
function useSecondsLeft(payment: Payment | null, running: boolean) {
    const [deadline, setDeadline] = useState<number | null>(null);
    const [now, setNow] = useState(() => Date.now());

    useEffect(() => {
        setNow(Date.now());
        setDeadline(
            payment?.expires_in == null
                ? null
                : Date.now() + payment.expires_in * 1000,
        );
    }, [payment]);

    useEffect(() => {
        if (!running) {
            return;
        }

        const timer = window.setInterval(() => setNow(Date.now()), 1000);

        return () => window.clearInterval(timer);
    }, [running]);

    return deadline === null
        ? null
        : Math.max(0, Math.ceil((deadline - now) / 1000));
}

function clock(seconds: number): string {
    return `${Math.floor(seconds / 60)}:${String(seconds % 60).padStart(2, '0')}`;
}

/**
 * CutLuy's KHQR card, with a placeholder of the same shape while it loads.
 */
function KhqrImage({ src, label }: { src: string; label: string }) {
    const [state, setState] = useState<'loading' | 'ready' | 'error'>(
        'loading',
    );

    useEffect(() => setState('loading'), [src]);

    if (state === 'error') {
        return (
            <p className="flex aspect-[408/541] w-72 items-center justify-center rounded-2xl border border-dashed p-6 text-center text-sm text-muted-foreground">
                The QR code did not load. Check your connection, then tap Check
                payment.
            </p>
        );
    }

    return (
        <div className="relative aspect-[408/541] w-72">
            {state === 'loading' ? (
                <Skeleton className="absolute inset-3 rounded-2xl" />
            ) : null}
            <img
                src={src}
                alt={label}
                onLoad={() => setState('ready')}
                onError={() => setState('error')}
                className={cn(
                    'size-full transition-opacity duration-300',
                    state === 'ready' ? 'opacity-100' : 'opacity-0',
                )}
            />
        </div>
    );
}

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
        yearly_price_cents: number | null;
        product_limit: number;
    }[];
    payment: Payment | null;
}) {
    const [payment, setPayment] = useState<Payment | null>(flashedPayment);
    const [open, setOpen] = useState(flashedPayment !== null);
    const http = useHttp<Record<string, never>, Payment>();
    const expired = usage.status === 'expired';
    const [period, setPeriod] = useState<BillingPeriod>('monthly');
    const anyYearly = plans.some(offersYearly);
    const largestLimit = Math.max(
        0,
        ...plans.map((plan) => plan.product_limit),
    );
    const recommendedId = plans
        .filter((plan) => plan.product_limit > usage.limit)
        .sort((a, b) => a.price_cents - b.price_cents)[0]?.id;
    const bestSaving = Math.max(0, ...plans.map(yearlySaving));

    useEffect(() => {
        setPayment(flashedPayment);
        setOpen(flashedPayment !== null);
    }, [flashedPayment]);

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
    const secondsLeft = useSecondsLeft(payment, open && !settled);
    const timedOut = secondsLeft === 0;
    const active = payment !== null && !settled && !timedOut;
    const paid = payment?.status === 'paid';

    useEffect(() => {
        if (!open || settled) {
            return;
        }

        const timer = window.setInterval(() => check(false), 10000);

        return () => window.clearInterval(timer);
    }, [open, settled, check]);

    // When the time runs out, ask once so the server records it.
    useEffect(() => {
        if (open && timedOut && !settled) {
            check(false);
        }
    }, [open, timedOut, settled, check]);

    useEffect(() => {
        if (paid) {
            router.reload({ only: ['usage'] });
        }
    }, [paid]);

    const cancel = () => {
        if (!payment) {
            return;
        }

        void http
            .post(PlanPaymentController.cancel.url(payment.public_id))
            .finally(() => setOpen(false));
    };

    return (
        <>
            <Head title="Plan" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Plan"
                    description="Your plan sets how many products can be published. Drafts never count."
                />
                <Card className="gap-6 p-5 sm:flex-row sm:items-center sm:p-6">
                    <UsageRing used={usage.published} limit={usage.limit} />
                    <div className="grid min-w-0 flex-1 gap-3">
                        <div className="flex flex-wrap items-center gap-2">
                            <h2 className="text-2xl font-bold tracking-tight">
                                {usage.plan ?? 'No plan'}
                            </h2>
                            {expired ? (
                                <Badge variant="destructive">Ended</Badge>
                            ) : (
                                <Badge variant="success">Active</Badge>
                            )}
                            {usage.period ? (
                                <Badge variant="secondary">
                                    {usage.period === 'yearly'
                                        ? 'Paid yearly'
                                        : 'Paid monthly'}
                                </Badge>
                            ) : null}
                        </div>
                        <p className="text-muted-foreground">
                            <span className="font-medium text-foreground tabular-nums">
                                {usage.published} of {usage.limit}
                            </span>{' '}
                            products published.{' '}
                            {usage.ends_at
                                ? expired
                                    ? `Ended ${formatDate(usage.ends_at)}.`
                                    : `Paid until ${formatDate(usage.ends_at)}.`
                                : usage.free
                                  ? 'Free, with no end date.'
                                  : ''}
                        </p>
                        {!usage.can_publish ? (
                            <p
                                className="rounded-2xl bg-destructive/10 px-4 py-3 text-sm text-destructive"
                                role="alert"
                            >
                                {expired
                                    ? 'Your plan has ended, so new products cannot be published. Published products stay visible. Pay for a plan to publish again.'
                                    : 'Publishing is paused for this store.'}
                            </p>
                        ) : usage.published >= usage.limit ? (
                            <p className="rounded-2xl bg-warning/10 px-4 py-3 text-sm text-warning">
                                Your plan is full. Choose a bigger plan below to
                                publish more.
                            </p>
                        ) : null}
                    </div>
                </Card>

                <div className="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h2 className="text-lg font-semibold">
                            {usage.free ? 'Upgrade your plan' : 'Plans'}
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            Pay by Cambodia QR in your banking app. The plan
                            starts once the payment is complete, and paying
                            again adds time on top.
                        </p>
                    </div>
                    {anyYearly ? (
                        <BillingPeriodSwitch
                            value={period}
                            onChange={setPeriod}
                            note={
                                bestSaving > 0
                                    ? `Save up to ${dollars(bestSaving)}`
                                    : undefined
                            }
                        />
                    ) : null}
                </div>
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {plans.map((plan) => {
                        const yearly =
                            period === 'yearly' && offersYearly(plan);
                        const amount = yearly
                            ? (plan.yearly_price_cents ?? 0)
                            : plan.price_cents;
                        const saving = yearlySaving(plan);
                        const current = plan.id === usage.plan_id;
                        const recommended = plan.id === recommendedId;
                        const difference = plan.product_limit - usage.limit;
                        const share = Math.max(
                            4,
                            Math.round(
                                Math.sqrt(
                                    plan.product_limit /
                                        Math.max(largestLimit, 1),
                                ) * 100,
                            ),
                        );

                        return (
                            <Card
                                key={plan.id}
                                className={cn(
                                    'relative gap-5 p-5 sm:p-6',
                                    recommended &&
                                        'border-primary ring-4 ring-primary/10',
                                )}
                            >
                                <div className="flex items-start justify-between gap-2">
                                    <h3 className="text-lg font-semibold">
                                        {plan.name}
                                    </h3>
                                    {current ? (
                                        <Badge variant="secondary">
                                            Your plan
                                        </Badge>
                                    ) : recommended ? (
                                        <Badge>Recommended</Badge>
                                    ) : null}
                                </div>
                                <div>
                                    <p className="flex items-baseline gap-1">
                                        <span className="text-3xl font-bold tracking-tight tabular-nums">
                                            {dollars(amount)}
                                        </span>
                                        <span className="text-sm text-muted-foreground">
                                            {yearly ? 'per year' : 'per month'}
                                        </span>
                                    </p>
                                    <p className="mt-1 min-h-5 text-sm text-muted-foreground">
                                        {yearly && saving > 0
                                            ? `Saves ${dollars(saving)} against paying monthly.`
                                            : period === 'yearly' && !yearly
                                              ? 'Paid monthly only.'
                                              : ''}
                                    </p>
                                </div>
                                <div className="grid gap-2">
                                    <p className="flex items-baseline justify-between gap-2 text-sm">
                                        <span>
                                            <span className="font-semibold tabular-nums">
                                                {plan.product_limit}
                                            </span>{' '}
                                            live products
                                        </span>
                                        {!current && difference !== 0 ? (
                                            <span
                                                className={cn(
                                                    'tabular-nums',
                                                    difference > 0
                                                        ? 'text-success'
                                                        : 'text-muted-foreground',
                                                )}
                                            >
                                                {difference > 0
                                                    ? `+${difference}`
                                                    : difference}{' '}
                                                vs yours
                                            </span>
                                        ) : null}
                                    </p>
                                    <div
                                        className="h-2 overflow-hidden rounded-full bg-muted"
                                        aria-hidden="true"
                                    >
                                        <div
                                            className="h-full rounded-full bg-primary"
                                            style={{ width: `${share}%` }}
                                        />
                                    </div>
                                </div>
                                <Form
                                    {...PlanPaymentController.store.form(
                                        plan.id,
                                    )}
                                    options={{ preserveScroll: true }}
                                    className="mt-auto"
                                >
                                    {({ processing, errors }) => (
                                        <div className="grid gap-2">
                                            <input
                                                type="hidden"
                                                name="period"
                                                value={
                                                    yearly
                                                        ? 'yearly'
                                                        : 'monthly'
                                                }
                                            />
                                            <Button
                                                type="submit"
                                                disabled={processing}
                                                variant={
                                                    recommended
                                                        ? 'default'
                                                        : 'outline'
                                                }
                                                className="w-full"
                                            >
                                                {processing && <Spinner />}
                                                {current ? 'Renew' : 'Pay'}{' '}
                                                {dollars(amount)}
                                                {yearly
                                                    ? ' for a year'
                                                    : ' for a month'}
                                            </Button>
                                            <InputError message={errors.plan} />
                                        </div>
                                    )}
                                </Form>
                            </Card>
                        );
                    })}
                </div>
            </div>
            <Dialog
                open={open}
                onOpenChange={(next) => {
                    if (next || !active) {
                        setOpen(next);
                    }
                }}
            >
                <DialogContent
                    showCloseButton={!active}
                    onEscapeKeyDown={(event) =>
                        active && event.preventDefault()
                    }
                    onInteractOutside={(event) =>
                        active && event.preventDefault()
                    }
                    className="sm:max-w-sm"
                >
                    <DialogHeader>
                        <DialogTitle>
                            {payment
                                ? `Pay ${dollars(payment.amount_cents)}${payment.period === 'yearly' ? ' for a year' : payment.period === 'monthly' ? ' for a month' : ''}`
                                : 'Payment'}
                        </DialogTitle>
                        <DialogDescription>
                            {paid
                                ? 'Thank you. Your plan is active.'
                                : active
                                  ? 'Scan with any banking app that supports KHQR. This updates on its own once you pay.'
                                  : 'This QR code can no longer be paid.'}
                        </DialogDescription>
                    </DialogHeader>
                    {payment ? (
                        <div className="flex flex-col items-center gap-4">
                            {active && payment.qr_image ? (
                                <KhqrImage
                                    src={payment.qr_image}
                                    label={`KHQR code to pay ${dollars(payment.amount_cents)}`}
                                />
                            ) : null}
                            <p
                                role="status"
                                className="flex flex-wrap items-center justify-center gap-2 text-sm font-medium"
                            >
                                <Badge
                                    variant={
                                        paid
                                            ? 'default'
                                            : active
                                              ? 'secondary'
                                              : 'destructive'
                                    }
                                >
                                    {timedOut && !settled
                                        ? 'Expired'
                                        : (statusLabel[payment.status] ??
                                          payment.status)}
                                </Badge>
                                {active && secondsLeft !== null ? (
                                    <span
                                        className={cn(
                                            'tabular-nums',
                                            secondsLeft <= 60
                                                ? 'text-destructive'
                                                : 'text-muted-foreground',
                                        )}
                                    >
                                        Expires in {clock(secondsLeft)}
                                    </span>
                                ) : null}
                            </p>
                            {payment.notice ? (
                                <p className="text-center text-sm text-muted-foreground">
                                    {payment.notice}
                                </p>
                            ) : null}
                            <div className="flex w-full flex-wrap justify-center gap-2">
                                {active ? (
                                    <>
                                        <Button
                                            variant="outline"
                                            onClick={cancel}
                                            disabled={http.processing}
                                        >
                                            Cancel
                                        </Button>
                                        <Button
                                            variant="secondary"
                                            onClick={() => check(true)}
                                            disabled={http.processing}
                                        >
                                            {http.processing && <Spinner />}
                                            Check payment
                                        </Button>
                                    </>
                                ) : paid ? (
                                    <Button onClick={() => setOpen(false)}>
                                        Done
                                    </Button>
                                ) : (
                                    <>
                                        <Button
                                            variant="outline"
                                            onClick={() => setOpen(false)}
                                        >
                                            Close
                                        </Button>
                                        {payment.period ? (
                                            <Form
                                                {...PlanPaymentController.store.form(
                                                    payment.plan_id,
                                                )}
                                                options={{
                                                    preserveScroll: true,
                                                }}
                                            >
                                                {({ processing, errors }) => (
                                                    <div className="grid gap-2">
                                                        <input
                                                            type="hidden"
                                                            name="period"
                                                            value={
                                                                payment.period
                                                            }
                                                        />
                                                        <Button
                                                            type="submit"
                                                            disabled={
                                                                processing
                                                            }
                                                        >
                                                            {processing && (
                                                                <Spinner />
                                                            )}
                                                            New QR code
                                                        </Button>
                                                        <InputError
                                                            message={
                                                                errors.plan
                                                            }
                                                        />
                                                    </div>
                                                )}
                                            </Form>
                                        ) : null}
                                    </>
                                )}
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
