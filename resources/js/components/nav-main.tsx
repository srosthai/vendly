import { Link } from '@inertiajs/react';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import type { NavItem } from '@/types';

/**
 * One group of sidebar links. Collapsed to icons, the label gives way to a
 * short rule so every group keeps the same gap.
 */
export function NavMain({
    label,
    items,
}: {
    label?: string;
    items: NavItem[];
}) {
    const { isCurrentUrl } = useCurrentUrl();

    return (
        <SidebarGroup className="px-3 py-1">
            {label ? (
                <>
                    <SidebarGroupLabel>{label}</SidebarGroupLabel>
                    <span
                        aria-hidden="true"
                        className="mx-auto mb-2 hidden h-px w-6 bg-sidebar-border group-data-[collapsible=icon]:block"
                    />
                </>
            ) : null}
            <SidebarMenu className="gap-1">
                {items.map((item) => (
                    <SidebarMenuItem key={item.title}>
                        <SidebarMenuButton
                            asChild
                            isActive={isCurrentUrl(item.href)}
                            tooltip={{ children: item.title }}
                        >
                            <Link href={item.href} prefetch>
                                {item.icon && <item.icon />}
                                <span>{item.title}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                ))}
            </SidebarMenu>
        </SidebarGroup>
    );
}
