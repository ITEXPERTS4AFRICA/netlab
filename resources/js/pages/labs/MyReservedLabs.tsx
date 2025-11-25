import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { Card, CardTitle, CardContent, CardHeader } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import {
    Clock,
    Play,
    AlertCircle,
    CheckCircle,
    Timer,
    Calendar,
    ExternalLink,
    ArrowRight,
    Network,
    Activity,
    DollarSign
} from 'lucide-react';
import { motion, Variants } from 'framer-motion';
import { useState, useEffect, useMemo } from 'react';
import { router } from '@inertiajs/react';
import { formatDuration } from '@/lib/utils';
import { useFeedback } from '@/components/FeedbackManager';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Labs',
        href: '/labs',
    },
    {
        title: 'My Reserved Labs',
        href: '/labs/my-reserved',
    }
];

type ReservedLab = {
    reservation_id: string;
    lab_id: string;
    cml_id: string;
    lab_title: string;
    lab_description: string;
    node_count: number;
    current_state: string;
    reservation_start: string;
    reservation_end: string;
    duration_hours: number;
    time_info: {
        status: 'active' | 'pending' | 'expired';
        time_remaining_minutes?: number;
        time_to_start_minutes?: number;
        end_time?: string;
        start_time?: string;
        can_access: boolean;
    };
    can_access: boolean;
    status: string;
    requires_payment?: boolean;
    payment_amount?: number;
};

type Props = {
    reservedLabs: ReservedLab[];
    error?: string;
    auth?: {
        user?: {
            phone?: string | null;
        };
    };
};

