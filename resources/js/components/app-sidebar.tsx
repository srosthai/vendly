import { Link, usePage } from '@inertiajs/react';
import {
    BookOpen,
    CreditCard,
    FolderGit2,
    LayoutGrid,
    MessageSquare,
    Package,
    Send,
    Shapes,
    Store,
    Tag,
    Tags,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

const vendorNavItems: NavItem[] = [
    { title: 'Store', href: '/vendor/store', icon: Store },
    { title: 'Products', href: '/vendor/products', icon: Package },
    { title: 'Categories', href: '/vendor/categories', icon: Shapes },
    { title: 'Brands', href: '/vendor/brands', icon: Tag },
    { title: 'Plan', href: '/vendor/plan', icon: CreditCard },
    { title: 'Telegram', href: '/vendor/telegram', icon: Send },
];

const adminNavItems: NavItem[] = [
    { title: 'Vendors', href: '/admin/vendors', icon: Store },
    { title: 'Plans', href: '/admin/plans', icon: Tags },
    { title: 'Payments', href: '/admin/payments', icon: CreditCard },
    { title: 'Requests', href: '/admin/requests', icon: MessageSquare },
    { title: 'Telegram', href: '/admin/telegram', icon: Send },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    const { auth } = usePage().props;
    const items =
        auth.user?.is_admin === true
            ? adminNavItems
            : auth.hasStore
              ? vendorNavItems
              : mainNavItems;

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={items} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
