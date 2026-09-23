import { Head } from '@inertiajs/react';
import { Link2 } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { useTelegramMiniApp } from '@/components/storefront/telegram-mini-app';

/**
 * The mini app opened without a store. Vendly has no public directory, so
 * the customer needs the link a seller sent them.
 */
export default function Enter() {
    useTelegramMiniApp(true);

    return (
        <>
            <Head title="Open a store" />
            <main className="mx-auto flex min-h-dvh max-w-md flex-col items-center justify-center gap-6 px-6 text-center">
                <div className="flex items-center gap-1">
                    <AppLogo />
                </div>
                <div className="flex w-full flex-col items-center gap-4 rounded-3xl border bg-card p-8">
                    <span className="flex size-14 items-center justify-center rounded-full bg-secondary text-secondary-foreground">
                        <Link2 className="size-6" aria-hidden="true" />
                    </span>
                    <h1 className="text-2xl font-bold tracking-tight">
                        Open a store link from the seller
                    </h1>
                    <p className="text-muted-foreground">
                        This mini app opens the shop a seller shared with you.
                        Tap their store link in Telegram to start browsing.
                    </p>
                </div>
            </main>
        </>
    );
}
