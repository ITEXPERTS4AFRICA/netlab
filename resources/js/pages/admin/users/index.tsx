import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    Users, Search, Plus, Edit, Trash2, Eye, Filter,
    Shield, GraduationCap, User as UserIcon
} from 'lucide-react';
import { useState } from 'react';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

interface RecentReservation {
    id: number;
    lab_title: string;
    status: string;
    start_at?: string;
    end_at?: string;
    estimated_cents?: number | null;
}

interface NextReservation {
    id: number;
    lab_title: string;
    status: string;
    start_at?: string;
}

interface User {
    id: string;
    name: string;
    email: string;
    role: string;
    is_active: boolean;
    avatar?: string;
    organization?: string;
    department?: string;
    total_reservations?: number;
    active_reservations_count?: number;
    pending_reservations_count?: number;
    completed_reservations_count?: number;
    pending_payments_count?: number;
    last_activity_at?: string | null;
    created_at: string;
    recent_reservations?: RecentReservation[];
    next_reservation?: NextReservation | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Props {
    users: {
        data: User[];
        links?: PaginationLink[];
        total?: number;
        current_page?: number;
        last_page?: number;
        per_page?: number;
    };
    filters: {
        search?: string;
        role?: string;
        status?: string;
    };
    stats: {
        total: number;
        active: number;
        admins: number;
        instructors: number;
        students: number;
    };
}

export default function UsersIndex({ users, filters, stats }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [role, setRole] = useState(filters.role || 'all');
    const [status, setStatus] = useState(filters.status || 'all');

    const handleSearch = () => {
        router.get('/admin/users', { search, role, status }, { preserveState: true });
    };

    const getRoleBadgeColor = (role: string) => {
        switch (role) {
            case 'admin': return 'bg-red-500';
            case 'instructor': return 'bg-blue-500';
            case 'student': return 'bg-green-500';
            default: return 'bg-gray-500';
        }
    };

    const getRoleIcon = (role: string) => {
        switch (role) {
            case 'admin': return <Shield className="h-4 w-4" />;
            case 'instructor': return <GraduationCap className="h-4 w-4" />;
            case 'student': return <UserIcon className="h-4 w-4" />;
            default: return <UserIcon className="h-4 w-4" />;
        }
    };

    const getReservationStatusStyle = (status: string) => {
        const base = 'px-2 py-0.5 rounded-full text-[11px] font-medium';
        switch (status) {
            case 'active':
                return `${base} bg-green-100 text-green-700`;
            case 'pending':
                return `${base} bg-yellow-100 text-yellow-700`;
            case 'completed':
                return `${base} bg-blue-100 text-blue-700`;
            case 'cancelled':
                return `${base} bg-red-100 text-red-700`;
            default:
                return `${base} bg-muted text-muted-foreground`;
        }
    };

    const formatDate = (value?: string | null) => {
        if (!value) return 'N/A';
        return new Date(value).toLocaleString('fr-FR', {
            day: '2-digit',
            month: 'short',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const formatPrice = (cents?: number | null) => {
        if (!cents) return '—';
        return `${(cents / 100).toLocaleString('fr-FR')} XOF`;
    };

    return (
        <AppLayout>
            <Head title="Gestion des utilisateurs" />

            <div className="container mx-auto py-8 space-y-6">
                {/* En-tête */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold flex items-center gap-2">
                            <Users className="h-8 w-8" />
                            Gestion des utilisateurs
                        </h1>
                        <p className="text-muted-foreground mt-1">
                            Gérez les utilisateurs de la plateforme
                        </p>
                    </div>
                    <Link href="/admin/users/create">
                        <Button>
                            <Plus className="h-4 w-4 mr-2" />
                            Nouvel utilisateur
                        </Button>
                    </Link>
                </div>

                {/* Statistiques */}
                <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Total</CardDescription>
                            <CardTitle className="text-2xl">{stats.total}</CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Actifs</CardDescription>
                            <CardTitle className="text-2xl text-green-600">{stats.active}</CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Administrateurs</CardDescription>
                            <CardTitle className="text-2xl text-red-600">{stats.admins}</CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Instructeurs</CardDescription>
                            <CardTitle className="text-2xl text-blue-600">{stats.instructors}</CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Étudiants</CardDescription>
                            <CardTitle className="text-2xl text-green-600">{stats.students}</CardTitle>
                        </CardHeader>
                    </Card>
                </div>

                {/* Filtres */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Filter className="h-5 w-5" />
                            Filtres
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex gap-4">
                            <div className="flex-1">
                                <Input
                                    placeholder="Rechercher par nom, email, organisation..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyPress={(e) => e.key === 'Enter' && handleSearch()}
                                />
                            </div>
                            <Select value={role} onValueChange={setRole}>
                                <SelectTrigger className="w-[180px]">
                                    <SelectValue placeholder="Rôle" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Tous les rôles</SelectItem>
                                    <SelectItem value="admin">Administrateur</SelectItem>
                                    <SelectItem value="instructor">Instructeur</SelectItem>
                                    <SelectItem value="student">Étudiant</SelectItem>
                                    <SelectItem value="user">Utilisateur</SelectItem>
                                </SelectContent>
                            </Select>
                            <Select value={status} onValueChange={setStatus}>
                                <SelectTrigger className="w-[180px]">
                                    <SelectValue placeholder="Statut" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Tous les statuts</SelectItem>
                                    <SelectItem value="active">Actif</SelectItem>
                                    <SelectItem value="inactive">Inactif</SelectItem>
                                </SelectContent>
                            </Select>
                            <Button onClick={handleSearch}>
                                <Search className="h-4 w-4 mr-2" />
                                Rechercher
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Liste des utilisateurs */}
                <Card>
                    <CardHeader>
                        <CardTitle>Utilisateurs</CardTitle>
                        <CardDescription>
                            {users?.total ?? users?.data?.length ?? 0} utilisateur(s) trouvé(s)
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-4">
                            {users?.data && users.data.length > 0 ? users.data.map((user) => (
                                <div
                                    key={user.id}
                                    className="p-4 border rounded-lg hover:bg-muted/40 transition-colors space-y-4"
                                >
                                    <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                    <div className="flex items-center gap-4 flex-1">
                                        <Avatar>
                                            <AvatarImage src={user.avatar || undefined} />
                                            <AvatarFallback>
                                                {user.name.charAt(0).toUpperCase()}
                                            </AvatarFallback>
                                        </Avatar>
                                        <div className="flex-1">
                                                <div className="flex items-center gap-2 flex-wrap">
                                                <h3 className="font-semibold">{user.name}</h3>
                                                <Badge className={getRoleBadgeColor(user.role)}>
                                                    {getRoleIcon(user.role)}
                                                        <span className="ml-1 capitalize">{user.role}</span>
                                                </Badge>
                                                {!user.is_active && (
                                                    <Badge variant="destructive">Inactif</Badge>
                                                )}
                                            </div>
                                            <p className="text-sm text-muted-foreground">{user.email}</p>
                                            {user.organization && (
                                                <p className="text-xs text-muted-foreground">
                                                    {user.organization}
                                                    {user.department && ` • ${user.department}`}
                                                </p>
                                            )}
                                        </div>
                                            <div className="text-right min-w-[140px]">
                                            <div className="text-sm font-medium">
                                                {user.total_reservations || 0} réservation(s)
                                            </div>
                                                <p className="text-xs text-muted-foreground">
                                                    Inscrit le {new Date(user.created_at).toLocaleDateString('fr-FR')}
                                                </p>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-2">
                                        <Link href={`/admin/users/${user.id}`}>
                                            <Button variant="ghost" size="sm">
                                                <Eye className="h-4 w-4" />
                                            </Button>
                                        </Link>
                                        <Link href={`/admin/users/${user.id}/edit`}>
                                            <Button variant="ghost" size="sm">
                                                <Edit className="h-4 w-4" />
                                            </Button>
                                        </Link>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => {
                                                if (confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) {
                                                    router.delete(`/admin/users/${user.id}`);
                                                }
                                            }}
                                        >
                                            <Trash2 className="h-4 w-4 text-destructive" />
                                        </Button>
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                                        <div className="rounded-md bg-muted/50 p-3">
                                            <p className="text-muted-foreground">Actives</p>
                                            <p className="text-base font-semibold">
                                                {user.active_reservations_count ?? 0}
                                            </p>
                                        </div>
                                        <div className="rounded-md bg-muted/50 p-3">
                                            <p className="text-muted-foreground">En attente</p>
                                            <p className="text-base font-semibold">
                                                {user.pending_reservations_count ?? 0}
                                            </p>
                                        </div>
                                        <div className="rounded-md bg-muted/50 p-3">
                                            <p className="text-muted-foreground">Terminées</p>
                                            <p className="text-base font-semibold">
                                                {user.completed_reservations_count ?? 0}
                                            </p>
                                        </div>
                                        <div className="rounded-md bg-muted/50 p-3">
                                            <p className="text-muted-foreground">Paiements en attente</p>
                                            <p className="text-base font-semibold">
                                                {user.pending_payments_count ?? 0}
                                            </p>
                                        </div>
                                    </div>

                                    <div className="flex flex-col gap-3 lg:flex-row">
                                        <div className="flex-1 rounded-lg border p-3 bg-card/30">
                                            <div className="flex items-center justify-between mb-2">
                                                <p className="text-xs font-semibold text-muted-foreground">
                                                    Interactions récentes
                                                </p>
                                                <span className="text-[11px] text-muted-foreground">
                                                    {user.recent_reservations?.length || 0}/3
                                                </span>
                                            </div>
                                            {user.recent_reservations && user.recent_reservations.length > 0 ? (
                                                <div className="space-y-2">
                                                    {user.recent_reservations.map((reservation) => (
                                                        <div
                                                            key={reservation.id}
                                                            className="flex items-center justify-between rounded-md bg-muted/50 px-2 py-1"
                                                        >
                                                            <div className="min-w-0">
                                                                <p className="text-xs font-medium truncate">
                                                                    {reservation.lab_title}
                                                                </p>
                                                                <p className="text-[11px] text-muted-foreground">
                                                                    {formatDate(reservation.start_at)}
                                                                </p>
                                                            </div>
                                                            <div className="text-right">
                                                                <span className={getReservationStatusStyle(reservation.status)}>
                                                                    {reservation.status}
                                                                </span>
                                                                <p className="text-[11px] text-muted-foreground mt-1">
                                                                    {formatPrice(reservation.estimated_cents)}
                                                                </p>
                                                            </div>
                                                        </div>
                                                    ))}
                                                </div>
                                            ) : (
                                                <p className="text-xs text-muted-foreground">
                                                    Aucune interaction récente.
                                                </p>
                                            )}
                                        </div>

                                        <div className="w-full lg:w-64 rounded-lg border p-3 space-y-2 bg-card/30">
                                            <div>
                                                <p className="text-xs text-muted-foreground">Dernière activité</p>
                                                <p className="text-sm font-semibold">
                                                    {user.last_activity_at ? formatDate(user.last_activity_at) : '—'}
                                                </p>
                                            </div>
                                            <div>
                                                <p className="text-xs text-muted-foreground">Prochaine réservation</p>
                                                {user.next_reservation ? (
                                                    <div className="text-sm font-semibold">
                                                        <p>{user.next_reservation.lab_title}</p>
                                                        <p className="text-xs text-muted-foreground">
                                                            {formatDate(user.next_reservation.start_at)}
                                                        </p>
                                                        <span className={getReservationStatusStyle(user.next_reservation.status)}>
                                                            {user.next_reservation.status}
                                                        </span>
                                                    </div>
                                                ) : (
                                                    <p className="text-sm text-muted-foreground">Aucune planifiée</p>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )) : (
                                <div className="text-center py-8 text-muted-foreground">
                                    Aucun utilisateur trouvé.
                                </div>
                            )}
                        </div>

                        {/* Pagination */}
                        {users?.links && Array.isArray(users.links) && users.links.length > 3 && (
                            <div className="flex items-center justify-center gap-2 mt-6">
                                {users.links.map((link: PaginationLink, index: number) => (
                                    <Button
                                        key={index}
                                        variant={link.active ? 'default' : 'outline'}
                                        size="sm"
                                        disabled={!link.url}
                                        onClick={() => link.url && router.get(link.url)}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

