import { router } from '@inertiajs/react';
import { Search, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

export type ListFilter = {
    key: string;
    label: string;
    options: { value: string; label: string }[];
};

type Values = Record<string, string>;

/**
 * Search, filters, sort, and a result count above an admin list. Every
 * change reloads the list with the values in the URL, leaving out the
 * defaults so a plain list keeps a plain address. Changing anything goes
 * back to the first page.
 */
export function ListToolbar({
    url,
    values,
    defaults,
    searchPlaceholder,
    filters = [],
    sorts = [],
    dates,
    total,
    noun,
}: {
    url: string;
    values: Values;
    defaults: Values;
    searchPlaceholder?: string;
    filters?: ListFilter[];
    sorts?: { value: string; label: string }[];
    dates?: boolean;
    total: number;
    noun: [string, string];
}) {
    const [search, setSearch] = useState(values.search ?? '');
    const firstRender = useRef(true);
    const filtered = Object.keys(defaults).some(
        (key) => key !== 'sort' && (values[key] ?? '') !== defaults[key],
    );

    function visit(next: Values) {
        const query: Values = {};

        for (const [key, value] of Object.entries({ ...values, ...next })) {
            if (value !== '' && value !== defaults[key]) {
                query[key] = value;
            }
        }

        router.get(url, query, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }

    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;

            return;
        }

        const timer = window.setTimeout(
            () => visit({ search: search.trim() }),
            300,
        );

        return () => window.clearTimeout(timer);
        // Only the typed search should schedule a visit.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    return (
        <div className="flex flex-col gap-3">
            <div className="flex flex-wrap items-center gap-2">
                {searchPlaceholder !== undefined ? (
                    <label className="relative w-full sm:w-72">
                        <span className="sr-only">{searchPlaceholder}</span>
                        <Search
                            className="pointer-events-none absolute top-1/2 left-4 size-4 -translate-y-1/2 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <Input
                            type="search"
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder={searchPlaceholder}
                            className="h-10 rounded-full pl-10"
                        />
                    </label>
                ) : null}
                <>
                    {filters.map((filter) => (
                        <Select
                            key={filter.key}
                            value={values[filter.key] ?? defaults[filter.key]}
                            onValueChange={(value) =>
                                visit({ [filter.key]: value })
                            }
                        >
                            <SelectTrigger
                                aria-label={filter.label}
                                className="h-10 min-w-36 rounded-full bg-card"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {filter.options.map((option) => (
                                    <SelectItem
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    ))}
                    {dates ? (
                        <>
                            <label className="flex items-center gap-2 text-sm text-muted-foreground">
                                From
                                <Input
                                    type="date"
                                    value={values.from ?? ''}
                                    max={values.to || undefined}
                                    onChange={(event) =>
                                        visit({ from: event.target.value })
                                    }
                                    className="h-10 w-auto rounded-full"
                                />
                            </label>
                            <label className="flex items-center gap-2 text-sm text-muted-foreground">
                                To
                                <Input
                                    type="date"
                                    value={values.to ?? ''}
                                    min={values.from || undefined}
                                    onChange={(event) =>
                                        visit({ to: event.target.value })
                                    }
                                    className="h-10 w-auto rounded-full"
                                />
                            </label>
                        </>
                    ) : null}
                    {sorts.length > 0 ? (
                        <Select
                            value={values.sort ?? defaults.sort}
                            onValueChange={(value) => visit({ sort: value })}
                        >
                            <SelectTrigger
                                aria-label="Sort"
                                className="h-10 min-w-40 rounded-full bg-card"
                            >
                                <SelectValue />
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
                    ) : null}
                </>
            </div>
            <div className="flex min-h-9 items-center justify-between gap-3">
                <p className="text-sm text-muted-foreground" role="status">
                    {total === 1 ? `1 ${noun[0]}` : `${total} ${noun[1]}`}
                    {filtered ? ' match' : ''}
                    {filtered && total === 1 ? 'es' : ''}
                </p>
                {filtered ? (
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => {
                            setSearch('');
                            visit(
                                Object.fromEntries(
                                    Object.keys(defaults)
                                        .filter((key) => key !== 'sort')
                                        .map((key) => [key, defaults[key]]),
                                ),
                            );
                        }}
                    >
                        <X />
                        Clear filters
                    </Button>
                ) : null}
            </div>
        </div>
    );
}
