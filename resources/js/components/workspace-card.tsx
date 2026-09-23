import { Link, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Progress } from '@/components/ui/progress';
import admin from '@/routes/admin';
import selling from '@/routes/selling';
import vendor from '@/routes/vendor';

/**
 * The card at the foot of the sidebar. It always carries something the
 * person can act on: plan room for a vendor, undelivered requests for an
 * admin, and opening a store for a customer.
 */
export function WorkspaceCard() {
    const { workspace } = usePage().props;

    if (workspace === null) {
        return (
            <div className="flex flex-col gap-3 rounded-2xl bg-secondary p-4">
                <p className="text-sm font-medium text-secondary-foreground">
                    Sell on the web and in Telegram
                </p>
                <p className="text-sm text-muted-foreground">
                    Open a store in a minute. The free plan needs no payment.
                </p>
                <Button asChild size="sm" className="self-start">
                    <Link href={selling.create()}>Start selling</Link>
                </Button>
            </div>
        );
    }

    if (workspace.kind === 'admin') {
        return (
            <div className="flex flex-col gap-3 rounded-2xl bg-secondary p-4">
                <p className="text-sm font-medium text-secondary-foreground">
                    {workspace.undelivered === 0
                        ? 'Every request reached Telegram'
                        : `${workspace.undelivered} ${workspace.undelivered === 1 ? 'request has' : 'requests have'} not reached the admin chat`}
                </p>
                {workspace.undelivered > 0 ? (
                    <Button asChild size="sm" className="self-start">
                        <Link
                            href={admin.requests({
                                query: { filter: 'undelivered' },
                            })}
                        >
                            Review requests
                        </Link>
                    </Button>
                ) : null}
            </div>
        );
    }

    const left = Math.max(0, workspace.limit - workspace.published);

    return (
        <div className="flex flex-col gap-3 rounded-2xl bg-[var(--brand-navy)] p-4 text-white dark:bg-secondary dark:text-secondary-foreground">
            <div>
                <p className="text-sm font-medium">
                    {workspace.plan ?? 'Plan'}
                </p>
                <p className="text-sm text-white/75 dark:text-muted-foreground">
                    {workspace.published} of {workspace.limit} published
                </p>
            </div>
            <Progress
                value={workspace.published}
                max={workspace.limit}
                aria-label="Published products"
                className="bg-white/15 dark:bg-muted"
                indicatorClassName="bg-highlight"
            />
            <p className="text-sm text-white/75 dark:text-muted-foreground">
                {!workspace.can_publish
                    ? 'Your plan has ended. Renew it to publish again.'
                    : left === 0
                      ? 'Your plan is full. Upgrade to publish more.'
                      : `${left} more ${left === 1 ? 'product' : 'products'} can go live.`}
            </p>
            <Button
                asChild
                size="sm"
                variant="highlight"
                className="self-start"
            >
                <Link href={vendor.plan()}>
                    {workspace.free ? 'Upgrade plan' : 'Manage plan'}
                </Link>
            </Button>
        </div>
    );
}
