import { usePage } from '@inertiajs/react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { GlobalSearch } from '@/components/global-search';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { ThemeToggle } from '@/components/theme-toggle';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { UserInfo } from '@/components/user-info';
import { UserMenuContent } from '@/components/user-menu-content';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

/**
 * Admins search the whole back office and vendors their own store.
 * Customers have nothing to search here.
 */
function WorkspaceSearch() {
    const { auth, workspace } = usePage().props;

    if (auth.user?.is_admin === true) {
        return <GlobalSearch placeholder="Search the back office" />;
    }

    if (workspace?.kind === 'vendor') {
        return <GlobalSearch placeholder="Search your store" />;
    }

    return null;
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
                <ThemeToggle />
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
