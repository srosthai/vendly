import { router } from '@inertiajs/react';
import { CornerDownLeft, Search } from 'lucide-react';
import { useEffect, useId, useMemo, useRef, useState } from 'react';
import type { KeyboardEvent } from 'react';
import SearchController from '@/actions/App/Http/Controllers/SearchController';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogTitle } from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';

type Item = { title: string; subtitle: string | null; url: string };
type Group = { label: string; items: Item[] };

/**
 * Asks the server for suggestions once two characters are typed, waiting
 * for a short pause and dropping answers to older queries.
 */
function useSuggestions(query: string) {
    const [groups, setGroups] = useState<Group[]>([]);
    const [loading, setLoading] = useState(false);
    const [failed, setFailed] = useState(false);
    const trimmed = query.trim();

    useEffect(() => {
        if (trimmed.length < 2) {
            setGroups([]);
            setLoading(false);
            setFailed(false);

            return;
        }

        const controller = new AbortController();
        setLoading(true);

        const timer = window.setTimeout(() => {
            fetch(SearchController.url({ query: { q: trimmed } }), {
                headers: { Accept: 'application/json' },
                signal: controller.signal,
            })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error(String(response.status));
                    }

                    return response.json() as Promise<{ groups: Group[] }>;
                })
                .then((data) => {
                    setGroups(data.groups);
                    setFailed(false);
                    setLoading(false);
                })
                .catch((error: unknown) => {
                    if (
                        error instanceof DOMException &&
                        error.name === 'AbortError'
                    ) {
                        return;
                    }

                    setFailed(true);
                    setLoading(false);
                });
        }, 200);

        return () => {
            window.clearTimeout(timer);
            controller.abort();
        };
    }, [trimmed]);

    return { groups, loading, failed, ready: trimmed.length >= 2 };
}

/**
 * The search field and its grouped suggestions. Arrow keys move through
 * the results, Enter opens one, and Escape closes the list.
 */
