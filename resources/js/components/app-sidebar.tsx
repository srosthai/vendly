import { Link, usePage } from '@inertiajs/react';
import {
    CreditCard,
    Globe,
    LayoutGrid,
    MessageSquare,
    Package,
    Quote,
    Send,
    Shapes,
    Store,
    Tag,
    Tags,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { WorkspaceCard } from '@/components/workspace-card';
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
import admin from '@/routes/admin';
import vendor from '@/routes/vendor';
import type { NavItem } from '@/types';

type NavGroup = { label?: string; items: NavItem[] };

const overview: NavItem = {
    title: 'Dashboard',
    href: dashboard(),
    icon: LayoutGrid,
};

const vendorGroups: NavGroup[] = [
    { items: [overview] },
    {
        label: 'Catalog',
        items: [
            { title: 'Products', href: vendor.products(), icon: Package },
            { title: 'Categories', href: vendor.categories(), icon: Shapes },
            { title: 'Brands', href: vendor.brands(), icon: Tag },
        ],
    },
    {
        label: 'Store',
        items: [
            { title: 'Store', href: vendor.store(), icon: Store },
            { title: 'Telegram', href: vendor.telegram(), icon: Send },
            { title: 'Plan', href: vendor.plan(), icon: CreditCard },
        ],
    },
];

const adminGroups: NavGroup[] = [
    { items: [overview] },
    {
        label: 'Sellers',
        items: [
            { title: 'Vendors', href: admin.vendors(), icon: Store },
            { title: 'Plans', href: admin.plans(), icon: Tags },
            { title: 'Payments', href: admin.payments(), icon: CreditCard },
        ],
    },
    {
        label: 'Messages',
        items: [
            {
                title: 'Requests',
                href: admin.requests(),
                icon: MessageSquare,
            },
            { title: 'Telegram', href: admin.telegram(), icon: Send },
        ],
    },
    {
        label: 'Website',
        items: [
            {
                title: 'Site settings',
                href: admin.site(),
                icon: Globe,
            },
            {
                title: 'Testimonials',
                href: admin.testimonials(),
                icon: Quote,
            },
        ],
    },
];

export function AppSidebar() {
    const { auth } = usePage().props;
    const groups =
        auth.user?.is_admin === true
            ? adminGroups
            : auth.hasStore
              ? vendorGroups
              : [{ items: [overview] }];

    return (
        <Sidebar collapsible="icon" variant="floating">
            <SidebarHeader className="p-3">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            size="lg"
                            asChild
                            className="hover:bg-transparent"
                        >
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent className="gap-1">
                {groups.map((group, index) => (
                    <NavMain
                        key={group.label ?? index}
                        label={group.label}
                        items={group.items}
                    />
                ))}
            </SidebarContent>

            <SidebarFooter className="p-3 group-data-[collapsible=icon]:hidden">
                <WorkspaceCard />
            </SidebarFooter>
        </Sidebar>
    );
}
