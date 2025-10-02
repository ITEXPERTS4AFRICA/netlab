import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
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
    const { stats, activeReservations, userReservations, cmlLabs,  } = usePage<DashboardProps>().props;

    const getStatusIcon = (status: string) => {
        switch (status) {
            case 'active':
                return <CheckCircle className="h-4 w-4 text-green-500" />;
            case 'pending':
                return <ClockIcon className="h-4 w-4 text-yellow-500" />;
            case 'cancelled':
                return <X className="h-4 w-4 text-red-500" />;
            default:
                return <AlertCircle className="h-4 w-4 text-gray-500" />;
        }
    };

    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'active':
                return <Badge variant="default" className="bg-green-500 hover:bg-green-600">Active</Badge>;
            case 'pending':
                return <Badge variant="secondary" className="bg-yellow-500 hover:bg-yellow-600 text-black">Pending</Badge>;
            case 'expired':
                return <Badge variant="destructive">Expired</Badge>;
            default:
                return <Badge variant="outline">{status}</Badge>;
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-y-auto p-6">
                {/* Welcome Section */}
                <div className="flex flex-col gap-2">
                    <h1 className="text-3xl font-bold tracking-tight">Dashboard</h1>
                    <p className="text-muted-foreground">
                        Overview of your lab reservations and system status
                    </p>
                </div>

                {/* Statistics Cards */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Labs</CardTitle>
                            <Crown className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.totalLabs}</div>
                            <p className="text-xs text-muted-foreground">
                                All registered labs
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Available Labs</CardTitle>
                            <CheckCircle className="h-4 w-4 text-green-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-green-600">{stats.availableLabs}</div>
                            <p className="text-xs text-muted-foreground">
                                Ready for reservation
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Occupied Labs</CardTitle>
                            <Users className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-orange-600">{stats.occupiedLabs}</div>
                            <p className="text-xs text-muted-foreground">
                                Currently in use
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Your Reservations</CardTitle>
                            <Calendar className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-blue-600">{stats.userReservations}</div>
                            <p className="text-xs text-muted-foreground">
                                Active bookings
                            </p>
                        </CardContent>
                    </Card>
                </div>

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
            </div>
        </AppLayout>
    );
}
