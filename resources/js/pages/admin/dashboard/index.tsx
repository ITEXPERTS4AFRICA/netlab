import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';
import { useEffect, useMemo, useState } from 'react';
import {
    Activity,
    Calendar,
    CalendarDays,
    CheckCircle2,
    PauseCircle,
    PlayCircle,
    RefreshCw,
    TrendingUp,
    Users,
    XCircle,
} from 'lucide-react';

interface Summary {
    total_users: number;
    active_users: number;
    active_reservations: number;
    pending_reservations: number;
    weekly_reservations: number;
    monthly_revenue_cents: number;
}

interface PipelineStage {
    status: 'pending' | 'active' | 'completed' | 'cancelled';
    count: number;
    value: number;
}

interface UpcomingReservation {
    id: number;
    lab_title: string;
    user_name: string;
    status: string;
    start_at: string;
    end_at: string;
    duration_hours: number | null;
}

interface GanttRange {
    start: string;
    end: string;
}

interface GanttItem {
    id: number;
    lab_title: string;
    user_name: string;
    status: string;
    start_at: string;
    end_at: string;
    estimated_cents: number;
}

interface ActivityItem {
    id: string;
    type: 'reservation' | 'payment';
    title: string;
    status: string;
    success: boolean;
    timestamp: string;
    description: string;
}

interface DashboardProps {
    summary: Summary;
    pipeline: PipelineStage[];
    upcomingReservations: UpcomingReservation[];
    gantt: {
        range: GanttRange;
        items: GanttItem[];
    };
    activityFeed: ActivityItem[];
    actionStats: {
        success: number;
        failed: number;
    };
}

const STATUS_LABELS: Record<string, string> = {
    pending: 'En attente',
    active: 'Active',
    completed: 'Terminée',
    cancelled: 'Annulée',
};

const STATUS_COLORS: Record<string, string> = {
    pending: 'bg-yellow-500',
    active: 'bg-green-500',
    completed: 'bg-blue-500',
    cancelled: 'bg-red-500',
};

const formatCurrency = (cents: number | undefined | null) => {
    if (!cents) return '0 XOF';
    return `${(cents / 100).toLocaleString('fr-FR')} XOF`;
};

