import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';

type StoreSummary = {
    name: string;
    published: number;
    limit: number | null;
};

export default function Dashboard({ store }: { store: StoreSummary | null }) {
    return (
        <>
            <Head title="Dashboard" />
            <div className="flex flex-col gap-4 p-4 md:p-6">
                <h1 className="text-2xl font-semibold tracking-tight">
                    {store ? store.name : 'Dashboard'}
                </h1>
                {store ? (
                    <>
                        <p className="text-muted-foreground">
                            {store.published} of {store.limit ?? 0} published
                        </p>
                        <Button asChild>
                            <Link href="/vendor/products">View products</Link>
                        </Button>
                    </>
                ) : (
                    <Button asChild>
                        <Link href="/start-selling">Start selling</Link>
                    </Button>
                )}
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
