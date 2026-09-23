import {
    Check,
    ChevronLeft,
    Copy,
    MoreVertical,
    Send,
    ShoppingBag,
} from 'lucide-react';
import { cn } from '@/lib/utils';

/*
 * Small, real pieces of the Vendly product, drawn with the app's own
 * components and tokens. The website shows these instead of generic icons,
 * so every picture is something a seller or customer will actually see.
 */

const photo = '/images/shop-counter.jpg';

const exampleProducts = [
    { name: 'Jasmine tea', price: '$2.50', position: '18% 60%' },
    { name: 'Green tea', price: '$3.00', position: '42% 45%' },
    { name: 'Honey cake', price: '$1.80', position: '78% 62%' },
    { name: 'Oolong', price: '$4.20', position: '60% 40%' },
];

/**
 * The Vendly mini app open inside Telegram on a phone, with an example store.
 */
export function PhoneMockup({ className }: { className?: string }) {
    return (
        <div
            className={cn(
                'relative mx-auto w-[280px] rounded-[2.75rem] bg-[#0f0f10] p-2.5 shadow-2xl ring-1 shadow-black/25 ring-black/10 sm:w-[300px] dark:ring-white/10',
                className,
            )}
            role="img"
            aria-label="The Vendly mini app inside Telegram, showing the example store Smile Tea with four products and a cart ready to send."
        >
            <div className="relative overflow-hidden rounded-[2.25rem] bg-background">
                <div
                    className="absolute top-2 left-1/2 z-10 h-6 w-24 -translate-x-1/2 rounded-full bg-[#0f0f10]"
                    aria-hidden="true"
                />
                <div
                    className="flex items-center justify-between bg-card px-4 pt-10 pb-3"
                    aria-hidden="true"
                >
                    <span className="flex items-center gap-1 text-sm font-medium text-primary">
                        <ChevronLeft className="size-4" />
                        Close
                    </span>
                    <span className="text-center leading-tight">
                        <span className="block text-sm font-semibold">
                            Smile Tea
                        </span>
                        <span className="block text-sm text-muted-foreground">
                            mini app
                        </span>
                    </span>
                    <MoreVertical className="size-4 text-muted-foreground" />
                </div>
                <div className="flex flex-col gap-3 p-3" aria-hidden="true">
                    <div className="flex items-center gap-3 rounded-2xl border bg-card p-3">
                        <span className="flex size-10 items-center justify-center rounded-xl bg-secondary font-bold text-secondary-foreground">
                            S
                        </span>
                        <span className="min-w-0">
                            <span className="block font-semibold">
                                Smile Tea
                            </span>
                            <span className="block truncate text-sm text-muted-foreground">
                                Tea and small cakes
                            </span>
                        </span>
                    </div>
                    <div className="flex gap-1.5 overflow-hidden">
                        {['All', 'Green tea', 'Cakes', 'Black'].map(
                            (category, index) => (
                                <span
                                    key={category}
                                    className={cn(
                                        'shrink-0 rounded-full border px-3 py-1 text-sm',
                                        index === 0
                                            ? 'border-primary bg-primary text-primary-foreground'
                                            : 'bg-card text-muted-foreground',
                                    )}
                                >
                                    {category}
                                </span>
                            ),
                        )}
                    </div>
                    <div className="grid grid-cols-2 gap-2">
                        {exampleProducts.map((product) => (
                            <div
                                key={product.name}
                                className="overflow-hidden rounded-xl border bg-card"
                            >
                                <div
                                    className="aspect-square bg-cover"
                                    style={{
                                        backgroundImage: `url(${photo})`,
                                        backgroundPosition: product.position,
                                        backgroundSize: '320%',
                                    }}
                                />
                                <div className="p-2">
                                    <span className="block truncate text-sm font-medium">
                                        {product.name}
                                    </span>
                                    <span className="block text-sm font-bold">
                                        {product.price}
                                    </span>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
                <div
                    className="flex items-center gap-2 border-t bg-card p-3"
                    aria-hidden="true"
                >
                    <span className="flex size-10 items-center justify-center rounded-full border">
                        <ShoppingBag className="size-4" />
                    </span>
                    <span className="flex h-10 flex-1 items-center justify-center gap-2 rounded-full bg-primary text-sm font-medium text-primary-foreground">
                        <Send className="size-4" />
                        Send 2 items, $5.50
                    </span>
                </div>
            </div>
        </div>
    );
}

/**
 * The message a seller receives in Telegram.
 */
export function RequestMessage({ className }: { className?: string }) {
    return (
        <div
            className={cn(
                'w-64 rounded-2xl rounded-bl-md border bg-card p-3 shadow-lg shadow-black/10',
                className,
            )}
        >
            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                <span className="flex size-6 items-center justify-center rounded-full bg-primary text-primary-foreground">
                    <Send className="size-3" aria-hidden="true" />
                </span>
                Vendly bot, just now
            </div>
            <p className="mt-2 text-sm font-semibold">
                New request #42 from Smile Tea
            </p>
            <p className="text-sm text-muted-foreground">From: Ada (@ada)</p>
            <p className="text-sm">1 × Jasmine tea, $2.50</p>
            <p className="text-sm">1 × Green tea, $3.00</p>
            <p className="mt-1 text-sm font-semibold">Total $5.50</p>
        </div>
    );
}

export function ShareLinkFragment() {
    return (
        <div className="grid gap-2" aria-hidden="true">
            {[
                ['Web', '/s/smile-tea'],
                ['Telegram', 't.me/…?startapp=smile-tea'],
            ].map(([label, link]) => (
                <div
                    key={label}
                    className="flex items-center gap-2 rounded-xl border bg-background p-2 pl-3"
                >
                    <span className="w-16 shrink-0 text-sm text-muted-foreground">
                        {label}
                    </span>
                    <span className="min-w-0 flex-1 truncate text-sm font-medium">
                        {link}
                    </span>
                    <span className="flex size-8 items-center justify-center rounded-full border">
                        <Copy className="size-3.5" />
                    </span>
                </div>
            ))}
        </div>
    );
}

export function PublishFragment() {
    return (
        <div className="grid gap-2" aria-hidden="true">
            {[
                ['Jasmine tea', 'Published'],
                ['Honey cake', 'Draft'],
                ['Oolong', 'Sold out'],
            ].map(([name, status]) => (
                <div
                    key={name}
                    className="flex items-center justify-between gap-3 rounded-xl border bg-background px-3 py-2"
                >
                    <span className="text-sm font-medium">{name}</span>
                    <span
                        className={cn(
                            'rounded-full px-2.5 py-0.5 text-sm',
                            status === 'Published' &&
                                'bg-success/12 text-success',
                            status === 'Draft' &&
                                'border bg-card text-muted-foreground',
                            status === 'Sold out' &&
                                'bg-warning/12 text-warning',
                        )}
                    >
                        {status}
                    </span>
                </div>
            ))}
        </div>
    );
}

export function PlanUsageFragment() {
    return (
        <div
            className="grid gap-3 rounded-xl border bg-background p-4"
            aria-hidden="true"
        >
            <div className="flex items-baseline justify-between">
                <span className="text-sm font-semibold">Starter</span>
                <span className="text-sm text-muted-foreground">
                    72 of 100 published
                </span>
            </div>
            <div className="h-2 overflow-hidden rounded-full bg-muted">
                <div className="h-full w-[72%] rounded-full bg-primary" />
            </div>
            <span className="flex items-center gap-1.5 text-sm text-success">
                <Check className="size-4" />
                Paid by QR, active until next month
            </span>
        </div>
    );
}
