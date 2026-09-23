import { Head } from '@inertiajs/react';
import { ShieldAlert } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';

export default function Suspended({ store }: { store: { name: string } }) {
    return (
        <>
            <Head title="Store suspended" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <EmptyState
                    icon={ShieldAlert}
                    title={`${store.name} is suspended`}
                    description="Your store is hidden from customers and cannot be changed. Contact the Vendly admin to have it restored."
                />
            </div>
        </>
    );
}
