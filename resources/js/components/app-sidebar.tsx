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
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
} from '@/components/ui/sidebar';

import { dashboard } from '@/routes';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { LayoutGrid, SendToBackIcon, Zap, ExternalLink, Users, Shield, Settings, FlaskConical } from 'lucide-react';
import AppLogo from './app-logo';
import { Badge } from '@/components/ui/badge';
import { router } from '@inertiajs/react';

type ActiveReservation = {
    id: string;
    lab_id: string;
    lab_title: string;
    lab_description: string;
    start_at: string;
    end_at: string;
    duration_hours: number | null;
    time_remaining: number | null;
};

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title:'Labs',
        href: '/labs',
        icon: SendToBackIcon,
    }
];

const footerNavItems: NavItem[] = [

];

export function AppSidebar() {
    const page = usePage<SharedData & { userActiveReservations?: ActiveReservation[] }>();
    const { userActiveReservations = [], auth } = page.props;
    const user = auth.user;
    const isAdmin = user && 'role' in user && user.role === 'admin';

    // Filter only active (non-expired) reservations
    const now = new Date();
    const activeLabs = userActiveReservations.filter((reservation: ActiveReservation) => {
        const endTime = new Date(reservation.end_at);
        return endTime > now;
    });

    // Create dynamic nav items based on active labs
    const dynamicNavItems = [...mainNavItems];

    if (activeLabs.length > 0) {
        dynamicNavItems.push({
            title: 'My Reserved Labs',
            href: '/labs?filter=reserved',
            icon: Zap,
        });
    }

    // Admin navigation items
    const adminNavItems: NavItem[] = [
        {
            title: 'Tableau de bord',
            href: '/admin',
            icon: LayoutGrid,
        },
        {
            title: 'Utilisateurs',
            href: '/admin/users',
            icon: Users,
        },
        {
            title: 'Labs',
            href: '/admin/labs',
            icon: FlaskConical,
        },
        {
            title: 'Configuration CML',
            href: '/admin/cml-config',
            icon: Settings,
        },
    ];

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
                <NavMain items={dynamicNavItems} />

                {/* Administration Section - Only visible for admins */}
                {isAdmin && (
                    <SidebarGroup>
                        <SidebarGroupLabel className="flex items-center gap-2">
                            <Shield className="h-4 w-4 text-red-600" />
                            Administration
                        </SidebarGroupLabel>
                        <SidebarGroupContent>
                            <NavMain items={adminNavItems} />
                        </SidebarGroupContent>
                    </SidebarGroup>
                )}

                {/* Active Labs Section */}
                {activeLabs.length > 0 && (
                    <SidebarGroup>
                        <SidebarGroupLabel className="flex items-center gap-2">
                            <Zap className="h-4 w-4 text-green-600" />
                            Active Labs
                            <Badge variant="secondary" className="ml-auto text-xs">
                                {activeLabs.length}
                            </Badge>
                        </SidebarGroupLabel>
                        <SidebarGroupContent>
                            <SidebarMenu>
                                {activeLabs.slice(0, 3).map((reservation: ActiveReservation) => (
                                    <SidebarMenuItem key={reservation.id}>
                                        <SidebarMenuButton
                                            onClick={() => router.visit(`/labs/${reservation.lab_id}/workspace`, {
                                                method: 'get',
                                                preserveScroll: true
                                            })}
                                            className="group relative"
                                        >
                                            <div className="flex items-center gap-3 w-full">
                                                <div className="w-2 h-2 rounded-full bg-green-500 group-hover:bg-green-400 transition-colors" />
                                                <div className="flex-1 min-w-0">
                                                    <div className="font-medium text-sm truncate">
                                                        {reservation.lab_title}
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {reservation.time_remaining && (
                                                            <span>
                                                                {Math.floor(reservation.time_remaining / 60)}h {reservation.time_remaining % 60}m left
                                                            </span>
                                                        )}
                                                    </div>
                                                </div>
                                                <ExternalLink className="h-3 w-3 opacity-0 group-hover:opacity-100 transition-opacity" />
                                            </div>
                                        </SidebarMenuButton>
                                    </SidebarMenuItem>
                                ))}
                                {activeLabs.length > 3 && (
                                    <SidebarMenuItem>
                                        <SidebarMenuButton asChild>
                                            <Link href="/labs" className="text-xs text-muted-foreground">
                                                +{activeLabs.length - 3} more labs
                                            </Link>
                                        </SidebarMenuButton>
                                    </SidebarMenuItem>
                                )}
                            </SidebarMenu>
                        </SidebarGroupContent>
                    </SidebarGroup>
                )}
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
