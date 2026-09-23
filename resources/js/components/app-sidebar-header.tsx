import { Form, usePage } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { UserInfo } from '@/components/user-info';
import { UserMenuContent } from '@/components/user-menu-content';
import admin from '@/routes/admin';
import vendor from '@/routes/vendor';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

/**
 * Search goes where the person manages things: a vendor searches their
 * products, an admin searches vendors. Customers have nothing to search here.
 */
function WorkspaceSearch() {
    const { auth, workspace } = usePage().props;
    const target =
        auth.user?.is_admin === true
            ? { url: admin.vendors().url, label: 'Search vendors' }
            : workspace?.kind === 'vendor'
              ? { url: vendor.products().url, label: 'Search products' }
              : null;

    if (target === null) {
        return null;
    }

    return (
        <Form
            action={target.url}
            method="get"
            role="search"
            className="relative hidden w-full max-w-xs sm:block"
        >
            <Search
                className="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-muted-foreground"
                aria-hidden="true"
            />
            <label htmlFor="workspace-search" className="sr-only">
                {target.label}
            </label>
            <Input
                id="workspace-search"
                name="search"
                type="search"
                placeholder={target.label}
                className="rounded-full bg-background pl-10"
            />
        </Form>
    );
}

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    const { auth } = usePage().props;

    return (
        <header className="sticky top-2 z-20 mx-4 mt-2 flex h-16 shrink-0 items-center gap-3 rounded-2xl border bg-card px-3 md:mx-6">
            <SidebarTrigger className="size-10 rounded-full" />
            <div className="hidden min-w-0 lg:block">
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>
            <div className="ml-auto flex items-center gap-2">
                <WorkspaceSearch />
                {auth.user ? (
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button
                                variant="ghost"
                                className="h-10 gap-2 rounded-full px-1.5 sm:pr-3"
                                data-test="sidebar-menu-button"
                            >
                                <UserInfo user={auth.user} compact />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent className="min-w-56" align="end">
                            <UserMenuContent user={auth.user} />
                        </DropdownMenuContent>
                    </DropdownMenu>
                ) : null}
            </div>
        </header>
    );
}
