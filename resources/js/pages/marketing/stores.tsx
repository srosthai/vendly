import { InfiniteScroll, Link, router } from '@inertiajs/react';
import { Search, Store as StoreIcon } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { EmptyState } from '@/components/empty-state';
import { MarketingHead } from '@/components/marketing/marketing-head';
import type { PageMeta } from '@/components/marketing/marketing-head';
import { SectionHeading } from '@/components/marketing/section';
import { StoreMark } from '@/components/storefront/store-header';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import { formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';
import { index as storesIndex } from '@/routes/stores';

type DirectoryStore = {
    name: string;
    description: string | null;
    logo: string | null;
    accent: string | null;
    url: string;
    products_count: number;
    joined_at: string | null;
    previews: string[];
};

/**
 * Up to three product photos from the store, so a visitor sees what it
 * sells before opening it. A store without photos shows its mark instead.
 */
function Previews({ store }: { store: DirectoryStore }) {
    if (store.previews.length === 0) {
        return (
            <div className="flex aspect-[16/9] items-center justify-center bg-secondary">
                <StoreMark
                    store={{ ...store, slug: '' }}
                    className="size-16 bg-card text-2xl"
                />
            </div>
        );
    }

    return (
        <div
            className={cn(
                'grid aspect-[16/9] grid-rows-1 gap-0.5 bg-border',
                store.previews.length === 1 && 'grid-cols-1',
                store.previews.length === 2 && 'grid-cols-2',
                store.previews.length === 3 && 'grid-cols-3',
            )}
        >
            {store.previews.map((image) => (
                <img
                    key={image}
                    src={image}
                    alt=""
                    loading="lazy"
                    className="size-full min-h-0 object-cover"
                />
            ))}
        </div>
    );
}

function StoreCard({ store }: { store: DirectoryStore }) {
    return (
        <Link
            href={store.url}
            className="group flex h-full flex-col overflow-hidden rounded-3xl border bg-card transition-[border-color,box-shadow,translate] duration-200 ease-out hover:-translate-y-0.5 hover:border-primary/40 hover:shadow-lg hover:shadow-primary/5 focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none motion-reduce:transition-none motion-reduce:hover:translate-y-0"
        >
            <div className="overflow-hidden">
                <div className="transition-transform duration-300 ease-out group-hover:scale-[1.03] motion-reduce:group-hover:scale-100">
                    <Previews store={store} />
                </div>
            </div>
            <div className="flex flex-1 flex-col gap-3 p-5">
                <div className="flex items-center gap-3">
                    <StoreMark
                        store={{ ...store, slug: '' }}
                        className="size-11 rounded-xl text-base"
                    />
                    <div className="min-w-0">
                        <h2 className="truncate text-lg font-semibold">
                            {store.name}
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            {store.products_count === 1
                                ? '1 product'
                                : `${store.products_count} products`}
                        </p>
                    </div>
                </div>
                {store.description ? (
                    <p className="line-clamp-2 text-muted-foreground">
                        {store.description}
                    </p>
                ) : null}
                {store.joined_at ? (
                    <p className="mt-auto text-sm text-muted-foreground">
                        On Vendly since {formatDate(store.joined_at)}
                    </p>
                ) : null}
            </div>
        </Link>
    );
}

function DirectorySkeleton() {
    return (
        <div
            className="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
            aria-hidden="true"
        >
            {Array.from({ length: 3 }, (_, index) => (
                <div
                    key={index}
                    className="overflow-hidden rounded-3xl border bg-card"
                >
                    <Skeleton className="aspect-[16/9] w-full rounded-none" />
                    <div className="flex flex-col gap-2 p-5">
                        <Skeleton className="h-5 w-1/2" />
                        <Skeleton className="h-4 w-1/3" />
                    </div>
                </div>
            ))}
        </div>
    );
}

export default function Stores({
    meta,
    search,
    stores,
}: {
    meta: PageMeta;
    search: string;
    stores: { data: DirectoryStore[] };
}) {
    const [query, setQuery] = useState(search);
    const firstRender = useRef(true);

    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;

            return;
        }

        const timer = window.setTimeout(() => {
            router.get(
                storesIndex({
                    query: query.trim() === '' ? {} : { search: query.trim() },
                }),
                {},
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }, 300);

        return () => window.clearTimeout(timer);
    }, [query]);

    return (
        <>
            <MarketingHead meta={meta} />
            <section className="px-4 pt-16 pb-20 md:px-6 lg:pt-24">
                <div className="mx-auto max-w-6xl">
                    <div className="flex flex-wrap items-end justify-between gap-6">
                        <SectionHeading
                            as="h1"
                            title="Shops selling on Vendly"
                            description="Open a store to see what it sells. When you find something you want, buy it through Telegram."
                        />
                        <label className="relative w-full sm:w-80">
                            <span className="sr-only">Search stores</span>
                            <Search
                                className="pointer-events-none absolute top-1/2 left-4 size-4 -translate-y-1/2 text-muted-foreground"
                                aria-hidden="true"
                            />
                            <Input
                                type="search"
                                value={query}
                                onChange={(event) =>
                                    setQuery(event.target.value)
                                }
                                placeholder="Search by store name"
                                className="h-11 rounded-full pl-10"
                            />
                        </label>
                    </div>

                    {stores.data.length === 0 ? (
                        <div className="mt-12">
                            <EmptyState
                                icon={StoreIcon}
                                title={
                                    search === ''
                                        ? 'No stores yet'
                                        : `No store matches “${search}”`
                                }
                                description={
                                    search === ''
                                        ? 'Stores appear here once they publish their first product.'
                                        : 'Check the spelling, or clear the search to see every store.'
                                }
                            />
                        </div>
                    ) : (
                        <InfiniteScroll
                            data="stores"
                            buffer={400}
                            loading={<DirectorySkeleton />}
                            className="mt-12"
                        >
                            <ul className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                {stores.data.map((store) => (
                                    <li key={store.url}>
                                        <StoreCard store={store} />
                                    </li>
                                ))}
                            </ul>
                        </InfiniteScroll>
                    )}
                </div>
            </section>
        </>
    );
}