export default function MyReservedLabs() {
    const { reservedLabs = [], error, auth } = usePage<Props>().props;
    const [isLoading, setIsLoading] = useState(true);
    const [processingPayment, setProcessingPayment] = useState<string | null>(null);
    const { showSuccess, showError, showWarning } = useFeedback();

    useEffect(() => {
        const timer = setTimeout(() => setIsLoading(false), 500);
        return () => clearTimeout(timer);
    }, []);

    // Mémoriser les statistiques pour éviter les recalculs à chaque rendu
    const stats = useMemo(() => {
        const safeLabs = reservedLabs.filter(lab => lab && lab.time_info);
        return {
            total: safeLabs.length,
            canAccess: safeLabs.filter(lab => lab.time_info?.status === 'active' && lab.time_info?.can_access).length,
            pending: safeLabs.filter(lab => lab.time_info?.status === 'pending').length,
            starting: safeLabs.filter(lab => lab.time_info?.status === 'active' && !lab.time_info?.can_access).length,
        };
    }, [reservedLabs]);

    const getStatusBadge = (lab: ReservedLab) => {
        const { time_info } = lab;
        if (!time_info) {
            return (
                <Badge variant="outline">
                    Unknown
                </Badge>
            );
        }

        switch (time_info.status) {
            case 'active':
                if (time_info.can_access) {
                    return (
                        <Badge className="bg-[hsl(var(--chart-3))] hover:bg-[hsl(var(--chart-3))/80] text-white">
                            <CheckCircle className="h-3 w-3 mr-1" />
                            Active - Can Access
                        </Badge>
                    );
                } else {
                    return (
                        <Badge variant="secondary" className="bg-[hsl(var(--chart-2))] hover:bg-[hsl(var(--chart-2))/80] text-white">
                            <AlertCircle className="h-3 w-3 mr-1" />
                            Active - Starting
                        </Badge>
                    );
                }
            case 'pending':
                return (
                    <Badge variant="outline" className="border-[hsl(var(--chart-4))] text-[hsl(var(--chart-4))]">
                        <Clock className="h-3 w-3 mr-1" />
                        Pending
                    </Badge>
                );
            case 'expired':
                return (
                    <Badge variant="destructive">
                        <AlertCircle className="h-3 w-3 mr-1" />
                        Expired
                    </Badge>
                );
            default:
                return (
                    <Badge variant="outline">
                        {time_info.status}
                    </Badge>
                );
        }
    };

    const getTimeDisplay = (lab: ReservedLab) => {
        const { time_info } = lab;
        if (!time_info) {
            return null;
        }

        if (time_info.status === 'active' && time_info.time_remaining_minutes !== undefined) {
            const hours = Math.floor(time_info.time_remaining_minutes / 60);
            const minutes = time_info.time_remaining_minutes % 60;

            // Format avec zéros devant
            const formattedHours = hours.toString().padStart(2, '0');
            const formattedMinutes = minutes.toString().padStart(2, '0');

            if (time_info.time_remaining_minutes < 60) {
                return (
                    <div className="flex items-center gap-2 text-orange-600">
                        <Timer className="h-4 w-4" />
                        <span className="font-medium font-mono">
                            {formattedMinutes} min restantes
                        </span>
                    </div>
                );
            }

            return (
                <div className="flex items-center gap-2 text-green-600">
                    <Timer className="h-4 w-4" />
                    <span className="font-medium font-mono">
                        {formattedHours}h{formattedMinutes} restantes
                    </span>
                </div>
            );
        }

        if (time_info.status === 'pending' && time_info.time_to_start_minutes !== undefined) {
            const hours = Math.floor(time_info.time_to_start_minutes / 60);
            const minutes = time_info.time_to_start_minutes % 60;

            // Format avec zéros devant
            const formattedHours = hours.toString().padStart(2, '0');
            const formattedMinutes = minutes.toString().padStart(2, '0');

            return (
                <div className="flex items-center gap-2 text-blue-600">
                    <Clock className="h-4 w-4" />
                    <span className="font-medium font-mono">
                        Dans {formattedHours}h{formattedMinutes}
                    </span>
                </div>
            );
        }

        return null;
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

    const cardVariants: Variants = {
        hidden: { opacity: 0, y: 20 },
        visible: {
            opacity: 1,
            y: 0,
            transition: {
                type: "spring",
                stiffness: 100,
                damping: 15
            }
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="My Reserved Labs" />
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
                                <motion.h1
                                    className="text-4xl font-bold tracking-tight bg-gradient-to-r from-foreground via-foreground to-foreground/70 bg-clip-text text-transparent"
                                    initial={{ opacity: 0, x: -20 }}
                                    animate={{ opacity: 1, x: 0 }}
                                    transition={{ duration: 0.6, delay: 0.2 }}
                                >
                                    My Reserved Labs
                                </motion.h1>
                                <p className="text-muted-foreground text-lg mt-1">
                                    Access your reserved network laboratories
                                </p>
                            </div>
                        </div>

                        {/* Quick Stats */}
                        <motion.div
                            className="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6"
                            initial={{ opacity: 0, y: 10 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.5, delay: 0.4 }}
                        >
                            <div className="p-4 rounded-xl bg-primary/10 border border-primary/20">
                                <div className="text-2xl font-bold text-primary">{stats.total}</div>
                                <div className="text-sm text-muted-foreground">Total Reserved</div>
                            </div>
                            <div className="p-4 rounded-xl bg-green-500/10 border border-green-500/20">
                                <div className="text-2xl font-bold text-green-600">{stats.canAccess}</div>
                                <div className="text-sm text-muted-foreground">Can Access Now</div>
                            </div>
                            <div className="p-4 rounded-xl bg-blue-500/10 border border-blue-500/20">
                                <div className="text-2xl font-bold text-blue-600">{stats.pending}</div>
                                <div className="text-sm text-muted-foreground">Pending</div>
                            </div>
                            <div className="p-4 rounded-xl bg-orange-500/10 border border-orange-500/20">
                                <div className="text-2xl font-bold text-orange-600">{stats.starting}</div>
                                <div className="text-sm text-muted-foreground">Starting</div>
                            </div>
                        </motion.div>
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
                                <h3 className="font-semibold text-destructive">Connection Error</h3>
                                <p className="text-sm text-muted-foreground mt-1">{error}</p>
                            </div>
                        </div>
                    </motion.div>
                )}

                {/* Labs Grid */}
                {isLoading ? (
                    <div className="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                        {[...Array(6)].map((_, i) => (
                            <Card key={i} className="overflow-hidden">
                                <CardHeader className="pb-4">
                                    <Skeleton className="h-5 w-32 mb-2" />
                                    <Skeleton className="h-4 w-20" />
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-4">
                                        <Skeleton className="h-16 w-full" />
                                        <div className="flex gap-2">
                                            <Skeleton className="h-9 w-24" />
                                            <Skeleton className="h-9 w-20" />
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                ) : reservedLabs.length === 0 ? (
                    <motion.div
                        variants={cardVariants}
                        className="flex flex-col items-center justify-center py-16 text-center"
                    >
                        <motion.div
                            initial={{ scale: 0 }}
                            animate={{ scale: 1 }}
                            transition={{ duration: 0.5, delay: 0.2 }}
                            className="w-24 h-24 rounded-full bg-muted flex items-center justify-center mb-6"
                        >
                            <Calendar className="h-12 w-12 text-muted-foreground" />
                        </motion.div>
                        <h3 className="text-xl font-semibold mb-2">No reserved labs</h3>
                        <p className="text-muted-foreground max-w-md mb-6">
                            You haven't reserved any labs yet. Browse available labs to make a reservation.
                        </p>
                        <Button onClick={() => router.visit('/labs')}>
                            Browse Labs
                        </Button>
                    </motion.div>
                ) : (
                    <motion.div
                        className="grid gap-6 md:grid-cols-2 xl:grid-cols-3"
                        variants={containerVariants}
                    >
                        {reservedLabs.map((lab, index) => {
                            if (!lab || !lab.time_info) {
                                return null;
                            }
                            return (
                                <motion.div key={lab.reservation_id || `lab-${index}`} variants={cardVariants}>
                                <Card className="group relative overflow-hidden border-0 bg-gradient-to-br from-card via-card/95 to-card/80 hover:shadow-2xl hover:shadow-primary/5 transition-all duration-500">
                                    {/* Status gradient stripe */}
                                    <motion.div
                                        className={`absolute top-0 left-0 right-0 h-1 ${
                                            lab.time_info?.status === 'active' && lab.time_info?.can_access
                                                ? 'bg-gradient-to-r from-[hsl(var(--chart-3))] to-[hsl(var(--chart-3))/70]'
                                                : lab.time_info?.status === 'pending'
                                                ? 'bg-gradient-to-r from-[hsl(var(--chart-4))] to-[hsl(var(--chart-4))/70]'
                                                : lab.time_info?.status === 'expired'
                                                ? 'bg-gradient-to-r from-destructive to-destructive/70'
                                                : 'bg-gradient-to-r from-[hsl(var(--chart-2))] to-[hsl(var(--chart-2))/70]'
                                        }`}
                                        initial={false}
                                        animate={{
                                            scaleX: [0, 1],
                                            transformOrigin: "left"
                                        }}
                                        transition={{ duration: 0.6, delay: index * 0.05 }}
                                    />

                                    <CardHeader className="pb-4">
                                        <div className="flex items-start justify-between">
                                            <div className="flex-1 min-w-0">
                                                <CardTitle className="text-xl font-bold line-clamp-2 mb-3 group-hover:text-primary transition-colors duration-300">
                                                    {lab.lab_title}
                                                </CardTitle>
                                                {getStatusBadge(lab)}
                                            </div>

                                            {/* Status indicator - Clickable to access lab */}
                                            <motion.button
                                                onClick={() => {
                                                    if (lab.requires_payment) {
                                                        // Si paiement requis, rediriger vers l'initiation de paiement
                                                        window.location.href = `/api/reservations/${lab.reservation_id}/payments/initiate`;
                                                        return;
                                                    }
                                                    
                                                    if (lab.time_info.status !== 'expired') {
                                                        router.visit(`/labs/${lab.lab_id}/workspace`, {
                                                            method: 'get',
                                                            preserveScroll: true,
                                                        });
                                                    }
                                                }}
                                                disabled={lab.time_info.status === 'expired'}
                                                className={`p-3 rounded-xl transition-all duration-200 ${
                                                    lab.time_info.status === 'expired'
                                                        ? 'bg-destructive/10 border border-destructive/20 cursor-not-allowed opacity-50'
                                                        : lab.requires_payment
                                                        ? 'bg-amber-500/10 border border-amber-500/20 hover:bg-amber-500/20 cursor-pointer'
                                                        : lab.time_info.status === 'active' && lab.time_info.can_access
                                                        ? 'bg-[hsl(var(--chart-3)/10)] border border-[hsl(var(--chart-3)/20)] hover:bg-[hsl(var(--chart-3)/20)] cursor-pointer'
                                                        : lab.time_info.status === 'pending'
                                                        ? 'bg-[hsl(var(--chart-4)/10)] border border-[hsl(var(--chart-4)/20)] hover:bg-[hsl(var(--chart-4)/20)] cursor-pointer'
                                                        : 'bg-[hsl(var(--chart-2)/10)] border border-[hsl(var(--chart-2)/20)] hover:bg-[hsl(var(--chart-2)/20)] cursor-pointer'
                                                }`}
                                                whileHover={lab.time_info.status !== 'expired' ? { scale: 1.1 } : {}}
                                                whileTap={lab.time_info.status !== 'expired' ? { scale: 0.95 } : {}}
                                                transition={{ duration: 0.2 }}
                                                title={lab.time_info.status === 'expired' ? 'Reservation expirée' : lab.requires_payment ? 'Paiement requis' : 'Accéder au lab'}
                                            >
                                                {lab.time_info.status === 'active' && lab.time_info.can_access ? (
                                                    <CheckCircle className="h-6 w-6 text-[hsl(var(--chart-3))]" />
                                                ) : lab.requires_payment ? (
                                                    <DollarSign className="h-6 w-6 text-amber-500" />
                                                ) : lab.time_info.status === 'pending' ? (
                                                    <Clock className="h-6 w-6 text-[hsl(var(--chart-4))]" />
                                                ) : lab.time_info.status === 'expired' ? (
                                                    <AlertCircle className="h-6 w-6 text-destructive" />
                                                ) : (
                                                    <Play className="h-6 w-6 text-[hsl(var(--chart-2))]" />
                                                )}
                                            </motion.button>
                                        </div>
                                    </CardHeader>

                                    <CardContent className="space-y-6">
                                        {/* Description */}
                                        {lab.lab_description && (
                                            <p className="text-muted-foreground line-clamp-2 text-sm leading-relaxed">
                                                {lab.lab_description}
                                            </p>
                                        )}

                                        {/* Time Information */}
                                        <div className="space-y-3">
                                            <div className="flex items-center justify-between text-sm">
                                                <span className="text-muted-foreground">Duration:</span>
                                                <Badge variant="outline" className="font-mono">
                                                    {formatDuration(lab.duration_hours)}
                                                </Badge>
                                            </div>

                                            <div className="flex items-center justify-between text-sm">
                                                <span className="text-muted-foreground">Time slot:</span>
                                                <span className="font-mono text-xs">
                                                    {new Date(lab.reservation_start).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', hour12: false })} - {new Date(lab.reservation_end).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', hour12: false })}
                                                </span>
                                            </div>

                                            {/* Dynamic time display */}
                                            {getTimeDisplay(lab)}
                                        </div>

                                        {/* Lab Stats */}
                                        <div className="grid grid-cols-2 gap-4">
                                            <motion.div
                                                className="flex items-center gap-3 p-4 rounded-xl bg-muted/30 border border-border/50"
                                                whileHover={{ scale: 1.02 }}
                                            >
                                                <Network className="h-5 w-5 text-[hsl(var(--chart-4))]" />
                                                <div>
                                                    <div className="text-lg font-semibold">{lab.node_count}</div>
                                                    <div className="text-xs text-muted-foreground">devices</div>
                                                </div>
                                            </motion.div>

                                            <motion.div
                                                className="flex items-center gap-3 p-4 rounded-xl bg-muted/30 border border-border/50"
                                                whileHover={{ scale: 1.02 }}
                                            >
                                                <Activity className="h-5 w-5 text-[hsl(var(--chart-5))]" />
                                                <div>
                                                    <div className="text-sm font-medium capitalize">
                                                        {lab.current_state || 'Unknown'}
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">status</div>
                                                </div>
                                            </motion.div>
                                        </div>

                                        {/* Access Button - Always visible except for expired */}
                                        <motion.div
                                            whileHover={{ scale: 1.02 }}
                                            whileTap={{ scale: 0.98 }}
                                            className="pt-2"
                                        >
                                            {lab.time_info.status === 'expired' ? (
                                                <Button
                                                    disabled
                                                    variant="destructive"
                                                    className="w-full h-12 cursor-not-allowed"
                                                >
                                                    <AlertCircle className="h-5 w-5 mr-2" />
                                                    Reservation Expired
                                                </Button>
                                            ) : lab.requires_payment ? (
                                                <Button
                                                    onClick={async () => {
                                                        if (processingPayment === lab.reservation_id) return;
                                                        
                                                        setProcessingPayment(lab.reservation_id);
                                                        
                                                        try {
                                                            // Récupérer le token CSRF
                                                            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                                                            
                                                            if (!csrfToken) {
                                                                showError('Token CSRF manquant. Veuillez recharger la page.');
                                                                setProcessingPayment(null);
                                                                return;
                                                            }
                                                            
                                                            // Préparer les données (numéro de téléphone optionnel)
                                                            const requestData: Record<string, string> = {};
                                                            if (auth?.user?.phone) {
                                                                requestData.customer_phone_number = auth.user.phone;
                                                            }
                                                            
                                                            const response = await fetch(`/api/reservations/${lab.reservation_id}/payments/initiate`, {
                                                                method: 'POST',
                                                                headers: {
                                                                    'Content-Type': 'application/json',
                                                                    'X-CSRF-TOKEN': csrfToken,
                                                                    'Accept': 'application/json',
                                                                },
                                                                body: JSON.stringify(requestData),
                                                            });
                                                            
                                                            const result = await response.json();
                                                            
                                                            if (!response.ok) {
                                                                // Gérer les erreurs de validation
                                                                if (response.status === 422 && result.errors) {
                                                                    const errorMessages = Object.values(result.errors).flat().join(', ');
                                                                    showError(`Erreur de validation: ${errorMessages}`);
                                                                } else if (response.status === 503 || result.is_timeout || result.code === 'CONNECTION_TIMEOUT') {
                                                                    // Timeout - message spécial avec option de réessayer
                                                                    showWarning(
                                                                        result.message || result.error || 'Le service de paiement ne répond pas. Veuillez réessayer dans quelques instants.',
                                                                        { duration: 8000 }
                                                                    );
                                                                    // Permettre de réessayer après un court délai
                                                                    setTimeout(() => {
                                                                        setProcessingPayment(null);
                                                                    }, 2000);
                                                                } else {
                                                                    // Autres erreurs
                                                                    showError(result.message || result.error || 'Erreur lors de l\'initialisation du paiement');
                                                                }
                                                                setProcessingPayment(null);
                                                                return;
                                                            }
                                                            
                                                            // Succès - rediriger vers l'URL de paiement
                                                            if (result.payment_url) {
                                                                window.location.href = result.payment_url;
                                                            } else {
                                                                showWarning('L\'URL de paiement n\'a pas été fournie. Veuillez réessayer.');
                                                                setProcessingPayment(null);
                                                            }
                                                        } catch (error) {
                                                            console.error('Erreur lors de l\'initiation du paiement:', error);
                                                            showError('Une erreur est survenue lors de l\'initialisation du paiement. Veuillez réessayer.');
                                                            setProcessingPayment(null);
                                                        }
                                                    }}
                                                    disabled={processingPayment === lab.reservation_id}
                                                    className="w-full h-12 bg-amber-600 hover:bg-amber-700 text-white font-medium shadow-lg hover:shadow-xl transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed"
                                                >
                                                    <DollarSign className="h-5 w-5 mr-2" />
                                                    {processingPayment === lab.reservation_id ? (
                                                        'Traitement...'
                                                    ) : (
                                                        <>
                                                            Payer ({lab.payment_amount ? (lab.payment_amount / 100).toLocaleString('fr-FR') : ''} XOF)
                                                            <ArrowRight className="h-5 w-5 ml-2" />
                                                        </>
                                                    )}
                                                </Button>
                                            ) : lab.time_info.can_access ? (
                                                <Button
                                                    onClick={() => router.visit(`/labs/${lab.lab_id}/workspace`, {
                                                        method: 'get',
                                                        preserveScroll: true,
                                                        onSuccess: () => {
                                                            // Optionnel: feedback
                                                        },
                                                        onError: (errors) => {
                                                            console.error('Navigation error:', errors);
                                                        }
                                                    })}
                                                    className="w-full h-12 bg-gradient-to-r from-[hsl(var(--chart-3))] to-[hsl(var(--chart-3))/90] hover:from-[hsl(var(--chart-3))/90] hover:to-[hsl(var(--chart-3))] text-white font-medium shadow-lg hover:shadow-xl transition-all duration-300"
                                                >
                                                    <ExternalLink className="h-5 w-5 mr-2" />
                                                    Access Lab Now
                                                    <ArrowRight className="h-5 w-5 ml-2" />
                                                </Button>
                                            ) : lab.time_info.status === 'pending' ? (
                                                <Button
                                                    onClick={() => router.visit(`/labs/${lab.lab_id}/workspace`, {
                                                        method: 'get',
                                                        preserveScroll: true,
                                                    })}
                                                    className="w-full h-12 bg-[hsl(var(--chart-4))] hover:bg-[hsl(var(--chart-4))/90] text-white font-medium shadow-lg hover:shadow-xl transition-all duration-300"
                                                >
                                                    <Clock className="h-5 w-5 mr-2" />
                                                    View Lab (Pending)
                                                    <ArrowRight className="h-5 w-5 ml-2" />
                                                </Button>
                                            ) : (
                                                <Button
                                                    onClick={() => router.visit(`/labs/${lab.lab_id}/workspace`, {
                                                        method: 'get',
                                                        preserveScroll: true,
                                                    })}
                                                    className="w-full h-12 bg-[hsl(var(--chart-2))] hover:bg-[hsl(var(--chart-2))/90] text-white font-medium shadow-lg hover:shadow-xl transition-all duration-300"
                                                >
                                                    <Play className="h-5 w-5 mr-2" />
                                                    Access Lab (Starting)
                                                    <ArrowRight className="h-5 w-5 ml-2" />
                                                </Button>
                                            )}
                                        </motion.div>
                                    </CardContent>
                                </Card>
                            </motion.div>
                            );
                        })}
                    </motion.div>
                )}
            </motion.div>
        </AppLayout>
    );
}
