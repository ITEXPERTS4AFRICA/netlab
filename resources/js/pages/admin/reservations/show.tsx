import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
    ArrowLeft, Calendar, Clock, User, DollarSign, CreditCard, Activity,
    CheckCircle, XCircle, AlertCircle, FileText, Edit, Save, X
} from 'lucide-react';
import { useState } from 'react';

interface Payment {
    id: number;
    transaction_id: string;
    cinetpay_transaction_id: string | null;
    amount: number;
    currency: string;
    status: string;
    payment_method: string | null;
    customer_name: string;
    customer_surname: string | null;
    customer_email: string;
    customer_phone_number: string | null;
    description: string | null;
    cinetpay_response: any;
    webhook_data: any;
    paid_at: string | null;
    created_at: string;
    updated_at: string;
}

interface UsageRecord {
    id: number;
    started_at: string;
    ended_at: string | null;
    duration_seconds: number;
    cost_cents: number;
    duration_hours: number | null;
}

interface Lab {
    id: number;
    cml_id: string;
    lab_title: string;
    lab_description: any;
    short_description: string | null;
    state: string;
    price_cents: number | null;
    currency: string | null;
    is_published: boolean;
}

interface User {
    id: string;
    name: string;
    email: string;
    phone: string | null;
}

interface Rate {
    id: number;
    name: string | null;
}

interface TimeRemaining {
    minutes: number;
    hours: number;
    is_expired: boolean;
}

interface Reservation {
    id: number;
    lab: Lab;
    user: User;
    rate: Rate | null;
    start_at: string;
    end_at: string;
    status: 'pending' | 'active' | 'completed' | 'cancelled';
    estimated_cents: number | null;
    notes: string | null;
    payments: Payment[];
    usage_record: UsageRecord | null;
    time_remaining: TimeRemaining | null;
    created_at: string;
    updated_at: string;
    duration_hours: number | null;
}

interface Props {
    reservation: Reservation;
}