function SearchPanel({
    placeholder,
    autoFocus = false,
    inline = false,
    inputRef,
    onNavigate,
}: {
    placeholder: string;
    autoFocus?: boolean;
    inline?: boolean;
    inputRef?: React.RefObject<HTMLInputElement | null>;
    onNavigate?: () => void;
}) {
    const id = useId();
    const [query, setQuery] = useState('');
    const [open, setOpen] = useState(false);
    const [active, setActive] = useState(0);
    const { groups, loading, failed, ready } = useSuggestions(query);
    const items = useMemo(
        () => groups.flatMap((group) => group.items),
        [groups],
    );
    const showList = (inline ? open : true) && ready;
    const [shortcut, setShortcut] = useState('Ctrl K');

    useEffect(() => {
        if (/Mac|iPhone|iPad/.test(navigator.userAgent)) {
            setShortcut('⌘ K');
        }
    }, []);

    useEffect(() => setActive(0), [groups]);

    function go(item: Item) {
        setOpen(false);
        setQuery('');
        onNavigate?.();
        router.visit(item.url);
    }

    function onKeyDown(event: KeyboardEvent<HTMLInputElement>) {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setOpen(true);
            setActive((index) => Math.min(index + 1, items.length - 1));
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            setActive((index) => Math.max(index - 1, 0));
        } else if (event.key === 'Enter') {
            const item = items[active];

            if (item && showList) {
                event.preventDefault();
                go(item);
            }
        } else if (event.key === 'Escape') {
            if (query !== '') {
                event.preventDefault();
                setQuery('');
            }

            setOpen(false);
            (event.target as HTMLInputElement).blur();
        }
    }

    let index = -1;

    return (
        <div className="relative w-full">
            <Search
                className="pointer-events-none absolute top-5 left-3.5 size-4 -translate-y-1/2 text-muted-foreground"
                aria-hidden="true"
            />
            <input
                ref={inputRef}
                id={id}
                type="search"
                role="combobox"
                aria-expanded={showList}
                aria-controls={`${id}-results`}
                aria-autocomplete="list"
                aria-activedescendant={
                    showList && items[active]
                        ? `${id}-item-${active}`
                        : undefined
                }
                autoFocus={autoFocus}
                autoComplete="off"
                value={query}
                placeholder={placeholder}
                onChange={(event) => {
                    setQuery(event.target.value);
                    setOpen(true);
                }}
                onFocus={() => setOpen(true)}
                onBlur={() => window.setTimeout(() => setOpen(false), 120)}
                onKeyDown={onKeyDown}
                className="h-10 w-full rounded-full border border-input bg-background pr-16 pl-10 text-base outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 md:text-sm [&::-webkit-search-cancel-button]:appearance-none"
            />
            {inline && query === '' ? (
                <kbd className="pointer-events-none absolute top-5 right-3 hidden -translate-y-1/2 rounded-md border bg-muted px-1.5 font-sans text-sm text-muted-foreground lg:inline">
                    {shortcut}
                </kbd>
            ) : null}
            {showList ? (
                <div
                    id={`${id}-results`}
                    role="listbox"
                    aria-label="Search results"
                    className={cn(
                        'max-h-[60vh] overflow-y-auto rounded-2xl border bg-popover p-1.5 text-popover-foreground',
                        inline
                            ? 'absolute top-12 right-0 z-50 w-[min(28rem,calc(100vw-2rem))] shadow-lg'
                            : 'mt-3',
                    )}
                    onMouseDown={(event) => event.preventDefault()}
                >
                    {loading && items.length === 0 ? (
                        <p className="flex items-center gap-2 px-3 py-4 text-sm text-muted-foreground">
                            <Spinner />
                            Searching
                        </p>
                    ) : failed ? (
                        <p className="px-3 py-4 text-sm text-muted-foreground">
                            Search is unavailable right now. Try again in a
                            moment.
                        </p>
                    ) : items.length === 0 ? (
                        <p className="px-3 py-4 text-sm text-muted-foreground">
                            Nothing matches “{query.trim()}”.
                        </p>
                    ) : (
                        groups.map((group) => (
                            <div
                                key={group.label}
                                role="group"
                                aria-label={group.label}
                            >
                                <p className="px-3 pt-2 pb-1 text-sm font-medium text-muted-foreground">
                                    {group.label}
                                </p>
                                {group.items.map((item) => {
                                    index += 1;
                                    const position = index;

                                    return (
                                        <div
                                            key={`${group.label}-${item.url}-${item.title}`}
                                            id={`${id}-item-${position}`}
                                            role="option"
                                            aria-selected={position === active}
                                            onMouseEnter={() =>
                                                setActive(position)
                                            }
                                            onClick={() => go(item)}
                                            className={cn(
                                                'flex min-h-11 cursor-pointer items-center justify-between gap-3 rounded-xl px-3 py-2',
                                                position === active &&
                                                    'bg-accent text-accent-foreground',
                                            )}
                                        >
                                            <span className="min-w-0">
                                                <span className="block truncate text-sm font-medium">
                                                    {item.title}
                                                </span>
                                                {item.subtitle ? (
                                                    <span className="block truncate text-sm text-muted-foreground">
                                                        {item.subtitle}
                                                    </span>
                                                ) : null}
                                            </span>
                                            {position === active ? (
                                                <CornerDownLeft
                                                    className="size-4 shrink-0 text-muted-foreground"
                                                    aria-hidden="true"
                                                />
                                            ) : null}
                                        </div>
                                    );
                                })}
                            </div>
                        ))
                    )}
                </div>
            ) : null}
        </div>
    );
}

/**
 * The top bar search: a field on wide screens and a button that opens a
 * search dialog on a phone. Ctrl+K or Cmd+K opens it from anywhere.
 */
export function GlobalSearch({ placeholder }: { placeholder: string }) {
    const inputRef = useRef<HTMLInputElement>(null);
    const [dialogOpen, setDialogOpen] = useState(false);

    useEffect(() => {
        function onKeyDown(event: globalThis.KeyboardEvent) {
            if (
                (event.metaKey || event.ctrlKey) &&
                event.key.toLowerCase() === 'k'
            ) {
                event.preventDefault();
                const input = inputRef.current;

                if (input && input.offsetParent !== null) {
                    input.focus();
                    input.select();
                } else {
                    setDialogOpen(true);
                }
            }
        }

        window.addEventListener('keydown', onKeyDown);

        return () => window.removeEventListener('keydown', onKeyDown);
    }, []);

    return (
        <>
            <div role="search" className="hidden w-full max-w-xs sm:block">
                <SearchPanel
                    inline
                    placeholder={placeholder}
                    inputRef={inputRef}
                />
            </div>
            <Button
                variant="outline"
                size="icon"
                className="size-11 rounded-full sm:hidden"
                aria-label="Search"
                onClick={() => setDialogOpen(true)}
            >
                <Search />
            </Button>
            <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                <DialogContent className="top-[10%] translate-y-0 gap-0 p-4 sm:max-w-lg">
                    <DialogTitle className="sr-only">Search</DialogTitle>
                    <div role="search">
                        <SearchPanel
                            autoFocus
                            placeholder={placeholder}
                            onNavigate={() => setDialogOpen(false)}
                        />
                    </div>
                </DialogContent>
            </Dialog>
        </>
    );
}
