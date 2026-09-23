import { Head } from '@inertiajs/react';
import { MessageSquare } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatDateTime } from '@/lib/format';
import { dashboard } from '@/routes';

type SentRequest = {
    id: number;
    reference: string;
    store: string;
    store_url: string | null;
    lines: number;
    total: string;
    sent_at: string | null;
};

/**
 * A customer's home: the requests they sent. The sidebar card offers
 * opening a store.
 */
export default function Dashboard({ requests }: { requests: SentRequest[] }) {
    return (
        <>
            <Head title="Dashboard" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Your requests"
                    description="Products you sent to stores on Telegram. The store replies to you there."
                />
                <div className="max-w-3xl">
                    <Card className="gap-0 overflow-hidden p-0">
                        <CardHeader className="border-b px-5 py-4">
                            <CardTitle>Sent</CardTitle>
                            <CardDescription>Your latest five.</CardDescription>
                        </CardHeader>
                        {requests.length === 0 ? (
                            <EmptyState
                                bare
                                icon={MessageSquare}
                                title="Nothing sent yet"
                                description="Open a store link from a seller, then tap Buy or send your cart."
                            />
                        ) : (
                            <ul className="divide-y">
                                {requests.map((request) => (
                                    <li
                                        key={request.id}
                                        className="flex flex-wrap items-center justify-between gap-3 px-5 py-4"
                                    >
                                        <div className="min-w-0">
                                            <p className="font-medium">
                                                {request.store_url ? (
                                                    <a
                                                        href={request.store_url}
                                                        className="hover:underline"
                                                    >
                                                        {request.store}
                                                    </a>
                                                ) : (
                                                    request.store
                                                )}{' '}
                                                <span className="text-muted-foreground">
                                                    {request.reference}
                                                </span>
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {request.lines}{' '}
                                                {request.lines === 1
                                                    ? 'product'
                                                    : 'products'}{' '}
                                                ·{' '}
                                                {formatDateTime(
                                                    request.sent_at,
                                                )}
                                            </p>
                                        </div>
                                        <p className="font-medium tabular-nums">
                                            {request.total}
                                        </p>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Card>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
};
