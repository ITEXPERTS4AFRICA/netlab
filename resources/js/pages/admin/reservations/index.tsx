import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
    Calendar, Search, Filter, Activity, TrendingUp, Clock,
    AlertCircle, DollarSign, Eye, RefreshCw
} from 'lucide-react';
import { useState } from 'react';

interface Reservation {
    id: number;
    lab_title: string;
    lab_id: number;
    user_name: string;
    user_email: string;
    user_id: string;
    start_at: string;
    end_at: string;
    status: 'active' | 'pending' | 'completed' | 'cancelled';
    estimated_cents: number;
    has_payment: boolean;
    payment_status: string;
    created_at: string;
    updated_at: string;
    duration_hours: number | null;
}

interface ActiveReservation {
    id: number;
    lab_title: string;
    user_name: string;
    user_email: string;
    start_at: string;
    end_at: string;
    estimated_cents: number;
    has_payment: boolean;
    duration_hours: number | null;
    time_remaining_minutes: number | null;
}

interface ExpiredPendingReservation {
    id: number;
    lab_title: string;
    user_name: string;
    created_at: string;
    estimated_cents: number;
    expired_minutes_ago: number;
}

interface Lab {
    id: number;
    lab_title: string;
}

interface User {
    id: string;
    name: string;
    email: string;
}

interface Props {
    reservations: Reservation[];
    pagination: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    stats: {
        total: number;
        active: number;
        pending: number;
        pending_expired: number;
        completed: number;
        cancelled: number;
        total_revenue_cents: number;
        today_reservations: number;
        week_reservations: number;
        month_reservations: number;
    };
    statusStats: Record<string, number>;
    labStats: Array<{
        lab_id: number;
        lab_title: string;
        count: number;
    }>;
    paymentStats: {
        total_paid: number;
        total_pending: number;
        total_failed: number;
        total_revenue_cents: number;
    };
    activeReservations: ActiveReservation[];
    expiredPendingReservations: ExpiredPendingReservation[];
    filters: {
        status?: string;
        lab_id?: number;
        user_id?: string;
        date_from?: string;
        date_to?: string;
    };
    labs: Lab[];
    users: User[];
}

