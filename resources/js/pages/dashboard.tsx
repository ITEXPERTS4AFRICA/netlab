import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Activity,
    Users,
    Clock,
    CheckCircle,
    AlertCircle,
    Crown,
    Play,
    X,
    ClockIcon,
    Calendar
} from 'lucide-react';
import { motion, Variants } from 'framer-motion';
import { useEffect, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

type CmlLab = {
    id: string | number;
    title?: string;
    description?: string;
    state: string;
};

type DashboardProps = {
    stats: {
        totalLabs: number;
        availableLabs: number;
        occupiedLabs: number;
        userReservations: number;
    };
    activeReservations: Array<{
        id: string;
        lab_title: string;
        user_name: string;
        user_email: string;
        start_at: string;
        end_at: string;
        duration_hours: number | null;
    }>;
    userReservations: Array<{
        id: string;
        lab_title: string;
        start_at: string;
        end_at: string;
        status: string;
        created_at: string;
    }>;
    cmlLabs: CmlLab[];
    cmlSystemHealth: Record<string, unknown> | null;
};

export default function Dashboard() {
    const { stats, activeReservations, userReservations, cmlLabs } = usePage<DashboardProps>().props;
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        // Simulate initial load
        const timer = setTimeout(() => setIsLoading(false), 1000);
        return () => clearTimeout(timer);
    }, []);

    const getStatusIcon = (status: string) => {
        switch (status) {
            case 'active':
                return <CheckCircle className="h-4 w-4 text-[hsl(var(--chart-3))]" />;
            case 'pending':
                return <ClockIcon className="h-4 w-4 text-[hsl(var(--chart-2))]" />;
            case 'cancelled':
                return <X className="h-4 w-4 text-destructive" />;
            default:
                return <AlertCircle className="h-4 w-4 text-muted-foreground" />;
        }
    };

    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'active':
                return <Badge className="bg-[hsl(var(--chart-3))] hover:bg-[hsl(var(--chart-3))/80] text-white">Active</Badge>;
            case 'pending':
                return <Badge variant="secondary" className="bg-[hsl(var(--chart-2))] hover:bg-[hsl(var(--chart-2))/80] text-white">Pending</Badge>;
            case 'expired':
                return <Badge variant="destructive">Expired</Badge>;
            default:
                return <Badge variant="outline">{status}</Badge>;
        }
    };

    const containerVariants = {
        hidden: { opacity: 0 },
        visible: {
            opacity: 1,
            transition: {
                staggerChildren: 0.1
            }
        }
    };

    const itemVariants: Variants = {
        hidden: { opacity: 0, y: 20 },
        visible: {
            opacity: 1,
            y: 0,
            transition: {
                duration: 0.5,
                type: "spring",
                stiffness: 100,
                damping: 15
            }
        }
    };

    const statsData = [
        {
            title: "Total Labs",
            value: stats.totalLabs,
            description: "All registered labs",
            icon: Crown,
            color: "text-[hsl(var(--chart-1))] bg-[hsl(var(--chart-1)/10)]",
            borderColor: "border-[hsl(var(--chart-1)/20)]"
        },
        {
            title: "Available Labs",
            value: stats.availableLabs,
            description: "Ready for reservation",
            icon: CheckCircle,
            color: "text-[hsl(var(--chart-3))] bg-[hsl(var(--chart-3)/10)]",
            borderColor: "border-[hsl(var(--chart-3)/20)]"
        },
        {
            title: "Occupied Labs",
            value: stats.occupiedLabs,
            description: "Currently in use",
            icon: Users,
            color: "text-[hsl(var(--chart-2))] bg-[hsl(var(--chart-2)/10)]",
            borderColor: "border-[hsl(var(--chart-2)/20)]"
        },
        {
            title: "Your Reservations",
            value: stats.userReservations,
            description: "Active bookings",
            icon: Calendar,
            color: "text-[hsl(var(--chart-4))] bg-[hsl(var(--chart-4)/10)]",
            borderColor: "border-[hsl(var(--chart-4)/20)]"
        }
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <motion.div
                className="flex h-full flex-1 flex-col gap-8 overflow-y-auto p-6"
                initial="hidden"
                animate="visible"
                variants={containerVariants}
            >
                {/* Welcome Section */}
                <motion.div variants={itemVariants} className="flex flex-col gap-2">
                    <motion.h1
                        className="text-4xl font-bold tracking-tight bg-gradient-to-r from-foreground to-foreground/70 bg-clip-text text-transparent"
                        initial={{ opacity: 0, x: -20 }}
                        animate={{ opacity: 1, x: 0 }}
                        transition={{ duration: 0.6, delay: 0.2 }}
                    >
                        Dashboard
                    </motion.h1>
                    <motion.p
                        className="text-muted-foreground text-lg"
                        initial={{ opacity: 0 }}
                        animate={{ opacity: 1 }}
                        transition={{ duration: 0.6, delay: 0.4 }}
                    >
                        Overview of your lab reservations and system status
                    </motion.p>
                </motion.div>

                {/* Statistics Cards */}
                {isLoading ? (
                    <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                        {[...Array(4)].map((_, i) => (
                            <Card key={i} className="overflow-hidden">
                                <CardHeader className="pb-3">
                                    <Skeleton className="h-4 w-24" />
                                    <Skeleton className="h-8 w-16" />
                                </CardHeader>
                                <CardContent>
                                    <Skeleton className="h-6 w-20 mb-2" />
                                    <Skeleton className="h-4 w-32" />
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                ) : (
                    <motion.div
                        className="grid gap-6 md:grid-cols-2 lg:grid-cols-4"
                        variants={containerVariants}
                    >
                        {statsData.map((stat, index) => (
                            <motion.div key={stat.title} variants={itemVariants}>
                                <Card className="group relative overflow-hidden border-0 bg-gradient-to-br from-card to-card/50 hover:shadow-lg transition-all duration-300 hover:scale-[1.02]">
                                    <div className={`absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-300 bg-gradient-to-br ${stat.color.split(' ')[0].replace('text-', 'from-').replace('/10', '/5')} to-transparent`} />
                                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-4 relative z-10">
                                        <CardTitle className="text-sm font-medium text-muted-foreground">{stat.title}</CardTitle>
                                        <div className={`p-2 rounded-lg ${stat.color} border ${stat.borderColor} transition-transform duration-300 group-hover:scale-110`}>
                                            <stat.icon className="h-4 w-4" />
                                        </div>
                                    </CardHeader>
                                    <CardContent className="relative z-10">
                                        <motion.div
                                            className="text-3xl font-bold mb-2"
                                            initial={{ scale: 0 }}
                                            animate={{ scale: 1 }}
                                            transition={{
                                                type: "spring",
                                                stiffness: 260,
                                                damping: 20,
                                                delay: index * 0.1
                                            }}
                                        >
                                            {stat.value}
                                        </motion.div>
                                        <p className="text-xs text-muted-foreground">
                                            {stat.description}
                                        </p>
                                    </CardContent>
                                </Card>
                            </motion.div>
                        ))}
                    </motion.div>
                )}

                {/* Main Content Grid */}
                <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    {/* Active Reservations */}
                    <Card className="col-span-2">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Activity className="h-5 w-5" />
                                Active Reservations
                            </CardTitle>
                            <CardDescription>
                                Currently occupied labs with active reservations
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="max-h-[300px] overflow-y-auto">
                                {activeReservations.length === 0 ? (
                                    <div className="flex flex-col items-center justify-center py-8 text-center">
                                        <CheckCircle className="h-12 w-12 text-green-500 mb-4" />
                                        <h3 className="text-lg font-medium">All Labs Available</h3>
                                        <p className="text-sm text-muted-foreground">
                                            No active reservations at the moment
                                        </p>
                                    </div>
                                ) : (
                                    <div className="space-y-4">
                                        {activeReservations.map((reservation) => (
                                            <div key={reservation.id} className="flex items-center justify-between p-4 border rounded-lg">
                                                <div className="flex items-center space-x-4">
                                                    <Avatar className="h-10 w-10">
                                                        <AvatarFallback>
                                                            {reservation.user_name.split(' ').map(n => n[0]).join('').toUpperCase()}
                                                        </AvatarFallback>
                                                    </Avatar>
                                                    <div className="space-y-1">
                                                        <div className="flex items-center gap-2">
                                                            <p className="font-medium">{reservation.lab_title}</p>
                                                            <Badge variant="outline" className="text-xs">Active</Badge>
                                                        </div>
                                                        <p className="text-sm text-muted-foreground">
                                                            Reserved by {reservation.user_name}
                                                        </p>
                                                        <div className="flex items-center gap-4 text-xs text-muted-foreground">
                                                            <span>From: {reservation.start_at}</span>
                                                            <span>To: {reservation.end_at}</span>
                                                            {reservation.duration_hours && (
                                                                <Badge variant="secondary" className="text-xs">
                                                                    {reservation.duration_hours}h
                                                                </Badge>
                                                            )}
                                                        </div>
                                                    </div>
                                                </div>
                                                <div className="flex flex-col items-end space-y-1">
                                                    <Clock className="h-4 w-4 text-muted-foreground" />
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Your Recent Reservations */}
                    <Card className="col-span-1">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Calendar className="h-5 w-5" />
                                Your Reservations
                            </CardTitle>
                            <CardDescription>
                                Your recent lab bookings
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="max-h-[300px] overflow-y-auto">
                                {userReservations.length === 0 ? (
                                    <div className="flex flex-col items-center justify-center py-8 text-center">
                                        <Calendar className="h-12 w-12 text-gray-400 mb-4" />
                                        <h3 className="text-lg font-medium">No Reservations</h3>
                                        <p className="text-sm text-muted-foreground">
                                            You haven't made any reservations yet
                                        </p>
                                    </div>
                                ) : (
                                    <div className="space-y-3">
                                        {userReservations.map((reservation) => (
                                            <div key={reservation.id} className="flex items-start gap-3 p-3 rounded-lg border">
                                                <div className="flex-1 min-w-0">
                                                    <div className="flex items-center gap-2 mb-1">
                                                        <p className="font-medium text-sm truncate">{reservation.lab_title}</p>
                                                        {getStatusIcon(reservation.status)}
                                                    </div>
                                                    <div className="flex items-center gap-2 mb-1">
                                                        {getStatusBadge(reservation.status)}
                                                    </div>
                                                    <p className="text-xs text-muted-foreground">
                                                        {reservation.start_at} - {reservation.end_at}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground mt-1">
                                                        Reserved {reservation.created_at}
                                                    </p>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* CML Labs Overview */}
                {cmlLabs.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Play className="h-5 w-5" />
                                CML System Status
                            </CardTitle>
                            <CardDescription>
                                Latest status from Cisco Modeling Labs
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                {cmlLabs.slice(0, 6).map((lab, index) => (
                                    <div key={index} className="p-4 border rounded-lg">
                                        <div className="flex items-center justify-between mb-2">
                                            <h4 className="font-medium truncate">{lab.title || `Lab ${lab.id}`}</h4>
                                            <Badge variant={lab.state === 'STOPPED' ? 'secondary' : 'default'}>
                                                {lab.state}
                                            </Badge>
                                        </div>
                                        <p className="text-sm text-muted-foreground">
                                            {lab.description || 'No description available'}
                                        </p>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}
            </motion.div>
        </AppLayout>
    );
}
