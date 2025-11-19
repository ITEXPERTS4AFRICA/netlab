import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import {
    Activity, RefreshCw, CheckCircle2, XCircle, AlertCircle, Clock,
    Server, Settings, Wifi, CreditCard, TrendingUp, TrendingDown
} from 'lucide-react';
import { useState, useEffect } from 'react';

interface HealthStatus {
    status: 'operational' | 'misconfigured' | 'down' | 'unknown';
    timestamp: string;
    configuration: {
        api_key: string;
        site_id: string;
        mode: string;
        notify_url: string;
        return_url: string;
        cancel_url: string;
    };
    connectivity: {
        status: 'reachable' | 'unreachable' | 'timeout' | 'error';
        response_time_ms: number;
        http_status?: number;
        url: string;
        error?: string;
    };
    api_status: {
        status: 'operational' | 'error';
        response_time_ms: number;
        can_initiate_payment: boolean;
        error?: string;
        code?: string;
    };
    overall_health: 'healthy' | 'degraded' | 'unhealthy' | 'unknown';
    issues: string[];
    summary: {
        config_valid: boolean;
        api_reachable: boolean;
        payment_working: boolean;
    };
}

interface Payment {
    id: number;
    transaction_id: string;
    amount: number;
    currency: string;
    status: string;
    created_at: string;
    lab_title: string;
    user_name: string;
}

interface PaymentStats {
    total: number;
    completed: number;
    pending: number;
    failed: number;
    cancelled: number;
    last_24h: number;
    last_7d: number;
}

interface Props {
    health: HealthStatus;
    recentPayments: Payment[];
    paymentStats: PaymentStats;
}

