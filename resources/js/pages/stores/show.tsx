import { Head, InfiniteScroll, Link } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import { Package, ShoppingBag } from 'lucide-react';
import StoreController from '@/actions/App/Http/Controllers/StoreController';
import { EmptyState } from '@/components/empty-state';
import { CartSheet, type CartData } from '@/components/storefront/cart-sheet';
import { StoreHeader } from '@/components/storefront/store-header';
import { StorefrontFooter } from '@/components/storefront/storefront-footer';
import {
    TelegramSignInNotice,
    useTelegramMiniApp,
} from '@/components/storefront/telegram-mini-app';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { dollars } from '@/lib/format';
import { cn } from '@/lib/utils';

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
    embedded,
    authenticated,
    cart,
    status,
}: {
    status?: string | null;
    store: {
        name: string;
        slug: string;
        description: string | null;
        logo: string | null;
    };
    categories: Category[];
    activeCategory: string;
    products: { data: ProductCard[] };
    embedded: boolean;
    authenticated: boolean;
    cart: CartData;
}) {
    const miniApp = useTelegramMiniApp(authenticated);

    useEffect(() => {
        if (!status) {
            return;
        }

        if (status.startsWith('Too many')) {
            toast.error(status);
        } else {
            toast.success(status);
        }
    }, [status]);

    return (
        <>
            <Head title={store.name}>
                {store.description ? (
                    <meta name="description" content={store.description} />
                ) : null}
            </Head>
            <main className="mx-auto flex min-h-dvh w-full max-w-5xl flex-col gap-6 px-4 py-4 sm:py-6 md:px-6">
                <TelegramSignInNotice {...miniApp} />
                <StoreHeader
                    store={store}
                    cart={
                        <CartSheet
                            cart={cart}
                            storeSlug={store.slug}
                            authenticated={authenticated}
                        />
                    }
                />
                {categories.length > 0 ? (
                    <nav
                        aria-label="Categories"
                        className="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 md:mx-0 md:px-0"
                    >
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
                    </nav>
                ) : null}
                {products.data.length === 0 ? (
                    <EmptyState
                        icon={ShoppingBag}
                        title={
                            activeCategory === ''
                                ? 'No products yet'
                                : 'Nothing in this category yet'
                        }
                        description={
                            activeCategory === ''
                                ? 'This store is getting ready. Check back soon.'
                                : 'Try another category, or see everything in the store.'
                        }
                    />
                ) : (
                    <InfiniteScroll
                        data="products"
                        buffer={400}
                        loading={<ProductGridSkeleton />}
                    >
                        <ul className="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:grid-cols-4">
                            {products.data.map((product) => (
                                <li key={product.id}>
                                    <ProductTile product={product} />
                                </li>
                            ))}
                        </ul>
                    </InfiniteScroll>
                )}
                <StorefrontFooter hidden={embedded || miniApp.inTelegram} />
            </main>
        </>
    );
}

function ProductTile({ product }: { product: ProductCard }) {
    return (
        <Link
            href={product.url}
            className="group flex h-full flex-col overflow-hidden rounded-2xl border bg-card transition-colors hover:border-primary/40"
        >
            <span className="relative flex aspect-square items-center justify-center overflow-hidden bg-muted">
                {product.image ? (
                    <img
                        src={product.image}
                        alt=""
                        loading="lazy"
                        className={cn(
                            'size-full object-cover transition-transform duration-300 group-hover:scale-[1.03]',
                            product.sold_out && 'opacity-60',
                        )}
                    />
                ) : (
                    <Package
                        className="size-8 text-muted-foreground"
                        aria-hidden="true"
                    />
                )}
                {product.sold_out ? (
                    <Badge
                        variant="secondary"
                        className="absolute top-2 left-2"
                    >
                        Sold out
                    </Badge>
                ) : null}
            </span>
            <span className="flex flex-1 flex-col gap-1 p-3">
                <span className="line-clamp-2 text-sm font-medium sm:text-base">
                    {product.name}
                </span>
                <span className="mt-auto text-lg font-bold tabular-nums">
                    {dollars(product.price_cents)}
                </span>
            </span>
        </Link>
    );
}

function ProductGridSkeleton() {
    return (
        <ul
            className="mt-4 grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 lg:grid-cols-4"
            aria-hidden="true"
        >
            {Array.from({ length: 4 }, (_, index) => (
                <li
                    key={index}
                    className="flex flex-col gap-3 rounded-2xl border bg-card p-3"
                >
                    <Skeleton className="aspect-square w-full rounded-xl" />
                    <Skeleton className="h-4 w-2/3" />
                    <Skeleton className="h-5 w-1/3" />
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
    return (
        <Link
            href={StoreController.show(storeSlug, {
                query: slug === '' ? {} : { category: slug },
            })}
            preserveScroll
            aria-current={active ? 'page' : undefined}
            className={cn(
                'inline-flex min-h-11 shrink-0 items-center rounded-full border px-4 text-sm font-medium transition-colors',
                active
                    ? 'border-primary bg-primary text-primary-foreground'
                    : 'bg-card text-muted-foreground hover:border-primary/40 hover:text-foreground',
            )}
        >
            {children}
        </Link>
    );
}
