import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    from?: number | null;
    to?: number | null;
    total?: number;
};

function PageButton({ href, label }: { href: string | null; label: string }) {
    if (!href) {
        return (
            <Button variant="outline" size="sm" disabled>
                {label}
            </Button>
        );
    }

    return (
        <Button variant="outline" size="sm" asChild>
            <Link href={href} preserveScroll={false}>
                {label}
            </Link>
        </Button>
    );
}

/**
 * Previous and next for a Laravel paginator. Hidden when there is one page.
 */
export function SimplePagination<T>({ page }: { page: Paginated<T> }) {
    if (page.last_page <= 1) {
        return null;
    }

    return (
        <nav
            aria-label="Pages"
            className="flex items-center justify-between gap-4"
        >
            <PageButton href={page.prev_page_url} label="Previous" />
            <span className="text-sm text-muted-foreground">
                {page.from && page.to && page.total !== undefined
                    ? `Showing ${page.from}–${page.to} of ${page.total}`
                    : `Page ${page.current_page} of ${page.last_page}`}
            </span>
            <PageButton href={page.next_page_url} label="Next" />
        </nav>
    );
}
