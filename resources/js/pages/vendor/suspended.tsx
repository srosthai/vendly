import { Head } from '@inertiajs/react';

export default function Suspended({ store }: { store: { name: string } }) {
    return (
        <>
            <Head title="Store suspended" />
            <div className="flex max-w-lg flex-col gap-4 p-4 md:p-6">
                <h1 className="text-2xl font-semibold tracking-tight">
                    {store.name} is suspended
                </h1>
                <p className="text-sm text-muted-foreground">
                    Your store is hidden from customers and cannot be changed.
                    Contact the Vendly admin to have it restored.
                </p>
            </div>
        </>
    );
}
