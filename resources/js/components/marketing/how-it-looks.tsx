import { Send, ShoppingBag } from 'lucide-react';

/**
 * The hero picture shows how Vendly works rather than describing it: a
 * product card from an example store, and the Telegram message the vendor
 * gets when a customer taps Buy.
 */
export function HowItLooks() {
    return (
        <div className="relative mx-auto w-full max-w-lg lg:max-w-none">
            <img
                src="/images/shop-counter.jpg"
                alt="Jars of loose tea and paper bags of cakes on a shop counter."
                className="aspect-[4/3] w-full rounded-3xl object-cover"
                width={1400}
                height={933}
            />
            <div className="absolute top-4 left-4 w-44 rounded-2xl border bg-card p-3 shadow-[var(--brand-navy)]/10 shadow-lg sm:top-6 sm:left-6 sm:w-52">
                <p className="text-xs text-muted-foreground">
                    Example store: Smile Tea
                </p>
                <p className="mt-1 font-semibold">Jasmine tea</p>
                <p className="text-lg font-bold tabular-nums">$2.50</p>
                <div className="mt-2 flex items-center gap-2">
                    <span className="inline-flex h-8 flex-1 items-center justify-center rounded-full bg-primary text-xs font-medium text-primary-foreground">
                        Buy
                    </span>
                    <span className="inline-flex size-8 items-center justify-center rounded-full border text-muted-foreground">
                        <ShoppingBag className="size-4" aria-hidden="true" />
                    </span>
                </div>
            </div>
            <div className="hero-message absolute right-4 -bottom-6 w-60 rounded-2xl rounded-br-md border bg-card p-3 shadow-[var(--brand-navy)]/10 shadow-lg sm:right-6 sm:w-72">
                <div className="flex items-center gap-2 text-xs text-muted-foreground">
                    <span className="flex size-6 items-center justify-center rounded-full bg-primary text-primary-foreground">
                        <Send className="size-3" aria-hidden="true" />
                    </span>
                    Telegram, just now
                </div>
                <p className="mt-2 text-sm font-semibold">
                    New request #42 from Smile Tea
                </p>
                <p className="text-sm text-muted-foreground">
                    From: Ada (@ada)
                </p>
                <p className="text-sm">1 × Jasmine tea, $2.50</p>
            </div>
        </div>
    );
}
