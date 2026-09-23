import { Head, InfiniteScroll, Link } from '@inertiajs/react';
import { CartSheet, type CartData } from '@/components/storefront/cart-sheet';
import {
    TelegramSignInNotice,
    useTelegramMiniApp,
} from '@/components/storefront/telegram-mini-app';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';

type ProductCard = {
    id: number;
    name: string;
    price_cents: number;
    sold_out: boolean;
    image: string | null;
    url: string;
};

type Category = { name: string; slug: string };

export default function Show({
    store,
    categories,
    activeCategory,
    products,
    authenticated,
    cart,
}: {
    store: { name: string; slug: string; description: string | null };
    categories: Category[];
    activeCategory: string;
    products: { data: ProductCard[] };
    embedded: boolean;
    authenticated: boolean;
    cart: CartData;
}) {
    const miniApp = useTelegramMiniApp(authenticated);

    return (
        <>
            <Head title={store.name} />
            <main className="mx-auto flex w-full max-w-5xl flex-col gap-8 px-4 py-8 md:px-6">
                <TelegramSignInNotice {...miniApp} />
                <header className="flex items-start justify-between gap-4">
                    <div className="max-w-xl">
                        <h1 className="text-4xl font-semibold tracking-tight">
                            {store.name}
                        </h1>
                        {store.description ? (
                            <p className="mt-3 text-muted-foreground">
                                {store.description}
                            </p>
                        ) : null}
                    </div>
                    <CartSheet
                        cart={cart}
                        storeSlug={store.slug}
                        authenticated={authenticated}
                    />
                </header>
                {categories.length > 0 ? (
                    <div className="flex gap-2 overflow-x-auto pb-1">
                        <CategoryLink
                            slug=""
                            active={activeCategory === ''}
                            storeSlug={store.slug}
                        >
                            All
                        </CategoryLink>
                        {categories.map((category) => (
                            <CategoryLink
                                key={category.slug}
                                slug={category.slug}
                                active={activeCategory === category.slug}
                                storeSlug={store.slug}
                            >
                                {category.name}
                            </CategoryLink>
                        ))}
                    </div>
                ) : null}
                {products.data.length === 0 ? (
                    <p className="text-muted-foreground">
                        {activeCategory === ''
                            ? 'No products yet. Check back soon.'
                            : 'Nothing in this category yet.'}
                    </p>
                ) : (
                    <InfiniteScroll
                        data="products"
                        buffer={400}
                        loading={<ProductGridSkeleton />}
                    >
                        <ul className="grid grid-cols-2 gap-4 md:grid-cols-3">
                            {products.data.map((product) => (
                                <li key={product.id}>
                                    <Link
                                        href={product.url}
                                        className="flex h-full flex-col gap-3"
                                    >
                                        <span className="flex aspect-[4/5] items-center justify-center overflow-hidden bg-muted">
                                            {product.image ? (
                                                <img
                                                    src={product.image}
                                                    alt=""
                                                    className="size-full object-cover"
                                                />
                                            ) : (
                                                <span className="text-2xl text-muted-foreground">
                                                    {product.name.slice(0, 1)}
                                                </span>
                                            )}
                                        </span>
                                        <span className="flex items-baseline justify-between gap-2">
                                            <span className="font-medium">
                                                {product.name}
                                            </span>
                                            <span className="text-lg tabular-nums">
                                                $
                                                {(
                                                    product.price_cents / 100
                                                ).toFixed(2)}
                                            </span>
                                        </span>
                                        {product.sold_out ? (
                                            <Badge variant="secondary">
                                                Sold out
                                            </Badge>
                                        ) : null}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </InfiniteScroll>
                )}
            </main>
        </>
    );
}

function ProductGridSkeleton() {
    return (
        <ul
            className="mt-4 grid grid-cols-2 gap-4 md:grid-cols-3"
            aria-hidden="true"
        >
            {Array.from({ length: 3 }, (_, index) => (
                <li key={index} className="flex flex-col gap-3">
                    <Skeleton className="aspect-[4/5] w-full" />
                    <Skeleton className="h-5 w-2/3" />
                </li>
            ))}
        </ul>
    );
}

function CategoryLink({
    slug,
    active,
    storeSlug,
    children,
}: {
    slug: string;
    active: boolean;
    storeSlug: string;
    children: string;
}) {
    const href =
        slug === '' ? `/s/${storeSlug}` : `/s/${storeSlug}?category=${slug}`;

    return (
        <Link
            href={href}
            className={
                active
                    ? 'inline-flex min-h-11 shrink-0 items-center border border-foreground px-3 text-sm'
                    : 'inline-flex min-h-11 shrink-0 items-center border border-border px-3 text-sm text-muted-foreground'
            }
        >
            {children}
        </Link>
    );
}
