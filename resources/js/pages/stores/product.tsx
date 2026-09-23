import { Head, Link } from '@inertiajs/react';
import { useTelegramTheme } from '@/components/storefront/telegram-theme';
import { Badge } from '@/components/ui/badge';

export default function Product({
    store,
    product,
    embedded,
}: {
    store: { name: string; slug: string; url: string };
    embedded: boolean;
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

    return (
        <>
            <Head title={product.name} />
            <main className="mx-auto grid w-full max-w-5xl gap-8 px-4 py-8 md:grid-cols-2 md:px-6">
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
                    <div id="purchase-actions" />
                </div>
            </main>
        </>
    );
}