export default function PaymentHealthIndex({ health: initialHealth, recentPayments, paymentStats }: Props) {
    const [health, setHealth] = useState<HealthStatus>(initialHealth);
    const [isRefreshing, setIsRefreshing] = useState(false);
    const [autoRefresh, setAutoRefresh] = useState(false);

    useEffect(() => {
        if (!autoRefresh) return;

        const interval = setInterval(() => {
            refreshHealth();
        }, 30000); // Rafraîchir toutes les 30 secondes

        return () => clearInterval(interval);
    }, [autoRefresh]);

    const refreshHealth = async () => {
        setIsRefreshing(true);
        try {
            const response = await fetch('/admin/payments/health/refresh', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'include',
            });

            const data = await response.json();
            if (data.success && data.health) {
                setHealth(data.health);
            }
        } catch (error) {
            console.error('Erreur lors du rafraîchissement:', error);
        } finally {
            setIsRefreshing(false);
        }
    };

    const getHealthBadge = () => {
        switch (health.overall_health) {
            case 'healthy':
                return <Badge className="bg-green-500"><CheckCircle2 className="h-3 w-3 mr-1" />Opérationnel</Badge>;
            case 'degraded':
                return <Badge className="bg-yellow-500"><AlertCircle className="h-3 w-3 mr-1" />Dégradé</Badge>;
            case 'unhealthy':
                return <Badge className="bg-red-500"><XCircle className="h-3 w-3 mr-1" />Indisponible</Badge>;
            default:
                return <Badge variant="secondary"><Clock className="h-3 w-3 mr-1" />Inconnu</Badge>;
        }
    };

    const formatCurrency = (amount: number, currency: string = 'XOF') => {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: currency,
            minimumFractionDigits: 0,
        }).format(amount / 100);
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    return (
        <AppLayout>
            <Head title="Santé de l'API de paiement" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Santé de l'API de paiement</h1>
                        <p className="text-muted-foreground mt-1">
                            Surveillance de l'état de santé de l'API CinetPay
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        {getHealthBadge()}
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={refreshHealth}
                            disabled={isRefreshing}
                        >
                            <RefreshCw className={`h-4 w-4 mr-2 ${isRefreshing ? 'animate-spin' : ''}`} />
                            Actualiser
                        </Button>
                        <Button
                            variant={autoRefresh ? "default" : "outline"}
                            size="sm"
                            onClick={() => setAutoRefresh(!autoRefresh)}
                        >
                            <Activity className="h-4 w-4 mr-2" />
                            Auto-refresh
                        </Button>
                    </div>
                </div>

                {/* Alertes */}
                {health.issues.length > 0 && (
                    <Alert variant={health.overall_health === 'unhealthy' ? 'destructive' : 'default'}>
                        <AlertCircle className="h-4 w-4" />
                        <AlertDescription>
                            <strong>Problèmes détectés :</strong>
                            <ul className="list-disc list-inside mt-2">
                                {health.issues.map((issue, index) => (
                                    <li key={index}>{issue}</li>
                                ))}
                            </ul>
                        </AlertDescription>
                    </Alert>
                )}

                {/* Statistiques de santé */}
                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Configuration</CardTitle>
                            <Settings className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {health.summary.config_valid ? (
                                    <span className="text-green-600">✓ Valide</span>
                                ) : (
                                    <span className="text-red-600">✗ Invalide</span>
                                )}
                            </div>
                            <p className="text-xs text-muted-foreground mt-1">
                                Mode: {health.configuration.mode}
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Connectivité</CardTitle>
                            <Wifi className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {health.summary.api_reachable ? (
                                    <span className="text-green-600">✓ Accessible</span>
                                ) : (
                                    <span className="text-red-600">✗ Inaccessible</span>
                                )}
                            </div>
                            <p className="text-xs text-muted-foreground mt-1">
                                {health.connectivity.response_time_ms}ms
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Paiements</CardTitle>
                            <CreditCard className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {health.summary.payment_working ? (
                                    <span className="text-green-600">✓ Opérationnel</span>
                                ) : (
                                    <span className="text-red-600">✗ Erreur</span>
                                )}
                            </div>
                            <p className="text-xs text-muted-foreground mt-1">
                                {health.api_status.response_time_ms}ms
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Détails de la configuration */}
                <Card>
                    <CardHeader>
                        <CardTitle>Configuration</CardTitle>
                        <CardDescription>Paramètres de connexion à l'API CinetPay</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <p className="text-sm font-medium">Clé API</p>
                                <p className="text-sm text-muted-foreground">{health.configuration.api_key}</p>
                            </div>
                            <div>
                                <p className="text-sm font-medium">Site ID</p>
                                <p className="text-sm text-muted-foreground">{health.configuration.site_id}</p>
                            </div>
                            <div>
                                <p className="text-sm font-medium">Mode</p>
                                <p className="text-sm text-muted-foreground">{health.configuration.mode}</p>
                            </div>
                            <div>
                                <p className="text-sm font-medium">URL Webhook</p>
                                <p className="text-sm text-muted-foreground break-all">{health.configuration.notify_url}</p>
                            </div>
                            <div>
                                <p className="text-sm font-medium">URL Return</p>
                                <p className="text-sm text-muted-foreground break-all">{health.configuration.return_url}</p>
                            </div>
                            <div>
                                <p className="text-sm font-medium">URL Cancel</p>
                                <p className="text-sm text-muted-foreground break-all">{health.configuration.cancel_url}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Détails de la connectivité */}
                <div className="grid gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Server className="h-5 w-5" />
                                Connectivité API
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2">
                                <div className="flex justify-between">
                                    <span className="text-sm">Statut</span>
                                    <Badge variant={health.connectivity.status === 'reachable' ? 'default' : 'destructive'}>
                                        {health.connectivity.status}
                                    </Badge>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-sm">Temps de réponse</span>
                                    <span className="text-sm font-medium">{health.connectivity.response_time_ms}ms</span>
                                </div>
                                {health.connectivity.http_status && (
                                    <div className="flex justify-between">
                                        <span className="text-sm">Code HTTP</span>
                                        <span className="text-sm font-medium">{health.connectivity.http_status}</span>
                                    </div>
                                )}
                                {health.connectivity.error && (
                                    <div className="mt-2">
                                        <p className="text-sm text-red-600">{health.connectivity.error}</p>
                                    </div>
                                )}
                                <div className="mt-2">
                                    <p className="text-xs text-muted-foreground break-all">{health.connectivity.url}</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <CreditCard className="h-5 w-5" />
                                Test de paiement
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2">
                                <div className="flex justify-between">
                                    <span className="text-sm">Statut</span>
                                    <Badge variant={health.api_status.can_initiate_payment ? 'default' : 'destructive'}>
                                        {health.api_status.status}
                                    </Badge>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-sm">Temps de réponse</span>
                                    <span className="text-sm font-medium">{health.api_status.response_time_ms}ms</span>
                                </div>
                                {health.api_status.error && (
                                    <div className="mt-2">
                                        <p className="text-sm text-red-600">{health.api_status.error}</p>
                                        {health.api_status.code && (
                                            <p className="text-xs text-muted-foreground">Code: {health.api_status.code}</p>
                                        )}
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Statistiques des paiements */}
                <Card>
                    <CardHeader>
                        <CardTitle>Statistiques des paiements</CardTitle>
                        <CardDescription>Vue d'ensemble des transactions</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-4">
                            <div>
                                <p className="text-sm font-medium">Total</p>
                                <p className="text-2xl font-bold">{paymentStats.total}</p>
                            </div>
                            <div>
                                <p className="text-sm font-medium">Réussis</p>
                                <p className="text-2xl font-bold text-green-600">{paymentStats.completed}</p>
                            </div>
                            <div>
                                <p className="text-sm font-medium">En attente</p>
                                <p className="text-2xl font-bold text-yellow-600">{paymentStats.pending}</p>
                            </div>
                            <div>
                                <p className="text-sm font-medium">Échoués</p>
                                <p className="text-2xl font-bold text-red-600">{paymentStats.failed}</p>
                            </div>
                        </div>
                        <div className="grid gap-4 md:grid-cols-2 mt-4">
                            <div>
                                <p className="text-sm font-medium">Dernières 24h</p>
                                <p className="text-xl font-bold">{paymentStats.last_24h}</p>
                            </div>
                            <div>
                                <p className="text-sm font-medium">Derniers 7 jours</p>
                                <p className="text-xl font-bold">{paymentStats.last_7d}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Paiements récents */}
                <Card>
                    <CardHeader>
                        <CardTitle>Paiements récents</CardTitle>
                        <CardDescription>Dernières transactions enregistrées</CardDescription>
                    </CardHeader>
                    <CardContent>
                        {recentPayments.length > 0 ? (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b">
                                            <th className="text-left p-2">Transaction ID</th>
                                            <th className="text-left p-2">Lab</th>
                                            <th className="text-left p-2">Utilisateur</th>
                                            <th className="text-right p-2">Montant</th>
                                            <th className="text-center p-2">Statut</th>
                                            <th className="text-right p-2">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {recentPayments.map((payment) => (
                                            <tr key={payment.id} className="border-b">
                                                <td className="p-2 font-mono text-xs">{payment.transaction_id}</td>
                                                <td className="p-2">{payment.lab_title}</td>
                                                <td className="p-2">{payment.user_name}</td>
                                                <td className="p-2 text-right">{formatCurrency(payment.amount, payment.currency)}</td>
                                                <td className="p-2 text-center">
                                                    <Badge
                                                        variant={
                                                            payment.status === 'completed' ? 'default' :
                                                            payment.status === 'pending' ? 'secondary' :
                                                            'destructive'
                                                        }
                                                    >
                                                        {payment.status}
                                                    </Badge>
                                                </td>
                                                <td className="p-2 text-right text-xs text-muted-foreground">
                                                    {formatDate(payment.created_at)}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <p className="text-sm text-muted-foreground">Aucun paiement récent</p>
                        )}
                    </CardContent>
                </Card>

                {/* Timestamp */}
                <div className="text-xs text-muted-foreground text-center">
                    Dernière mise à jour : {formatDate(health.timestamp)}
                </div>
            </div>
        </AppLayout>
    );
}

