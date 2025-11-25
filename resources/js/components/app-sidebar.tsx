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
import { LayoutGrid, SendToBackIcon, Zap, ExternalLink, Users, Shield, Settings, FlaskConical, Calendar, Activity, CreditCard } from 'lucide-react';
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
        href: dashboard().url,
        icon: LayoutGrid,
    },
    {
        title:'Labs',
        href: '/labs',
        icon: SendToBackIcon,
    },
    {
        title: 'My Reserved Labs',
        href: '/labs/my-reserved',
        icon: Zap,
    }
];

const footerNavItems: NavItem[] = [

];

export function AppSidebar() {
    const page = usePage<SharedData & { userActiveReservations?: ActiveReservation[] }>();
    const { userActiveReservations = [], auth } = page.props;
    const user = auth.user;
    
    // Améliorer la détection de l'admin
    const isAdmin = user?.role === 'admin';

    // Filter only active (non-expired) reservations
    const now = new Date();
    const activeLabs = userActiveReservations.filter((reservation: ActiveReservation) => {
        const endTime = new Date(reservation.end_at);
        return endTime > now;
    });

    // Use main nav items (My Reserved Labs is now always visible)
    const dynamicNavItems = [...mainNavItems];

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
            title: 'Réservations',
            href: '/admin/reservations',
            icon: Calendar,
        },
        {
            title: 'Configuration CML',
            href: '/admin/cml-config',
            icon: Settings,
        },
        {
            title: 'Configuration CinetPay',
            href: '/admin/cinetpay-config',
            icon: CreditCard,
        },
        {
            title: 'Santé API Paiement',
            href: '/admin/payments/health',
            icon: Activity,
        },
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard().url} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                {/* User Section - Only visible for non-admins */}
                {!isAdmin && <NavMain items={dynamicNavItems} />}

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

                {/* Active Labs Section - Only visible for non-admins */}
                {!isAdmin && activeLabs.length > 0 && (
                    <SidebarGroup className="mt-4">
                        <SidebarGroupLabel className="flex items-center justify-between gap-2 px-2 py-1.5">
                            <div className="flex items-center gap-2">
                                <Zap className="h-4 w-4 text-green-600 flex-shrink-0" />
                                <span className="font-semibold text-sm">Active Labs</span>
                            </div>
                            <Badge variant="secondary" className="ml-auto text-xs font-medium px-2 py-0.5">
                                {activeLabs.length}
                            </Badge>
                        </SidebarGroupLabel>
                        <SidebarGroupContent className="px-1">
                            <SidebarMenu className="space-y-1">
                                {activeLabs.slice(0, 3).map((reservation: ActiveReservation) => {
                                    const hours = reservation.time_remaining ? Math.floor(reservation.time_remaining / 60) : 0;
                                    const minutes = reservation.time_remaining ? Math.floor(reservation.time_remaining % 60) : 0;
                                    
                                    return (
                                        <SidebarMenuItem key={reservation.id}>
                                            <SidebarMenuButton
                                                onClick={() => router.visit(`/labs/${reservation.lab_id}/workspace`, {
                                                    method: 'get',
                                                    preserveScroll: true
                                                })}
                                                className="group relative w-full px-3 py-2.5 rounded-md hover:bg-accent transition-colors"
                                            >
                                                <div className="flex items-start gap-3 w-full min-w-0">
                                                    <div className="w-2.5 h-2.5 rounded-full bg-green-500 group-hover:bg-green-400 transition-colors flex-shrink-0 mt-1.5" />
                                                    <div className="flex-1 min-w-0 space-y-1">
                                                        <div className="font-medium text-sm truncate leading-tight">
                                                            {reservation.lab_title}
                                                        </div>
                                                        {reservation.time_remaining && (
                                                            <div className="text-xs text-muted-foreground leading-tight">
                                                                <span className="inline-flex items-center gap-1">
                                                                    {hours > 0 && (
                                                                        <>
                                                                            <span>{hours}h</span>
                                                                            {minutes > 0 && <span>{minutes}m</span>}
                                                                        </>
                                                                    )}
                                                                    {hours === 0 && minutes > 0 && (
                                                                        <span>{minutes}m</span>
                                                                    )}
                                                                    <span className="text-muted-foreground/70">restant</span>
                                                                </span>
                                                            </div>
                                                        )}
                                                    </div>
                                                    <ExternalLink className="h-3.5 w-3.5 opacity-0 group-hover:opacity-60 transition-opacity flex-shrink-0 mt-1 text-muted-foreground" />
                                                </div>
                                            </SidebarMenuButton>
                                        </SidebarMenuItem>
                                    );
                                })}
                                {activeLabs.length > 3 && (
                                    <SidebarMenuItem className="pt-1">
                                        <SidebarMenuButton asChild>
                                            <Link 
                                                href="/labs?filter=reserved" 
                                                className="text-xs text-muted-foreground hover:text-foreground transition-colors px-3 py-2 rounded-md hover:bg-accent w-full text-center"
                                            >
                                                +{activeLabs.length - 3} autre{activeLabs.length - 3 > 1 ? 's' : ''} lab{activeLabs.length - 3 > 1 ? 's' : ''}
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
