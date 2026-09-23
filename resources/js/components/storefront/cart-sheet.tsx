import { Form, Link, router } from '@inertiajs/react';
import { Minus, Plus, ShoppingBag, Trash2 } from 'lucide-react';
import CartController from '@/actions/App/Http/Controllers/CartController';
import InquiryController from '@/actions/App/Http/Controllers/InquiryController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';

export type CartLine = {
    id: number;
    name: string;
    quantity: number;
    price_cents: number;
    image: string | null;
};

export type CartData = {
    count: number;
    total_cents: number;
    items: CartLine[];
};

const maxQuantity = 99;

function dollars(cents: number): string {
    return `$${(cents / 100).toFixed(2)}`;
}

/**
 * The cart after one line changes, so the sheet updates before the server
 * answers. Inertia rolls it back if the request fails.
 */
function withLine(cart: CartData, id: number, quantity: number): CartData {
    const items = cart.items
        .map((item) => (item.id === id ? { ...item, quantity } : item))
        .filter((item) => item.quantity > 0);

    return {
        items,
        count: items.reduce((sum, item) => sum + item.quantity, 0),
        total_cents: items.reduce(
            (sum, item) => sum + item.price_cents * item.quantity,
            0,
        ),
    };
}

function changeQuantity(
    storeSlug: string,
    line: CartLine,
    quantity: number,
): void {
    const args = { store: storeSlug, product: line.id };
    const options = { preserveScroll: true, preserveState: true };
    const optimistic = router.optimistic<{ cart?: CartData }>((props) =>
        props.cart ? { cart: withLine(props.cart, line.id, quantity) } : {},
    );

    if (quantity === 0) {
        optimistic.delete(CartController.destroy.url(args), options);

        return;
    }

    optimistic.patch(CartController.update.url(args), { quantity }, options);
}

function CartLineItem({
    line,
    storeSlug,
}: {
    line: CartLine;
    storeSlug: string;
}) {
    return (
        <li className="flex gap-3">
            <div className="size-16 shrink-0 overflow-hidden rounded-md bg-muted">
                {line.image ? (
                    <img
                        src={line.image}
                        alt={line.name}
                        className="size-full object-cover"
                    />
                ) : null}
            </div>
            <div className="flex min-w-0 flex-1 flex-col gap-2">
                <div className="flex items-baseline justify-between gap-3">
                    <p className="truncate font-medium">{line.name}</p>
                    <p className="tabular-nums">
                        {dollars(line.price_cents * line.quantity)}
                    </p>
                </div>
                <p className="text-sm text-muted-foreground tabular-nums">
                    {dollars(line.price_cents)} each
                </p>
                <div className="flex items-center justify-between gap-2">
                    <div className="flex items-center gap-1">
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            className="size-11"
                            aria-label={`One less ${line.name}`}
                            disabled={line.quantity <= 1}
                            onClick={() =>
                                changeQuantity(
                                    storeSlug,
                                    line,
                                    line.quantity - 1,
                                )
                            }
                        >
                            <Minus />
                        </Button>
                        <span
                            className="w-8 text-center tabular-nums"
                            aria-label={`Quantity of ${line.name}`}
                        >
                            {line.quantity}
                        </span>
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            className="size-11"
                            aria-label={`One more ${line.name}`}
                            disabled={line.quantity >= maxQuantity}
                            onClick={() =>
                                changeQuantity(
                                    storeSlug,
                                    line,
                                    line.quantity + 1,
                                )
                            }
                        >
                            <Plus />
                        </Button>
                    </div>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="size-11 text-muted-foreground"
                        aria-label={`Remove ${line.name}`}
                        onClick={() => changeQuantity(storeSlug, line, 0)}
                    >
                        <Trash2 />
                    </Button>
                </div>
            </div>
        </li>
    );
}

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
                <Button variant="outline" className="relative min-h-11">
                    <ShoppingBag />
                    Cart
                    {cart.count > 0 ? (
                        <span className="ml-1 rounded-full bg-primary px-2 py-0.5 text-sm text-primary-foreground tabular-nums">
                            {cart.count}
                        </span>
                    ) : null}
                </Button>
            </SheetTrigger>
            <SheetContent className="flex flex-col gap-0">
                <SheetHeader>
                    <SheetTitle>Cart</SheetTitle>
                    <SheetDescription>
                        Send your cart to the store on Telegram. You pay the
                        store directly.
                    </SheetDescription>
                </SheetHeader>
                {cart.items.length === 0 ? (
                    <p className="flex-1 px-4 text-sm text-muted-foreground">
                        Your cart is empty. Add a product to send it to the
                        store.
                    </p>
                ) : (
                    <ul className="flex flex-1 flex-col gap-4 overflow-y-auto px-4 py-2">
                        {cart.items.map((line) => (
                            <CartLineItem
                                key={line.id}
                                line={line}
                                storeSlug={storeSlug}
                            />
                        ))}
                    </ul>
                )}
                <div className="flex flex-col gap-3 border-t p-4 pb-[max(1rem,env(safe-area-inset-bottom))]">
                    <div className="flex items-baseline justify-between">
                        <span className="text-sm text-muted-foreground">
                            Total
                        </span>
                        <span className="text-lg font-medium tabular-nums">
                            {dollars(cart.total_cents)}
                        </span>
                    </div>
                    {authenticated ? (
                        <Form
                            {...InquiryController.cart.form(storeSlug)}
                            options={{ preserveScroll: true }}
                        >
                            {({ processing, errors }) => (
                                <div className="grid gap-2">
                                    <Button
                                        type="submit"
                                        className="min-h-11 w-full"
                                        disabled={
                                            processing ||
                                            cart.items.length === 0
                                        }
                                    >
                                        {processing && <Spinner />}
                                        Send to Telegram
                                    </Button>
                                    <InputError message={errors.cart} />
                                </div>
                            )}
                        </Form>
                    ) : (
                        <Button asChild className="min-h-11">
                            <Link
                                href={login({
                                    query: { next: `/s/${storeSlug}` },
                                })}
                            >
                                Log in to send
                            </Link>
                        </Button>
                    )}
                </div>
            </SheetContent>
        </Sheet>
    );
}
