import { Form, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';

export type CartLine = {
    id: number;
    name: string;
    quantity: number;
    price_cents: number;
};

export type CartData = {
    count: number;
    total_cents: number;
    items: CartLine[];
};

export function CartSheet({
    cart,
    storeSlug,
    authenticated,
}: {
    cart: CartData;
    storeSlug: string;
    authenticated: boolean;
}) {
    return (
        <Sheet>
            <SheetTrigger asChild>
                <Button variant="outline" className="min-h-11">
                    Cart ({cart.count})
                </Button>
            </SheetTrigger>
            <SheetContent className="flex flex-col">
                <SheetHeader>
                    <SheetTitle>Cart</SheetTitle>
                </SheetHeader>
                {cart.items.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        Add a product before sending.
                    </p>
                ) : (
                    <ul className="flex flex-1 flex-col gap-3 overflow-y-auto py-4">
                        {cart.items.map((item) => (
                            <li
                                key={item.id}
                                className="flex items-baseline justify-between gap-3 text-sm"
                            >
                                <span>
                                    {item.quantity} × {item.name}
                                </span>
                                <span className="tabular-nums">
                                    $
                                    {(
                                        (item.price_cents * item.quantity) /
                                        100
                                    ).toFixed(2)}
                                </span>
                            </li>
                        ))}
                    </ul>
                )}
                <div className="mt-auto flex flex-col gap-3 pb-[max(1rem,env(safe-area-inset-bottom))]">
                    <p className="text-lg tabular-nums">
                        ${(cart.total_cents / 100).toFixed(2)}
                    </p>
                    {authenticated ? (
                        <Form
                            action={`/s/${storeSlug}/cart/send`}
                            method="post"
                        >
                            {({ processing }) => (
                                <Button
                                    type="submit"
                                    className="min-h-11 w-full"
                                    disabled={
                                        processing || cart.items.length === 0
                                    }
                                >
                                    Send to Telegram
                                </Button>
                            )}
                        </Form>
                    ) : (
                        <Button asChild className="min-h-11">
                            <Link href={`/sign-in?next=/s/${storeSlug}`}>
                                Sign in to send
                            </Link>
                        </Button>
                    )}
                </div>
            </SheetContent>
        </Sheet>
    );
}