export default function ReservationsIndex({
    reservations,
    pagination,
    stats,
    statusStats,
    labStats,
    paymentStats,
    activeReservations,
    expiredPendingReservations,
    filters,
    labs,
    users,
}: Props) {
    const [status, setStatus] = useState(filters.status || 'all');
    const [labId, setLabId] = useState(filters.lab_id?.toString() || 'all');
    const [userId, setUserId] = useState(filters.user_id || 'all');
    const [dateFrom, setDateFrom] = useState(filters.date_from || '');
    const [dateTo, setDateTo] = useState(filters.date_to || '');
    const [isCleaning, setIsCleaning] = useState(false);

    const handleFilter = () => {
        router.get('/admin/reservations', {
            status: status !== 'all' ? status : undefined,
            lab_id: labId !== 'all' ? parseInt(labId) : undefined,
            user_id: userId !== 'all' ? userId : undefined,
            date_from: dateFrom || undefined,
            date_to: dateTo || undefined,
        }, { preserveState: true });
    };

    const getStatusBadge = (status: string) => {
        const colors: Record<string, string> = {
            'active': 'bg-green-500',
            'pending': 'bg-yellow-500',
            'completed': 'bg-blue-500',
            'cancelled': 'bg-red-500',
        };
        return colors[status] || 'bg-gray-500';
    };

    const getStatusLabel = (status: string) => {
        const labels: Record<string, string> = {
            'active': 'Active',
            'pending': 'En attente',
            'completed': 'Terminée',
            'cancelled': 'Annulée',
        };
        return labels[status] || status;
    };

    const formatPrice = (cents: number) => {
        if (!cents) return 'Gratuit';
        return `${(cents / 100).toLocaleString('fr-FR')} XOF`;
    };

    const formatDuration = (hours: number | null) => {
        if (!hours) return 'N/A';
        if (hours < 1) return `${Math.round(hours * 60)} min`;
        return `${hours.toFixed(1)} h`;
    };

    const formatTimeRemaining = (minutes: number | null) => {
        if (!minutes || minutes < 0) return 'Terminée';
        if (minutes < 60) return `${Math.round(minutes)} min`;
        const hours = Math.floor(minutes / 60);
        const mins = Math.round(minutes % 60);
        return `${hours}h ${mins}min`;
    };

    const handleCleanup = () => {
        if (isCleaning) return;
        setIsCleaning(true);
        router.post('/admin/reservations/cleanup', {}, {
            preserveScroll: true,
            onFinish: () => setIsCleaning(false),
        });
    };

    const formatNumber = (value?: number | null) => {
        if (!value) return '0';
        return value.toLocaleString('fr-FR');
    };

    const overviewCards = [
        {
            title: 'Réservations totales',
            value: formatNumber(stats.total),
            description: `${formatNumber(stats.month_reservations)} créées ce mois`,
            icon: Calendar,
            accent: 'text-primary bg-primary/10',
        },
        {
            title: 'Actives',
            value: formatNumber(stats.active),
            description: `${formatNumber(stats.pending)} en attente`,
            icon: Activity,
            accent: 'text-green-600 bg-green-500/10',
        },
        {
            title: 'Pending',
            value: formatNumber(stats.pending),
            description: `${formatNumber(stats.pending_expired)} expirées à nettoyer`,
            icon: Clock,
            accent: 'text-yellow-600 bg-yellow-500/10',
        },
        {
            title: 'Revenus totaux',
            value: formatPrice(stats.total_revenue_cents),
            description: `${formatNumber(paymentStats.total_paid)} paiements confirmés`,
            icon: DollarSign,
            accent: 'text-blue-600 bg-blue-500/10',
        },
    ];

    const quickStats = [
        {
            label: "Aujourd'hui",
            value: formatNumber(stats.today_reservations),
        },
        {
            label: 'Cette semaine',
            value: formatNumber(stats.week_reservations),
        },
        {
            label: 'Ce mois',
            value: formatNumber(stats.month_reservations),
        },
    ];

    const orderedStatuses: Array<'active' | 'pending' | 'completed' | 'cancelled'> = [
        'active',
        'pending',
        'completed',
        'cancelled',
    ];

    return (
        <AppLayout>
            <Head title="Monitoring des réservations" />

            <div className="container mx-auto py-8 space-y-6">
                {/* En-tête */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold flex items-center gap-2">
                            <Calendar className="h-8 w-8" />
                            Monitoring des réservations
                        </h1>
                        <p className="text-muted-foreground mt-1">
                            Suivi et gestion de toutes les réservations de labs
                        </p>
                    </div>
                </div>

                {/* Aperçu BI */}
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {overviewCards.map((card) => (
                        <Card key={card.title} className="border border-border/60">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <div>
                                    <CardDescription>{card.title}</CardDescription>
                                    <CardTitle className="text-3xl font-semibold">{card.value}</CardTitle>
                                </div>
                                <span className={`rounded-full p-3 ${card.accent}`}>
                                    <card.icon className="h-5 w-5" />
                                </span>
                        </CardHeader>
                            {card.description && (
                                <CardContent>
                                    <p className="text-xs text-muted-foreground">{card.description}</p>
                                </CardContent>
                            )}
                    </Card>
                    ))}
                </div>

                {/* Insights */}
                <div className="grid gap-4 lg:grid-cols-3">
                    <Card className="order-1">
                        <CardHeader>
                            <CardTitle className="text-lg">Répartition des statuts</CardTitle>
                            <CardDescription>Vue consolidée des statuts actifs</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {orderedStatuses.map((status) => (
                                <div key={status} className="flex items-center justify-between rounded-md border border-border/60 px-3 py-2">
                                    <div className="flex items-center gap-2">
                                        <span className={`h-2 w-2 rounded-full ${getStatusBadge(status)}`} />
                                        <span className="text-sm font-medium">{getStatusLabel(status)}</span>
                                    </div>
                                    <span className="text-sm font-semibold">
                                        {formatNumber(statusStats[status] ?? 0)}
                                    </span>
                                    </div>
                                ))}
                            <div className="rounded-md bg-red-500/10 p-3 text-sm font-medium text-red-600">
                                {formatNumber(stats.pending_expired)} réservation(s) pending dépassent 15 minutes
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="order-3 lg:order-2">
                        <CardHeader>
                            <CardTitle className="text-lg">Top Labs réservés</CardTitle>
                            <CardDescription>Volume sur les 5 derniers labs les plus demandés</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                {labStats.slice(0, 5).map((lab) => (
                                    <div key={lab.lab_id} className="flex items-center justify-between">
                                        <div className="flex flex-col">
                                            <span className="text-sm font-medium truncate max-w-[180px]">{lab.lab_title}</span>
                                            <span className="text-xs text-muted-foreground">
                                                {formatNumber(lab.count)} réservation(s)
                                            </span>
                                        </div>
                                        <Badge variant="secondary">{lab.count}</Badge>
                                    </div>
                                ))}
                                {!labStats.length && (
                                    <p className="text-sm text-muted-foreground">
                                        Pas encore de données disponibles
                                    </p>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="order-2 lg:order-3">
                        <CardHeader>
                            <CardTitle className="text-lg flex items-center gap-2">
                                <TrendingUp className="h-4 w-4 text-blue-500" />
                                Volume récent
                            </CardTitle>
                            <CardDescription>Activité sur les 30 derniers jours</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                {quickStats.map((item) => (
                                    <div
                                        key={item.label}
                                        className="flex items-center justify-between rounded-md border border-dashed border-border/60 px-3 py-2"
                                    >
                                        <span className="text-sm text-muted-foreground">{item.label}</span>
                                        <span className="text-base font-semibold">{item.value}</span>
                                </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-6 xl:grid-cols-[2fr_1fr]">
                    <div className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Activity className="h-5 w-5 text-green-600" />
                                Réservations actives ({activeReservations.length})
                            </CardTitle>
                                <CardDescription>Sessions en cours de consommation</CardDescription>
                        </CardHeader>
                        <CardContent>
                                {activeReservations.length > 0 ? (
                            <div className="space-y-2">
                                {activeReservations.map((reservation) => (
                                    <div
                                        key={reservation.id}
                                                className="flex items-center justify-between rounded-lg border border-border/70 p-3"
                                    >
                                        <div className="flex-1">
                                                    <div className="flex items-center gap-2 flex-wrap">
                                                <span className="font-medium">{reservation.lab_title}</span>
                                                {reservation.has_payment ? (
                                                    <Badge className="bg-green-500">Payé</Badge>
                                                ) : (
                                                    <Badge className="bg-yellow-500">Non payé</Badge>
                                                )}
                                            </div>
                                            <p className="text-sm text-muted-foreground">
                                                {reservation.user_name} ({reservation.user_email})
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {new Date(reservation.start_at).toLocaleString('fr-FR')} - {new Date(reservation.end_at).toLocaleString('fr-FR')}
                                            </p>
                                        </div>
                                        <div className="text-right">
                                            <p className="text-sm font-medium">{formatTimeRemaining(reservation.time_remaining_minutes)}</p>
                                            <p className="text-xs text-muted-foreground">{formatDuration(reservation.duration_hours)}</p>
                                            <p className="text-xs text-muted-foreground">{formatPrice(reservation.estimated_cents)}</p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                                ) : (
                                    <p className="text-sm text-muted-foreground">
                                        Aucune réservation active pour le moment.
                                    </p>
                                )}
                        </CardContent>
                    </Card>

                    <Card>
                            <CardHeader className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                            <CardTitle className="flex items-center gap-2">
                                <AlertCircle className="h-5 w-5 text-red-600" />
                                        Pending à nettoyer
                            </CardTitle>
                                    <CardDescription>Réservations en attente depuis plus de 15 minutes</CardDescription>
                                </div>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={isCleaning || expiredPendingReservations.length === 0}
                                    onClick={handleCleanup}
                                >
                                    <RefreshCw className={`h-4 w-4 mr-2 ${isCleaning ? 'animate-spin' : ''}`} />
                                    Nettoyer
                                </Button>
                        </CardHeader>
                        <CardContent>
                                {expiredPendingReservations.length > 0 ? (
                            <div className="space-y-2">
                                {expiredPendingReservations.map((reservation) => (
                                    <div
                                        key={reservation.id}
                                                className="flex items-center justify-between rounded-lg border border-red-200 bg-red-50/70 p-3 dark:border-red-900 dark:bg-red-950/40"
                                    >
                                                <div>
                                                    <p className="font-medium">{reservation.lab_title}</p>
                                            <p className="text-sm text-muted-foreground">
                                                {reservation.user_name}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                Créée: {new Date(reservation.created_at).toLocaleString('fr-FR')}
                                            </p>
                                        </div>
                                        <div className="text-right">
                                            <Badge className="bg-red-500">
                                                Expirée il y a {Math.round(reservation.expired_minutes_ago)} min
                                            </Badge>
                                            <p className="text-xs text-muted-foreground mt-1">
                                                {formatPrice(reservation.estimated_cents)}
                                            </p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                                ) : (
                                    <p className="text-sm text-muted-foreground">
                                        Aucun nettoyage nécessaire.
                                    </p>
                                )}
                        </CardContent>
                    </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Réservations</CardTitle>
                                <CardDescription>
                                    {pagination.total} réservation(s) trouvée(s)
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-4">
                                    {reservations.length > 0 ? reservations.map((reservation) => (
                                        <div
                                            key={reservation.id}
                                            className="flex flex-col gap-4 rounded-lg border border-border/70 p-4 transition hover:bg-muted/40 md:flex-row md:items-center md:justify-between"
                                        >
                                            <div className="flex items-start gap-4 flex-1">
                                                <div className="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center">
                                                    <Calendar className="h-6 w-6 text-primary" />
                                                </div>
                                                <div className="flex-1 min-w-0 space-y-1">
                                                    <div className="flex items-center gap-2 flex-wrap">
                                                        <span className="font-medium">{reservation.lab_title}</span>
                                                        <Badge className={getStatusBadge(reservation.status)}>
                                                            {getStatusLabel(reservation.status)}
                                                        </Badge>
                                                        {reservation.has_payment ? (
                                                            <Badge className="bg-green-500">Payé</Badge>
                                                        ) : reservation.estimated_cents > 0 ? (
                                                            <Badge className="bg-yellow-500">Non payé</Badge>
                                                        ) : null}
                                                    </div>
                                                    <p className="text-sm text-muted-foreground truncate">
                                                        {reservation.user_name} ({reservation.user_email})
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {new Date(reservation.start_at).toLocaleString('fr-FR')} - {new Date(reservation.end_at).toLocaleString('fr-FR')}
                                                    </p>
                                                </div>
                                            </div>
                                            <div className="flex items-center justify-between gap-4 md:justify-end">
                                                <div className="text-right">
                                                    <p className="text-sm font-medium">{formatPrice(reservation.estimated_cents)}</p>
                                                    <p className="text-xs text-muted-foreground">{formatDuration(reservation.duration_hours)}</p>
                                                    <p className="text-xs text-muted-foreground">
                                                        Créée: {new Date(reservation.created_at).toLocaleDateString('fr-FR')}
                                                    </p>
                                                </div>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => router.visit(`/admin/reservations/${reservation.id}`)}
                                                >
                                                    <Eye className="h-4 w-4 mr-2" />
                                                    Détails
                                                </Button>
                                            </div>
                                        </div>
                                    )) : (
                                        <div className="text-center py-8 text-muted-foreground">
                                            Aucune réservation trouvée
                                        </div>
                                    )}
                                </div>

                                {/* Pagination */}
                                {pagination.last_page > 1 && (
                                    <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between mt-6">
                                        <p className="text-sm text-muted-foreground">
                                            Page {pagination.current_page} sur {pagination.last_page}
                                        </p>
                                        <div className="flex gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                disabled={pagination.current_page === 1}
                                                onClick={() => router.get('/admin/reservations', {
                                                    ...filters,
                                                    page: pagination.current_page - 1,
                                                })}
                                            >
                                                Précédent
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                disabled={pagination.current_page === pagination.last_page}
                                                onClick={() => router.get('/admin/reservations', {
                                                    ...filters,
                                                    page: pagination.current_page + 1,
                                                })}
                                            >
                                                Suivant
                                            </Button>
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    <div className="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Filter className="h-5 w-5" />
                                    Filtres dynamiques
                        </CardTitle>
                                <CardDescription>Ajustez les indicateurs en temps réel</CardDescription>
                    </CardHeader>
                    <CardContent>
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <Select value={status} onValueChange={setStatus}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Statut" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Tous les statuts</SelectItem>
                                    <SelectItem value="active">Active</SelectItem>
                                    <SelectItem value="pending">En attente</SelectItem>
                                    <SelectItem value="completed">Terminée</SelectItem>
                                    <SelectItem value="cancelled">Annulée</SelectItem>
                                </SelectContent>
                            </Select>
                            <Select value={labId} onValueChange={setLabId}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Lab" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Tous les labs</SelectItem>
                                    {labs.map((lab) => (
                                        <SelectItem key={lab.id} value={lab.id.toString()}>
                                            {lab.lab_title}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select value={userId} onValueChange={setUserId}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Utilisateur" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Tous les utilisateurs</SelectItem>
                                    {users.map((user) => (
                                        <SelectItem key={user.id} value={user.id}>
                                            {user.name} ({user.email})
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Input
                                type="date"
                                placeholder="Date début"
                                value={dateFrom}
                                onChange={(e) => setDateFrom(e.target.value)}
                            />
                            <Input
                                type="date"
                                placeholder="Date fin"
                                value={dateTo}
                                onChange={(e) => setDateTo(e.target.value)}
                            />
                                    <Button onClick={handleFilter} className="sm:col-span-2">
                                <Search className="h-4 w-4 mr-2" />
                                Filtrer
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                                <CardTitle>Paiements</CardTitle>
                                <CardDescription>Pipeline financier des réservations</CardDescription>
                    </CardHeader>
                            <CardContent className="space-y-3">
                                <div className="flex items-center justify-between">
                                    <span className="text-sm text-muted-foreground">Payés</span>
                                    <Badge className="bg-green-500">{paymentStats.total_paid}</Badge>
                                </div>
                                <div className="flex items-center justify-between">
                                    <span className="text-sm text-muted-foreground">En attente</span>
                                    <Badge className="bg-yellow-500">{paymentStats.total_pending}</Badge>
                                </div>
                                <div className="flex items-center justify-between">
                                    <span className="text-sm text-muted-foreground">Échoués</span>
                                    <Badge className="bg-red-500">{paymentStats.total_failed}</Badge>
                                </div>
                                <div className="rounded-md bg-muted/60 px-3 py-2 text-sm">
                                    Revenus encaissés :{' '}
                                    <span className="font-semibold">{formatPrice(paymentStats.total_revenue_cents)}</span>
                            </div>
                    </CardContent>
                </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

