import { Head } from '@inertiajs/react';

export default function Enter() {
    return (
        <>
            <Head title="Open a store" />
            <main className="mx-auto flex min-h-svh max-w-md flex-col justify-center gap-3 px-6">
                <h1 className="text-3xl font-semibold tracking-tight">
                    Open a store link from the seller
                </h1>
                <p className="text-muted-foreground">
                    This mini app opens the shop they sent you.
                </p>
            </main>
        </>
    );
}
