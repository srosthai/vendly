import { timeAgo } from '@/lib/time-ago';

export type RecentStore = {
    name: string;
    url: string;
    joined_at: string | null;
};

function StorePill({
    store,
    hidden,
}: {
    store: RecentStore;
    hidden?: boolean;
}) {
    return (
        <li aria-hidden={hidden || undefined}>
            <a
                href={store.url}
                tabIndex={hidden ? -1 : undefined}
                className="flex items-center gap-2.5 rounded-full border bg-card py-1.5 pr-4 pl-1.5 text-sm whitespace-nowrap transition-colors hover:border-primary/40"
            >
                <span className="flex size-7 items-center justify-center rounded-full bg-secondary text-xs font-bold text-secondary-foreground">
                    {store.name.slice(0, 1).toUpperCase()}
                </span>
                <span className="font-medium">{store.name}</span>
                <span className="text-muted-foreground">
                    {timeAgo(store.joined_at)}
                </span>
            </a>
        </li>
    );
}

/**
 * Real stores that opened most recently, sliding past without end. The list
 * is drawn twice so the loop is seamless; the copy is hidden from screen
 * readers and keyboard. Hovering or focusing pauses it.
 */
export function RecentlyJoined({ stores }: { stores: RecentStore[] }) {
    if (stores.length === 0) {
        return null;
    }

    const loop = stores.length >= 2;
    // A short list repeats until it is wide enough to loop without a gap.
    const lane = loop
        ? Array.from(
              { length: Math.ceil(8 / stores.length) },
              () => stores,
          ).flat()
        : stores;

    return (
        <section
            aria-labelledby="recently-joined"
            className="flex flex-col gap-4"
        >
            <div className="mx-auto flex w-full max-w-6xl items-center gap-3 px-4 md:px-6">
                <span className="relative flex size-2">
                    <span className="absolute inline-flex size-full animate-ping rounded-full bg-success/60 motion-reduce:hidden" />
                    <span className="relative inline-flex size-2 rounded-full bg-success" />
                </span>
                <h2
                    id="recently-joined"
                    className="text-sm font-medium text-muted-foreground"
                >
                    Recently joined on Vendly
                </h2>
                <span className="h-px flex-1 bg-border" aria-hidden="true" />
            </div>
            <div className="marquee no-scrollbar overflow-x-auto [mask-image:linear-gradient(to_right,transparent,black_6%,black_94%,transparent)]">
                <ul
                    className={
                        loop
                            ? 'marquee-track flex w-max gap-3 px-3'
                            : 'mx-auto flex w-max gap-3 px-4'
                    }
                >
                    {lane.map((store, index) => (
                        <StorePill
                            key={`${store.url}-${index}`}
                            store={store}
                            hidden={index >= stores.length}
                        />
                    ))}
                    {loop
                        ? lane.map((store, index) => (
                              <StorePill
                                  key={`${store.url}-copy-${index}`}
                                  store={store}
                                  hidden
                              />
                          ))
                        : null}
                </ul>
            </div>
        </section>
    );
}
