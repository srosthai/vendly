import { Form, Head, Link } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import { CartSheet, type CartData } from '@/components/storefront/cart-sheet';
import { useTelegramTheme } from '@/components/storefront/telegram-theme';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

export default function Product({
    store,
    product,
    embedded,
    authenticated,
    status,
    cart,
}: {
    store: { name: string; slug: string; url: string };
    embedded: boolean;
    authenticated: boolean;
    status?: string | null;
    cart: CartData;
    product: {
        id: number;
        slug: string;
        name: string;
        description: string | null;
        price_cents: number;
        sold_out: boolean;
        image: string | null;
    };
}) {
    useTelegramTheme(embedded);

    useEffect(() => {
        if (status) {
            toast.success(status);
        }
    }, [status]);

    return (
        <>
            <Head title={product.name} />
            <main className="mx-auto grid w-full max-w-5xl gap-8 px-4 py-8 pb-28 md:grid-cols-2 md:px-6 md:pb-8">
                <div className="flex aspect-[4/5] items-center justify-center bg-muted">
                    {product.image ? (
                        <img
                            src={product.image}
                            alt=""
                            className="size-full object-cover"
                        />
                    ) : (
                        <span className="text-4xl text-muted-foreground">
                            {product.name.slice(0, 1)}
                        </span>
                    )}
                </div>
                <div className="flex flex-col gap-4">
                    <Link
                        href={store.url}
                        className="text-sm text-muted-foreground"
                    >
                        {store.name}
                    </Link>
                    <h1 className="text-4xl font-semibold tracking-tight">
                        {product.name}
                    </h1>
                    <p className="text-2xl tabular-nums">
                        ${(product.price_cents / 100).toFixed(2)}
                    </p>
                    {product.sold_out ? (
                        <Badge variant="secondary">Sold out</Badge>
                    ) : null}
                    {product.description ? (
                        <p className="max-w-prose text-muted-foreground">
                            {product.description}
                        </p>
                    ) : null}
                    <div className="fixed inset-x-0 bottom-0 z-10 flex gap-2 border-t border-border bg-background p-4 pb-[max(1rem,env(safe-area-inset-bottom))] md:static md:z-auto md:border-0 md:bg-transparent md:p-0">
                        {!product.sold_out ? (
                            <>
                                <Form
                                    action={`/s/${store.slug}/products/${product.id}/cart`}
                                    method="post"
                                    className="flex-1"
                                >
                                    {({ processing }) => (
                                        <Button
                                            type="submit"
                                            variant="outline"
                                            className="min-h-11 w-full"
                                            disabled={processing}
                                        >
                                            Add to cart
                                        </Button>
                                    )}
                                </Form>
                                {authenticated ? (
                                    <Form
                                        action={`/s/${store.slug}/products/${product.id}/buy`}
                                        method="post"
                                        className="flex-1"
                                    >
                                        {({ processing }) => (
                                            <Button
                                                type="submit"
                                                className="min-h-11 w-full"
                                                disabled={processing}
                                            >
                                                Buy
                                            </Button>
                                        )}
                                    </Form>
                                ) : (
                                    <Button asChild className="min-h-11 flex-1">
                                        <Link
                                            href={`/login?next=/s/${store.slug}/p/${product.slug}`}
                                        >
                                            Buy
                                        </Link>
                                    </Button>
                                )}
                            </>
                        ) : null}
                    </div>
                    <CartSheet
                        cart={cart}
                        storeSlug={store.slug}
                        authenticated={authenticated}
                    />
                </div>
            </main>
        </>
    );
}