export default function ReservationShow({ reservation }: Props) {
    const [isEditing, setIsEditing] = useState(false);
    const { data, setData, put, processing } = useForm({
        status: reservation.status,
        notes: reservation.notes || '',
    });

    const handleUpdate = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/admin/reservations/${reservation.id}`, {
            preserveScroll: true,
            onSuccess: () => setIsEditing(false),
        });
    };

    const handleCancel = () => {
        if (confirm('Êtes-vous sûr de vouloir annuler cette réservation ?')) {
            router.post(`/admin/reservations/${reservation.id}/cancel`);
        }
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

    const getPaymentStatusBadge = (status: string) => {
        const colors: Record<string, string> = {
            'completed': 'bg-green-500',
            'pending': 'bg-yellow-500',
            'failed': 'bg-red-500',
        };
        return colors[status] || 'bg-gray-500';
    };

    const getPaymentStatusLabel = (status: string) => {
        const labels: Record<string, string> = {
            'completed': 'Payé',
            'pending': 'En attente',
            'failed': 'Échoué',
        };
        return labels[status] || status;
    };

    const formatPrice = (cents: number | null) => {
        if (!cents) return 'Gratuit';
        return `${(cents / 100).toLocaleString('fr-FR')} XOF`;
    };

    const formatDuration = (hours: number | null) => {
        if (!hours) return 'N/A';
        if (hours < 1) return `${Math.round(hours * 60)} min`;
        return `${hours.toFixed(1)} h`;
    };

    const formatTimeRemaining = (timeRemaining: TimeRemaining | null) => {
        if (!timeRemaining) return 'N/A';
        if (timeRemaining.is_expired) return 'Expirée';
        if (timeRemaining.minutes < 60) return `${Math.round(timeRemaining.minutes)} min`;
        const hours = Math.floor(timeRemaining.minutes / 60);
        const mins = Math.round(timeRemaining.minutes % 60);
        return `${hours}h ${mins}min`;
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleString('fr-FR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    return (
        <AppLayout>
            <Head title={`Réservation #${reservation.id}`} />

            <div className="container mx-auto py-8 space-y-6">
                {/* En-tête */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => router.visit('/admin/reservations')}
                        >
                            <ArrowLeft className="h-4 w-4 mr-2" />
                            Retour
                        </Button>
                        <div>
                            <h1 className="text-3xl font-bold flex items-center gap-2">
                                <Calendar className="h-8 w-8" />
                                Réservation #{reservation.id}
                            </h1>
                            <p className="text-muted-foreground mt-1">
                                Détails complets de la réservation
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <Badge className={getStatusBadge(reservation.status)}>
                            {getStatusLabel(reservation.status)}
                        </Badge>
                        {reservation.status !== 'cancelled' && (
                            <Button
                                variant="destructive"
                                size="sm"
                                onClick={handleCancel}
                            >
                                <X className="h-4 w-4 mr-2" />
                                Annuler
                            </Button>
                        )}
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Colonne principale */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Informations générales */}
                        <Card>
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <CardTitle className="flex items-center gap-2">
                                        <FileText className="h-5 w-5" />
                                        Informations générales
                                    </CardTitle>
                                    {!isEditing && (
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => setIsEditing(true)}
                                        >
                                            <Edit className="h-4 w-4 mr-2" />
                                            Modifier
                                        </Button>
                                    )}
                                </div>
                            </CardHeader>
                            <CardContent>
                                {isEditing ? (
                                    <form onSubmit={handleUpdate} className="space-y-4">
                                        <div>
                                            <label className="text-sm font-medium mb-2 block">Statut</label>
                                            <Select
                                                value={data.status}
                                                onValueChange={(value) => setData('status', value as any)}
                                            >
                                                <SelectTrigger>
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="pending">En attente</SelectItem>
                                                    <SelectItem value="active">Active</SelectItem>
                                                    <SelectItem value="completed">Terminée</SelectItem>
                                                    <SelectItem value="cancelled">Annulée</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div>
                                            <label className="text-sm font-medium mb-2 block">Notes</label>
                                            <Textarea
                                                value={data.notes}
                                                onChange={(e) => setData('notes', e.target.value)}
                                                rows={4}
                                                placeholder="Notes sur la réservation..."
                                            />
                                        </div>
                                        <div className="flex gap-2">
                                            <Button type="submit" disabled={processing}>
                                                <Save className="h-4 w-4 mr-2" />
                                                Enregistrer
                                            </Button>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                onClick={() => {
                                                    setIsEditing(false);
                                                    setData({
                                                        status: reservation.status,
                                                        notes: reservation.notes || '',
                                                    });
                                                }}
                                            >
                                                <X className="h-4 w-4 mr-2" />
                                                Annuler
                                            </Button>
                                        </div>
                                    </form>
                                ) : (
                                    <div className="space-y-4">
                                        <div className="grid grid-cols-2 gap-4">
                                            <div>
                                                <p className="text-sm text-muted-foreground">Statut</p>
                                                <Badge className={getStatusBadge(reservation.status)}>
                                                    {getStatusLabel(reservation.status)}
                                                </Badge>
                                            </div>
                                            <div>
                                                <p className="text-sm text-muted-foreground">Date de début</p>
                                                <p className="font-medium">{formatDate(reservation.start_at)}</p>
                                            </div>
                                            <div>
                                                <p className="text-sm text-muted-foreground">Date de fin</p>
                                                <p className="font-medium">{formatDate(reservation.end_at)}</p>
                                            </div>
                                            <div>
                                                <p className="text-sm text-muted-foreground">Durée</p>
                                                <p className="font-medium">{formatDuration(reservation.duration_hours)}</p>
                                            </div>
                                            {reservation.time_remaining && (
                                                <div>
                                                    <p className="text-sm text-muted-foreground">Temps restant</p>
                                                    <p className="font-medium">
                                                        {formatTimeRemaining(reservation.time_remaining)}
                                                    </p>
                                                </div>
                                            )}
                                            <div>
                                                <p className="text-sm text-muted-foreground">Prix estimé</p>
                                                <p className="font-medium">{formatPrice(reservation.estimated_cents)}</p>
                                            </div>
                                        </div>
                                        {reservation.notes && (
                                            <div>
                                                <p className="text-sm text-muted-foreground mb-2">Notes</p>
                                                <p className="text-sm bg-muted p-3 rounded-md">{reservation.notes}</p>
                                            </div>
                                        )}
                                        <div className="grid grid-cols-2 gap-4 pt-4 border-t">
                                            <div>
                                                <p className="text-sm text-muted-foreground">Créée le</p>
                                                <p className="text-sm">{formatDate(reservation.created_at)}</p>
                                            </div>
                                            <div>
                                                <p className="text-sm text-muted-foreground">Modifiée le</p>
                                                <p className="text-sm">{formatDate(reservation.updated_at)}</p>
                                            </div>
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Informations du lab */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Activity className="h-5 w-5" />
                                    Lab réservé
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-4">
                                    <div>
                                        <p className="text-sm text-muted-foreground">Titre</p>
                                        <p className="font-medium">{reservation.lab.lab_title}</p>
                                    </div>
                                    {reservation.lab.short_description && (
                                        <div>
                                            <p className="text-sm text-muted-foreground">Description</p>
                                            <p className="text-sm">{reservation.lab.short_description}</p>
                                        </div>
                                    )}
                                    <div className="grid grid-cols-2 gap-4">
                                        <div>
                                            <p className="text-sm text-muted-foreground">CML ID</p>
                                            <p className="text-sm font-mono">{reservation.lab.cml_id}</p>
                                        </div>
                                        <div>
                                            <p className="text-sm text-muted-foreground">État</p>
                                            <Badge variant="outline">{reservation.lab.state}</Badge>
                                        </div>
                                        <div>
                                            <p className="text-sm text-muted-foreground">Prix par heure</p>
                                            <p className="text-sm">{formatPrice(reservation.lab.price_cents)}</p>
                                        </div>
                                        <div>
                                            <p className="text-sm text-muted-foreground">Publié</p>
                                            <Badge variant={reservation.lab.is_published ? 'default' : 'secondary'}>
                                                {reservation.lab.is_published ? 'Oui' : 'Non'}
                                            </Badge>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Informations utilisateur */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <User className="h-5 w-5" />
                                    Utilisateur
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-4">
                                    <div>
                                        <p className="text-sm text-muted-foreground">Nom</p>
                                        <p className="font-medium">{reservation.user.name}</p>
                                    </div>
                                    <div>
                                        <p className="text-sm text-muted-foreground">Email</p>
                                        <p className="text-sm">{reservation.user.email}</p>
                                    </div>
                                    {reservation.user.phone && (
                                        <div>
                                            <p className="text-sm text-muted-foreground">Téléphone</p>
                                            <p className="text-sm">{reservation.user.phone}</p>
                                        </div>
                                    )}
                                    <div>
                                        <p className="text-sm text-muted-foreground">ID Utilisateur</p>
                                        <p className="text-sm font-mono">{reservation.user.id}</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Paiements */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <CreditCard className="h-5 w-5" />
                                    Paiements ({reservation.payments.length})
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {reservation.payments.length > 0 ? (
                                    <div className="space-y-4">
                                        {reservation.payments.map((payment) => (
                                            <div
                                                key={payment.id}
                                                className="border rounded-lg p-4 space-y-3"
                                            >
                                                <div className="flex items-center justify-between">
                                                    <div className="flex items-center gap-2">
                                                        <Badge className={getPaymentStatusBadge(payment.status)}>
                                                            {getPaymentStatusLabel(payment.status)}
                                                        </Badge>
                                                        <span className="text-sm text-muted-foreground">
                                                            #{payment.id}
                                                        </span>
                                                    </div>
                                                    <p className="font-medium">{formatPrice(payment.amount)}</p>
                                                </div>
                                                <div className="grid grid-cols-2 gap-4 text-sm">
                                                    <div>
                                                        <p className="text-muted-foreground">Transaction ID</p>
                                                        <p className="font-mono text-xs">{payment.transaction_id}</p>
                                                    </div>
                                                    {payment.cinetpay_transaction_id && (
                                                        <div>
                                                            <p className="text-muted-foreground">CinetPay ID</p>
                                                            <p className="font-mono text-xs">{payment.cinetpay_transaction_id}</p>
                                                        </div>
                                                    )}
                                                    {payment.payment_method && (
                                                        <div>
                                                            <p className="text-muted-foreground">Méthode</p>
                                                            <p>{payment.payment_method}</p>
                                                        </div>
                                                    )}
                                                    {payment.paid_at && (
                                                        <div>
                                                            <p className="text-muted-foreground">Payé le</p>
                                                            <p>{formatDate(payment.paid_at)}</p>
                                                        </div>
                                                    )}
                                                </div>
                                                {payment.description && (
                                                    <div>
                                                        <p className="text-sm text-muted-foreground">Description</p>
                                                        <p className="text-sm">{payment.description}</p>
                                                    </div>
                                                )}
                                                <div className="grid grid-cols-2 gap-4 text-xs text-muted-foreground pt-2 border-t">
                                                    <div>
                                                        <p>Créé: {formatDate(payment.created_at)}</p>
                                                    </div>
                                                    <div>
                                                        <p>Modifié: {formatDate(payment.updated_at)}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <p className="text-sm text-muted-foreground">Aucun paiement enregistré</p>
                                )}
                            </CardContent>
                        </Card>

                        {/* Enregistrement d'utilisation */}
                        {reservation.usage_record && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <Clock className="h-5 w-5" />
                                        Enregistrement d'utilisation
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-4">
                                        <div className="grid grid-cols-2 gap-4">
                                            <div>
                                                <p className="text-sm text-muted-foreground">Début</p>
                                                <p className="font-medium">{formatDate(reservation.usage_record.started_at)}</p>
                                            </div>
                                            {reservation.usage_record.ended_at && (
                                                <div>
                                                    <p className="text-sm text-muted-foreground">Fin</p>
                                                    <p className="font-medium">{formatDate(reservation.usage_record.ended_at)}</p>
                                                </div>
                                            )}
                                            <div>
                                                <p className="text-sm text-muted-foreground">Durée</p>
                                                <p className="font-medium">
                                                    {formatDuration(reservation.usage_record.duration_hours)}
                                                </p>
                                            </div>
                                            <div>
                                                <p className="text-sm text-muted-foreground">Coût réel</p>
                                                <p className="font-medium">
                                                    {formatPrice(reservation.usage_record.cost_cents)}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </div>

                    {/* Colonne latérale */}
                    <div className="space-y-6">
                        {/* Résumé */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Résumé</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="flex items-center justify-between">
                                    <span className="text-sm text-muted-foreground">Statut</span>
                                    <Badge className={getStatusBadge(reservation.status)}>
                                        {getStatusLabel(reservation.status)}
                                    </Badge>
                                </div>
                                <div className="flex items-center justify-between">
                                    <span className="text-sm text-muted-foreground">Prix estimé</span>
                                    <span className="font-medium">{formatPrice(reservation.estimated_cents)}</span>
                                </div>
                                {reservation.usage_record && (
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm text-muted-foreground">Coût réel</span>
                                        <span className="font-medium">
                                            {formatPrice(reservation.usage_record.cost_cents)}
                                        </span>
                                    </div>
                                )}
                                <div className="flex items-center justify-between">
                                    <span className="text-sm text-muted-foreground">Durée</span>
                                    <span className="font-medium">{formatDuration(reservation.duration_hours)}</span>
                                </div>
                                {reservation.payments.some(p => p.status === 'completed') && (
                                    <div className="flex items-center gap-2 pt-2 border-t">
                                        <CheckCircle className="h-4 w-4 text-green-600" />
                                        <span className="text-sm text-green-600">Paiement confirmé</span>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