const formatDate = (value?: string) => {
    if (!value) return 'N/A';
    return new Date(value).toLocaleString('fr-FR', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const statusBadge = (status: string) => (
    <Badge className={STATUS_COLORS[status] ?? 'bg-muted'}>{STATUS_LABELS[status] ?? status}</Badge>
);

const GanttChart = ({ range, items }: { range: GanttRange; items: GanttItem[] }) => {
    const start = new Date(range.start);
    const end = new Date(range.end);
    const total = Math.max(1, end.getTime() - start.getTime());

    const clamp = (value: number) => Math.max(0, Math.min(100, value));

    const getOffset = (value: string) => {
        const date = new Date(value);
        return clamp(((date.getTime() - start.getTime()) / total) * 100);
    };

    const getWidth = (startValue: string, endValue: string) => {
        const width = getOffset(endValue) - getOffset(startValue);
        return clamp(width <= 0 ? 2 : width);
    };

    return (
        <div className="space-y-4">
            <div className="flex items-center justify-between text-xs text-muted-foreground">
                <span>{start.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' })}</span>
                <span>{end.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' })}</span>
            </div>
            <div className="space-y-4">
                {items.length === 0 && (
                    <p className="text-sm text-muted-foreground text-center py-6">
                        Aucune activité planifiée sur la période sélectionnée.
                    </p>
                )}
                {items.map((item) => {
                    const offset = getOffset(item.start_at);
                    const width = getWidth(item.start_at, item.end_at);
                    const color = STATUS_COLORS[item.status] ?? 'bg-primary';

                    return (
                        <div key={item.id} className="space-y-1">
                            <div className="flex items-center justify-between text-xs text-muted-foreground">
                                <span className="font-medium truncate max-w-[200px]">{item.lab_title}</span>
                                <span>{STATUS_LABELS[item.status] ?? item.status}</span>
                            </div>
                            <div className="relative h-6 rounded bg-muted">
                                <div
                                    className={`absolute top-0 h-6 rounded text-[11px] font-medium text-white flex items-center px-2 ${color}`}
                                    style={{
                                        left: `${offset}%`,
                                        width: `${width}%`,
                                    }}
                                >
                                    {item.user_name}
                                </div>
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>
    );
};

export default function AdminDashboard({
    summary,
    pipeline,
    upcomingReservations,
    gantt,
    activityFeed,
    actionStats,
}: DashboardProps) {
    const [autoRefresh, setAutoRefresh] = useState(true);

    const totalActivities = actionStats.success + actionStats.failed;
    const successRate = totalActivities ? Math.round((actionStats.success / totalActivities) * 100) : 0;

    useEffect(() => {
        if (!autoRefresh) return;

        const interval = setInterval(() => {
            router.reload({
                only: ['summary', 'pipeline', 'upcomingReservations', 'gantt', 'activityFeed', 'actionStats'],
                preserveScroll: true,
            });
        }, 60000);

        return () => clearInterval(interval);
    }, [autoRefresh]);

    const handleManualRefresh = () => {
        router.reload({
            only: ['summary', 'pipeline', 'upcomingReservations', 'gantt', 'activityFeed', 'actionStats'],
            preserveScroll: true,
        });
    };

    const summaryCards = useMemo(() => [
        {
            title: 'Utilisateurs',
            value: summary.total_users,
            description: `${summary.active_users} actifs`,
            icon: Users,
        },
        {
            title: 'Réservations actives',
            value: summary.active_reservations,
            description: `${summary.pending_reservations} en attente`,
            icon: Activity,
        },
        {
            title: 'Réservations semaine',
            value: summary.weekly_reservations,
            description: 'Sur la semaine en cours',
            icon: CalendarDays,
        },
        {
            title: 'Revenus mensuels',
            value: formatCurrency(summary.monthly_revenue_cents),
            description: 'Paiements confirmés',
            icon: TrendingUp,
        },
    ], [summary]);

    return (
        <AppLayout>
            <Head title="Tableau de bord CRM" />
            <div className="container mx-auto py-8 space-y-6">
                <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 className="text-3xl font-bold flex items-center gap-2">
                            <Activity className="h-7 w-7 text-primary" />
                            Tableau de bord de l'Administrateur
                        </h1>
                        <p className="text-muted-foreground">
                            Vue 360° des interactions, calendrier en temps réel et suivi des actions critiques.
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button
                            variant={autoRefresh ? 'default' : 'outline'}
                            size="sm"
                            onClick={() => setAutoRefresh((value) => !value)}
                        >
                            {autoRefresh ? (
                                <>
                                    <PlayCircle className="h-4 w-4 mr-2" />
                                    Auto-refresh actif
                                </>
                            ) : (
                                <>
                                    <PauseCircle className="h-4 w-4 mr-2" />
                                    Auto-refresh inactif
                                </>
                            )}
                        </Button>
                        <Button variant="outline" size="sm" onClick={handleManualRefresh}>
                            <RefreshCw className="h-4 w-4 mr-2" />
                            Actualiser
                        </Button>
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {summaryCards.map((card) => (
                        <Card key={card.title}>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardDescription>{card.title}</CardDescription>
                                <card.icon className="h-5 w-5 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-3xl font-bold">
                                    {typeof card.value === 'number' ? card.value.toLocaleString('fr-FR') : card.value}
                                </div>
                                <p className="text-xs text-muted-foreground mt-2">{card.description}</p>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Calendar className="h-5 w-5" />
                                Pipeline des réservations
                            </CardTitle>
                            <CardDescription>Distribution des deals par statut et valeur estimée</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                {pipeline.map((stage) => (
                                    <div
                                        key={stage.status}
                                        className="rounded-lg border border-border/60 p-3 space-y-2"
                                    >
                                        <div className="flex items-center justify-between">
                                            <span className="text-sm font-medium">{STATUS_LABELS[stage.status]}</span>
                                            <Badge variant="outline">{stage.count}</Badge>
                                        </div>
                                        <p className="text-2xl font-semibold">{stage.count}</p>
                                        <p className="text-xs text-muted-foreground">
                                            {formatCurrency(stage.value)} pipeline
                                        </p>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <CheckCircle2 className="h-5 w-5 text-green-500" />
                                Taux de réussite des actions
                            </CardTitle>
                            <CardDescription>Dernières opérations (paiements, réservations, annulations)</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <div className="flex items-center justify-between text-sm">
                                    <span>Succès</span>
                                    <span>{actionStats.success.toLocaleString('fr-FR')}</span>
                                </div>
                                <div className="flex items-center justify-between text-sm">
                                    <span>Échecs</span>
                                    <span>{actionStats.failed.toLocaleString('fr-FR')}</span>
                                </div>
                            </div>
                            <div>
                                <div className="flex items-center justify-between text-sm mb-1">
                                    <span>Taux de réussite</span>
                                    <span>{successRate}%</span>
                                </div>
                                <Progress value={successRate} />
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Calcul basé sur les 12 dernières actions enregistrées.
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader className="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <CardTitle className="flex items-center gap-2">
                                <CalendarDays className="h-5 w-5" />
                                Gantt / Calendrier des activités
                            </CardTitle>
                            <CardDescription>
                                Projection des réservations sur les 7 prochains jours, mise à jour en temps réel.
                            </CardDescription>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <GanttChart range={gantt.range} items={gantt.items} />
                    </CardContent>
                </Card>

                <div className="grid gap-4 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Événements imminents</CardTitle>
                            <CardDescription>Prochaines réservations clients / labs</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                {upcomingReservations.length === 0 && (
                                    <p className="text-sm text-muted-foreground">
                                        Aucun rendez-vous planifié.
                                    </p>
                                )}
                                {upcomingReservations.map((reservation) => (
                                    <div
                                        key={reservation.id}
                                        className="flex items-start justify-between rounded-lg border border-border/60 p-3"
                                    >
                                        <div className="space-y-1">
                                            <p className="font-medium">{reservation.lab_title}</p>
                                            <p className="text-xs text-muted-foreground">
                                                {reservation.user_name}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {formatDate(reservation.start_at)} → {formatDate(reservation.end_at)}
                                            </p>
                                        </div>
                                        {statusBadge(reservation.status)}
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Flux d’activités</CardTitle>
                            <CardDescription>Historique des dernières actions (réussites / échecs)</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                {activityFeed.length === 0 && (
                                    <p className="text-sm text-muted-foreground">
                                        Aucune activité récente.
                                    </p>
                                )}
                                {activityFeed.map((activity) => (
                                    <div
                                        key={activity.id}
                                        className="flex items-start justify-between rounded-lg border border-border/60 p-3"
                                    >
                                        <div>
                                            <div className="flex items-center gap-2">
                                                <p className="font-medium">{activity.title}</p>
                                                <Badge variant="outline" className={activity.success ? 'text-green-600' : 'text-red-600'}>
                                                    {activity.success ? 'Succès' : 'Échec'}
                                                </Badge>
                                            </div>
                                            <p className="text-xs text-muted-foreground">
                                                {STATUS_LABELS[activity.status] ?? activity.status} • {activity.description}
                                            </p>
                                        </div>
                                        <span className="text-xs text-muted-foreground">
                                            {formatDate(activity.timestamp)}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}

