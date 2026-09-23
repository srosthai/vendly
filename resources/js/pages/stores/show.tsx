import { Head, InfiniteScroll, Link, router } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import { ArrowDownUp, Package, Send, ShoppingBag } from 'lucide-react';
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
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { dollars, formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';

type ProductCard = {
    id: number;
    name: string;
    price_cents: number;
    sold_out: boolean;
    image: string | null;
    url: string;
    telegram_url: string | null;
};

type Option = { name: string; slug: string };

type Filters = { category: string; brand: string; sort: string };

const sorts = [
    { value: 'newest', label: 'Newest' },
    { value: 'price-low', label: 'Price: low to high' },
    { value: 'price-high', label: 'Price: high to low' },
];

/**
 * The storefront URL for a set of filters, leaving out the defaults so the
 * plain store link stays clean.
 */
function filterUrl(storeSlug: string, filters: Filters) {
    const query: Record<string, string> = {};

    if (filters.category !== '') {
        query.category = filters.category;
    }

    if (filters.brand !== '') {
        query.brand = filters.brand;
    }

    if (filters.sort !== 'newest') {
        query.sort = filters.sort;
    }

    return StoreController.show(storeSlug, { query });
}

export default function Show({
    store,
    categories,
    brands,
    filters,
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
        telegram_url: string | null;
        products_count: number;
        joined_at: string | null;
    };
    categories: Option[];
    brands: Option[];
    filters: Filters;
    products: { data: ProductCard[] };
    embedded: boolean;
    authenticated: boolean;
    cart: CartData;
}) {
    const miniApp = useTelegramMiniApp(authenticated);
    const inTelegram = embedded || miniApp.inTelegram;
    const filtered = filters.category !== '' || filters.brand !== '';
    const visit = (next: Partial<Filters>) =>
        router.visit(filterUrl(store.slug, { ...filters, ...next }), {
            preserveScroll: true,
        });

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
                    details={
                        <div className="mt-3 flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
                            <span className="rounded-full bg-secondary px-3 py-1 text-secondary-foreground">
                                {store.products_count === 1
                                    ? '1 product'
                                    : `${store.products_count} products`}
                            </span>
                            {store.joined_at ? (
                                <span className="rounded-full border px-3 py-1">
                                    On Vendly since{' '}
                                    {formatDate(store.joined_at)}
                                </span>
                            ) : null}
                            {!inTelegram && store.telegram_url ? (
                                <a
                                    href={store.telegram_url}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 font-medium text-primary transition-colors hover:border-primary/40 hover:bg-primary/5"
                                >
                                    <Send
                                        className="size-3.5"
                                        aria-hidden="true"
                                    />
                                    Open in Telegram
                                </a>
                            ) : null}
                        </div>
                    }
                    cart={
                        <CartSheet
                            cart={cart}
                            storeSlug={store.slug}
                            authenticated={authenticated}
                        />
                    }
                />
                <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    {categories.length > 0 ? (
                        <nav
                            aria-label="Categories"
                            className="no-scrollbar -mx-4 flex min-w-0 gap-2 overflow-x-auto px-4 pb-1 md:mx-0 md:px-0"
                        >
                            <CategoryLink
                                href={filterUrl(store.slug, {
                                    ...filters,
                                    category: '',
                                })}
                                active={filters.category === ''}
                            >
                                All
                            </CategoryLink>
                            {categories.map((category) => (
                                <CategoryLink
                                    key={category.slug}
                                    href={filterUrl(store.slug, {
                                        ...filters,
                                        category: category.slug,
                                    })}
                                    active={filters.category === category.slug}
                                >
                                    {category.name}
                                </CategoryLink>
                            ))}
                        </nav>
                    ) : (
                        <span />
                    )}
                    <div className="flex shrink-0 gap-2">
                        {brands.length > 0 ? (
                            <Select
                                value={
                                    filters.brand === '' ? 'all' : filters.brand
                                }
                                onValueChange={(value) =>
                                    visit({
                                        brand: value === 'all' ? '' : value,
                                    })
                                }
                            >
                                <SelectTrigger
                                    aria-label="Brand"
                                    className="h-11 min-w-36 flex-1 rounded-full bg-card lg:flex-none"
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        All brands
                                    </SelectItem>
                                    {brands.map((brand) => (
                                        <SelectItem
                                            key={brand.slug}
                                            value={brand.slug}
                                        >
                                            {brand.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        ) : null}
                        <Select
                            value={filters.sort}
                            onValueChange={(value) => visit({ sort: value })}
                        >
                            <SelectTrigger
                                aria-label="Sort products"
                                className="h-11 min-w-44 flex-1 rounded-full bg-card lg:flex-none"
                            >
                                <span className="flex items-center gap-2">
                                    <ArrowDownUp
                                        className="size-4 text-muted-foreground"
                                        aria-hidden="true"
                                    />
                                    <SelectValue />
                                </span>
                            </SelectTrigger>
                            <SelectContent>
                                {sorts.map((sort) => (
                                    <SelectItem
                                        key={sort.value}
                                        value={sort.value}
                                    >
                                        {sort.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>
                {products.data.length === 0 ? (
                    <EmptyState
                        icon={ShoppingBag}
                        title={filtered ? 'Nothing matches' : 'No products yet'}
                        description={
                            filtered
                                ? 'Try another category or brand, or see everything in the store.'
                                : 'This store is getting ready. Check back soon.'
                        }
                        action={
                            filtered ? (
                                <Button asChild variant="outline">
                                    <Link
                                        href={filterUrl(store.slug, {
                                            category: '',
                                            brand: '',
                                            sort: filters.sort,
                                        })}
                                    >
                                        Show all products
                                    </Link>
                                </Button>
                            ) : undefined
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
                                    <ProductTile
                                        product={product}
                                        inTelegram={inTelegram}
                                    />
                                </li>
                            ))}
                        </ul>
                    </InfiniteScroll>
                )}
                <StorefrontFooter hidden={inTelegram} />
            </main>
        </>
    );
}

/**
 * One product. The photo and name open the product page; Buy opens the
 * product in the Telegram mini app when browsing on the web, and the
 * product page inside Telegram, where one tap sends the request.
 */
function ProductTile({
    product,
    inTelegram,
}: {
    product: ProductCard;
    inTelegram: boolean;
}) {
    const buyInTelegram = !inTelegram && product.telegram_url !== null;

    return (
        <article className="group flex h-full flex-col overflow-hidden rounded-2xl border bg-card transition-[border-color,box-shadow] duration-200 hover:border-primary/40 hover:shadow-lg hover:shadow-primary/5">
            <Link
                href={product.url}
                className="flex flex-1 flex-col rounded-t-2xl outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
            >
                <span className="relative flex aspect-square items-center justify-center overflow-hidden bg-muted">
                    {product.image ? (
                        <img
                            src={product.image}
                            alt=""
                            loading="lazy"
                            className={cn(
                                'size-full object-cover transition-transform duration-300 group-hover:scale-[1.03] motion-reduce:group-hover:scale-100',
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
                <span className="flex flex-1 flex-col gap-1 p-3 pb-2">
                    <span className="line-clamp-2 text-sm font-medium sm:text-base">
                        {product.name}
                    </span>
                    <span className="mt-auto text-lg font-bold tabular-nums">
                        {dollars(product.price_cents)}
                    </span>
                </span>
            </Link>
            <div className="p-3 pt-1">
                {product.sold_out ? (
                    <Button
                        variant="secondary"
                        size="sm"
                        className="h-10 w-full"
                        disabled
                    >
                        Sold out
                    </Button>
                ) : buyInTelegram ? (
                    <Button asChild size="sm" className="h-10 w-full">
                        <a
                            href={product.telegram_url ?? undefined}
                            target="_blank"
                            rel="noreferrer"
                            aria-label={`Buy ${product.name} in Telegram`}
                        >
                            <Send />
                            Buy
                        </a>
                    </Button>
                ) : (
                    <Button asChild size="sm" className="h-10 w-full">
                        <Link
                            href={product.url}
                            aria-label={`Buy ${product.name}`}
                        >
                            <Send />
                            Buy
                        </Link>
                    </Button>
                )}
            </div>
        </article>
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
    href,
    active,
    children,
}: {
    href: ReturnType<typeof StoreController.show>;
    active: boolean;
    children: string;
}) {
    return (
        <Link
            href={href}
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
