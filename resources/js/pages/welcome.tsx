import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

const slip = [
    { name: 'Jasmine tea', price: '$2.50' },
    { name: 'Sesame cake', price: '$1.00' },
    { name: 'Cold brew', price: '$3.00' },
];

export default function Welcome() {
    return (
        <>
            <Head title="Vendly" />
            <div className="min-h-svh bg-background text-foreground">
                <header className="mx-auto flex w-full max-w-5xl items-center justify-between px-6 py-6">
                    <span className="text-sm font-medium tracking-tight">
                        Vendly
                    </span>
                    <Button variant="ghost" asChild>
                        <Link href="/login">Sign in</Link>
                    </Button>
                </header>
                <main className="mx-auto grid w-full max-w-5xl gap-16 px-6 pt-10 pb-24 lg:grid-cols-[minmax(0,1.1fr)_minmax(16rem,22rem)] lg:items-end">
                    <div className="max-w-xl">
                        <h1 className="text-5xl leading-[0.95] font-semibold tracking-tight text-balance sm:text-6xl">
                            Open a shop. Send the link.
                        </h1>
                        <p className="mt-6 max-w-[36ch] text-lg leading-relaxed text-muted-foreground">
                            Vendors list what they sell. Customers send a
                            request on Telegram, from the web or from the mini
                            app.
                        </p>
                        <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                            <Button asChild size="lg">
                                <Link href="/register?next=sell">
                                    Start selling
                                </Link>
                            </Button>
                            <Button asChild size="lg" variant="outline">
                                <Link href="/login">Sign in</Link>
                            </Button>
                        </div>
                    </div>
                    <aside className="border border-border bg-card p-6">
                        <p className="text-sm text-muted-foreground">
                            Smile Tea
                        </p>
                        <ul className="mt-6 space-y-3">
                            {slip.map((line) => (
                                <li
                                    key={line.name}
                                    className="flex items-baseline justify-between gap-4 text-sm"
                                >
                                    <span>{line.name}</span>
                                    <span className="tabular-nums">
                                        {line.price}
                                    </span>
                                </li>
                            ))}
                        </ul>
                        <p className="mt-6 border-t border-border pt-4 text-sm">
                            Sent to the store on Telegram
                        </p>
                    </aside>
                </main>
            </div>
        </>
    );
}
