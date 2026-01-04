import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { Card, CardTitle, CardContent, CardHeader, } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { Button } from '@/components/ui/button';

import {
    AlertCircle,
    Calendar,
    Eye,
    Clock,
    Users,
    ExternalLink,
    Zap,
} from 'lucide-react';  

import { motion, Variants } from 'framer-motion';
import { useState, useMemo, useEffect } from 'react';
import { Link, router } from '@inertiajs/react';
import { formatDuration } from '@/lib/utils';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Labs', href: '/labs' },
    { title: 'My Reservations', href: '/labs/reservations' }
];

type UserReservation = {
    id: string;
    lab_id: string;
    lab_title: string;
    lab_description: string;
    start_at: string;
    end_at: string;
    status: string;
    duration_hours: number | null;
    time_remaining: number | null;
    created_at: string;
};

type Props = {
    userReservations: UserReservation[];
    userActiveReservations: UserReservation[];
    error?: string;
};

export default function UserReservations() {
    const { userReservations = [], userActiveReservations = [], error } = usePage<Props>().props;
    const [activeTab, setActiveTab] = useState<'active' | 'all' | 'expired'>('active');
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        const timer = setTimeout(() => setIsLoading(false), 500);
        return () => clearTimeout(timer);
    }, []);

    // Filter reservations based on status and time
    const filteredReservations = useMemo(() => {
        const now = new Date();

        return userReservations.filter(reservation => {
            const endTime = new Date(reservation.end_at);
            const isActive = endTime > now;

            switch (activeTab) {
                case 'active':
                    return isActive;
                case 'expired':
                    return !isActive;
                default:
                    return true;
            }
        });
    }, [userReservations, activeTab]);

    // Get connected users count for each lab (simulated for now)
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    const getConnectedUsersCount = (labId: string) => {
        // This would come from backend in real implementation
        return Math.floor(Math.random() * 3) + 1; // Simulated 1-3 users
    };

    const getStatusBadge = (status: string, isActive: boolean) => {
        if (isActive) {
            return (
                <Badge className="bg-green-500 hover:bg-green-600 text-white border-0">
                    <div className="w-2 h-2 rounded-full bg-white mr-1.5 animate-pulse" />
                    Active
                </Badge>
            );
        }

        switch (status) {
            case 'active':
                return <Badge className="bg-[hsl(var(--chart-3))] hover:bg-[hsl(var(--chart-3))/80] text-white border-0">Confirmed</Badge>;
            case 'pending':
                return <Badge variant="secondary" className="bg-[hsl(var(--chart-2))] hover:bg-[hsl(var(--chart-2))/80] text-white border-0">Pending</Badge>;
            case 'expired':
                return <Badge variant="destructive" className="border-0">Expired</Badge>;
            default:
                return <Badge variant="outline">{status}</Badge>;
        }
    };

    const containerVariants = {
        hidden: { opacity: 0 },
        visible: {
            opacity: 1,
            transition: {
                staggerChildren: 0.08
            }
        }
    };

    const cardVariants: Variants = {
        hidden: { opacity: 0, y: 20 },
        visible: {
            opacity: 1,
            y: 0,
            transition: {
                type: "spring",
                stiffness: 100,
                damping: 12
            }
        },
        hover: {
            y: -4,
            transition: {
                duration: 0.2,
                type: "tween"
            }
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="My Reservations" />

            <motion.div
                className="flex h-full flex-1 flex-col gap-8 overflow-y-auto p-6"
                initial="hidden"
                animate="visible"
                variants={containerVariants}
            >
                {/* Header Section */}
                <motion.div variants={cardVariants} className="relative">
                    <div className="absolute inset-0 bg-gradient-to-r from-primary/5 via-transparent to-accent/5 rounded-2xl blur-3xl" />
                    <div className="relative p-8 rounded-2xl bg-card/80 backdrop-blur-sm border border-border/50 shadow-lg">
                        <div className="flex items-center gap-3 mb-4">
                            <div className="w-12 h-12 rounded-xl bg-gradient-to-br from-primary/20 to-primary/5 flex items-center justify-center border border-primary/20">
                                <Calendar className="h-6 w-6 text-primary" />
                            </div>
                            <div>
                                <h1 className="text-4xl font-bold tracking-tight bg-gradient-to-r from-foreground via-foreground to-foreground/70 bg-clip-text text-transparent">
                                    My Reservations
                                </h1>
                                <p className="text-muted-foreground text-lg mt-1">
                                    Manage your lab reservations and access active sessions
                                </p>
                            </div>
                        </div>

                        {/* Reservation Stats */}
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
                            <div className="p-4 rounded-xl bg-primary/10 border border-primary/20">
                                <div className="text-2xl font-bold text-primary">{userActiveReservations.length}</div>
                                <div className="text-sm text-muted-foreground">Active Sessions</div>
                            </div>
                            <div className="p-4 rounded-xl bg-green-500/10 border border-green-500/20">
                                <div className="text-2xl font-bold text-green-600">{userReservations.filter(r => r.status === 'active').length}</div>
                                <div className="text-sm text-muted-foreground">Confirmed</div>
                            </div>
                            <div className="p-4 rounded-xl bg-yellow-500/10 border border-yellow-500/20">
                                <div className="text-2xl font-bold text-yellow-600">{userReservations.filter(r => r.status === 'pending').length}</div>
                                <div className="text-sm text-muted-foreground">Pending</div>
                            </div>
                            <div className="p-4 rounded-xl bg-red-500/10 border border-red-500/20">
                                <div className="text-2xl font-bold text-red-600">{userReservations.filter(r => r.status === 'expired').length}</div>
                                <div className="text-sm text-muted-foreground">Expired</div>
                            </div>
                        </div>
                    </div>
                </motion.div>

                {/* Tabs */}
                <motion.div variants={cardVariants}>
                    <div className="flex space-x-1 bg-muted/50 p-1 rounded-xl w-fit">
                        {[
                            { key: 'active', label: 'Active Sessions', icon: Zap },
                            { key: 'all', label: 'All Reservations', icon: Calendar },
                            { key: 'expired', label: 'History', icon: Clock }
                        ].map(tab => (
                            <Button
                                key={tab.key}
                                variant={activeTab === tab.key ? 'default' : 'ghost'}
                                size="sm"
                                className={`h-10 px-4 ${activeTab === tab.key ? 'bg-background shadow-sm' : ''}`}
                                onClick={() => setActiveTab(tab.key as 'active' | 'all' | 'expired')}
                            >
                                <tab.icon className="h-4 w-4 mr-2" />
                                {tab.label}
                            </Button>
                        ))}
                    </div>
                </motion.div>

                {/* Error Display */}
                {error && (
                    <motion.div
                        variants={cardVariants}
                        className="p-6 rounded-xl bg-destructive/10 border border-destructive/20"
                    >
                        <div className="flex items-center gap-3">
                            <AlertCircle className="h-6 w-6 text-destructive" />
                            <div>
                                <h3 className="font-semibold text-destructive">Error</h3>
                                <p className="text-sm text-muted-foreground mt-1">{error}</p>
                            </div>
                        </div>
                    </motion.div>
                )}

                {/* Reservations List */}
                <motion.div variants={cardVariants}>
                    {isLoading ? (
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {[...Array(6)].map((_, i) => (
                                <Card key={i} className="overflow-hidden">
                                    <CardHeader className="pb-3">
                                        <Skeleton className="h-5 w-32 mb-2" />
                                        <Skeleton className="h-4 w-20" />
                                    </CardHeader>
                                    <CardContent>
                                        <div className="space-y-3">
                                            <Skeleton className="h-4 w-full" />
                                            <Skeleton className="h-4 w-3/4" />
                                            <div className="flex gap-2">
                                                <Skeleton className="h-9 w-24" />
                                                <Skeleton className="h-9 w-20" />
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    ) : filteredReservations.length === 0 ? (
                        <Card className="p-12 text-center">
                            <div className="w-16 h-16 rounded-full bg-muted flex items-center justify-center mx-auto mb-4">
                                <Calendar className="h-8 w-8 text-muted-foreground" />
                            </div>
                            <h3 className="text-xl font-semibold mb-2">
                                {activeTab === 'active' ? 'No Active Sessions' :
                                 activeTab === 'expired' ? 'No Past Reservations' : 'No Reservations'}
                            </h3>
                            <p className="text-muted-foreground mb-6">
                                {activeTab === 'active' ? 'You don\'t have any active lab sessions.' :
                                 activeTab === 'expired' ? 'You haven\'t had any past reservations.' :
                                 'You haven\'t made any reservations yet.'}
                            </p>
                            <Button asChild>
                                <Link href="/labs">Browse Available Labs</Link>
                            </Button>
                        </Card>
                    ) : (
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {filteredReservations.map((reservation, _index) => { // eslint-disable-line @typescript-eslint/no-unused-vars
                                const isActive = new Date(reservation.end_at) > new Date();
                                const connectedUsers = getConnectedUsersCount(reservation.lab_id);

                                return (
                                    <motion.div key={reservation.id} variants={cardVariants}>
                                        <Card className={`group relative overflow-hidden transition-all duration-300 hover:shadow-lg ${
                                            isActive ? 'border-primary/20 bg-primary/5' : 'border-border'
                                        }`}>
                                            {/* Status stripe */}
                                            <div className={`h-1 w-full ${
                                                isActive ? 'bg-gradient-to-r from-green-500 to-green-400' : 'bg-muted'
                                            }`} />

                                            <CardHeader className="pb-3">
                                                <div className="flex items-start justify-between">
                                                    <div className="flex-1 min-w-0">
                                                        <CardTitle className="text-lg font-semibold truncate group-hover:text-primary transition-colors">
                                                            {reservation.lab_title}
                                                        </CardTitle>
                                                        <div className="flex items-center gap-2 mt-2">
                                                            {getStatusBadge(reservation.status, isActive)}
                                                        </div>
                                                    </div>

                                                    {/* Connected users indicator */}
                                                    <div className="flex items-center gap-1 px-2 py-1 rounded-full bg-muted/50">
                                                        <Users className="h-3 w-3 text-muted-foreground" />
                                                        <span className="text-xs font-medium">{connectedUsers}</span>
                                                    </div>
                                                </div>
                                            </CardHeader>

                                            <CardContent className="space-y-4">
                                                {/* Time Information */}
                                                <div className="space-y-2">
                                                    <div className="flex items-center justify-between text-sm">
                                                        <span className="text-muted-foreground">Duration:</span>
                                                        <Badge variant="outline" className="font-mono">
                                                            {formatDuration(reservation.duration_hours)}
                                                        </Badge>
                                                    </div>

                                                    {isActive && reservation.time_remaining && (
                                                        <div className="flex items-center justify-between text-sm">
                                                            <span className="text-muted-foreground">Time left:</span>
                                                            <Badge
                                                                variant={reservation.time_remaining < 60 ? "destructive" : "secondary"}
                                                                className={`font-mono ${reservation.time_remaining < 60 ? 'animate-pulse' : ''}`}
                                                            >
                                                                {Math.floor(reservation.time_remaining / 60).toString().padStart(2, '0')}h {reservation.time_remaining % 60}m
                                                            </Badge>
                                                        </div>
                                                    )}
                                                </div>

                                                {/* Connected Users Info */}
                                                <div className="p-3 rounded-lg bg-muted/30 border border-border/50">
                                                    <div className="flex items-center justify-between mb-2">
                                                        <span className="text-sm font-medium">Lab Activity</span>
                                                        <div className="flex items-center gap-1">
                                                            <div className="w-2 h-2 rounded-full bg-green-500 animate-pulse" />
                                                            <span className="text-xs text-muted-foreground">Live</span>
                                                        </div>
                                                    </div>
                                                    <div className="flex items-center justify-between">
                                                        <span className="text-sm text-muted-foreground">Connected Users:</span>
                                                        <div className="flex items-center gap-1">
                                                            <Users className="h-4 w-4 text-muted-foreground" />
                                                            <span className="font-semibold">{connectedUsers}</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                {/* Actions */}
                                                <div className="flex gap-2 pt-2 border-t border-border/50">
                                                    {isActive && (
                                                        <Button
                                                            size="sm"
                                                            className="flex-1 bg-gradient-to-r from-primary to-primary/90 hover:from-primary/90 hover:to-primary"
                                                            onClick={() => router.visit(`/labs/${reservation.lab_id}/workspace`)}
                                                        >
                                                            <ExternalLink className="h-4 w-4 mr-2" />
                                                            Access Lab
                                                        </Button>
                                                    )}

                                                    <Button variant="outline" size="sm" className="flex-1">
                                                        <Eye className="h-4 w-4 mr-2" />
                                                        View Details
                                                    </Button>
                                                </div>
                                            </CardContent>
                                        </Card>
                                    </motion.div>
                                );
                            })}
                        </div>
                    )}
                </motion.div>
            </motion.div>
        </AppLayout>
    );
}
