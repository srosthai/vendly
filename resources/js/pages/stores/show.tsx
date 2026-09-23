import { Head } from '@inertiajs/react';

type Product = {
    id: number;
    name: string;
    price_cents: number;
    url: string;
};

export default function Show({
    store,
    products,
}: {
    store: { name: string; description: string | null };
    products: Product[];
}) {
    return (
        <>
            <Head title={store.name} />
            <main className="mx-auto flex max-w-3xl flex-col gap-6 p-6">
                <header>
                    <h1 className="text-3xl font-semibold tracking-tight">
                        {store.name}
                    </h1>
                    {store.description ? (
                        <p className="mt-2 text-muted-foreground">
                            {store.description}
                        </p>
                    ) : null}
                </header>
                {products.length === 0 ? (
                    <p>No products yet</p>
                ) : (
                    <ul className="flex flex-col gap-3">
                        {products.map((product) => (
                            <li key={product.id}>
                                <a
                                    href={product.url}
                                    className="flex items-baseline justify-between gap-4"
                                >
                                    <span>{product.name}</span>
                                    <span>
                                        $
                                        {(product.price_cents / 100).toFixed(2)}
                                    </span>
                                </a>
                            </li>
                        ))}
                    </ul>
                )}
            </main>
        </>
    );
}
