import { Head } from '@inertiajs/react';

export default function Product({
    store,
    product,
}: {
    store: { name: string; slug: string };
    product: {
        name: string;
        description: string | null;
        price_cents: number;
        sold_out: boolean;
    };
}) {
    return (
        <>
            <Head title={product.name} />
            <main className="mx-auto flex max-w-3xl flex-col gap-4 p-6">
                <p className="text-sm text-muted-foreground">{store.name}</p>
                <h1 className="text-3xl font-semibold tracking-tight">
                    {product.name}
                </h1>
                <p className="text-xl">
                    ${(product.price_cents / 100).toFixed(2)}
                </p>
                {product.sold_out ? <p>Sold out</p> : null}
                {product.description ? <p>{product.description}</p> : null}
            </main>
        </>
    );
}
