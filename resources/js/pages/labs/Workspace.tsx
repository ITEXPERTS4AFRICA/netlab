import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage, router } from '@inertiajs/react';
import { Card, CardTitle, CardContent, CardHeader } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import AnnotationLab from '@/components/AnnotationLab';
import LabTopology from '@/components/LabTopology';
import {
    ArrowLeft,
    Play,
    Square,
    Network,
    Clock,
    Info,
    AlertTriangle,
    CheckCircle,
    Edit,
    Eye,
    Share2,
    ExternalLink,
    Timer,
    // Activity, // <--- Remove or comment out, as Activity is not available in lucide-react
    CheckCircle2,
    XCircle,
    Send
} from 'lucide-react';
import LabConsolePanel from '@/components/lab-console-panel';
import LabDetailsPanel from '@/components/LabDetailsPanel';
import LabEventsPanel from '@/components/LabEventsPanel';
import LabConfigEditor from '@/components/LabConfigEditor';
import { useEffect, useMemo, useState } from 'react';
import { toast } from 'sonner';
import { ActionLogsProvider, useActionLogs } from '@/contexts/ActionLogsContext';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Labs',
        href: '/labs',
    },
    {
        title: 'My Reservations',
        href: '/labs/my-reserved',
    },
    {
        title: 'Workspace',
        href: '#',
    }
];

type Lab = {
    id: string;
    cml_id: string;
    state: string;
    lab_title: string;
    node_count: string | number;
    lab_description?: string | string[] | Record<string, unknown>;
    created: string;
    modified: string;
    owner: string;
    owner_username?: string | null;
    owner_fullname?: string | null;
    link_count: number;
    effective_permissions: string[];
};

type Reservation = {
    id: number;
    start_at: string;
    end_at: string;
    status: string;
};

type LabNode = {
    id: string;
    label?: string;
    name?: string;
    state?: string;
    node_definition?: string;
};

type LabLink = {
    id: string;
    n1?: string;
    n2?: string;
    i1?: string;
    i2?: string;
    state?: string;
    [key: string]: unknown;
};

type Props = {
    lab: Lab;
    reservation: Reservation | null;
    nodes: LabNode[] | Record<string, LabNode>;
    links?: LabLink[];
    topology?: unknown;
    tile?: unknown;
};

function WorkspaceContent() {
    const { lab, reservation, nodes, links = [], topology } = usePage<Props>().props;
    const [editMode, setEditMode] = useState(false);
    const [timeLeft, setTimeLeft] = useState<number>(0);
    const [activeTab, setActiveTab] = useState<'console' | 'details' | 'events' | 'config'>('console');
    const { actionLogs } = useActionLogs();
    const labDescription = useMemo(() => {
        if (!lab.lab_description) return '';
        if (typeof lab.lab_description === 'string') return lab.lab_description;
        if (Array.isArray(lab.lab_description)) {
            return lab.lab_description.join('\n\n');
        }
        if (typeof lab.lab_description === 'object') {
            const descObj = lab.lab_description as Record<string, unknown>;
            if ('description' in descObj && typeof descObj.description === 'string') {
                return descObj.description;
            }
            return JSON.stringify(lab.lab_description, null, 2);
        }
        return String(lab.lab_description);
    }, [lab.lab_description]);
    const descriptionParagraphs = useMemo(() => {
        if (!labDescription) return [];
        return labDescription
            .split(/\n+/)
            .map((paragraph) => paragraph.trim())
            .filter(Boolean);
    }, [labDescription]);

    const nodeList = useMemo<LabNode[]>(() => {
        if (Array.isArray(nodes)) {
            // Filtrer les nodes valides avec un id
            return nodes.filter(node => node && node.id && typeof node.id === 'string' && node.id.trim() !== '');
        }
        if (nodes && typeof nodes === 'object') {
            const values = Object.values(nodes);
            return values.filter(node => node && typeof node === 'object' && node.id && typeof node.id === 'string' && node.id.trim() !== '') as LabNode[];
        }
        return [];
    }, [nodes]);

    // Log pour déboguer
    useEffect(() => {
        if (nodeList.length === 0 && lab.state === 'RUNNING') {
            console.warn('Aucun node disponible alors que le lab est RUNNING', {
                nodes,
                labState: lab.state,
                nodeCount: lab.node_count,
            });
        }
    }, [nodeList, nodes, lab.state, lab.node_count]);

    const [reservationProgress, setReservationProgress] = useState<number>(0);

    useEffect(() => {
        if (!reservation) return;

        const interval = setInterval(() => {
            const now = new Date().getTime();
            const start = new Date(reservation.start_at).getTime();
            const end = new Date(reservation.end_at).getTime();
            const total = end - start;
            const elapsed = now - start;
            const remaining = end - now;

            if (remaining <= 0) {
                clearInterval(interval);
                setReservationProgress(100);
                toast.error('Session ended. Redirecting to dashboard...', {
                    duration: 3000,
                });
                setTimeout(() => {
                    router.visit('/dashboard');
                }, 3000);
            } else {
                const progress = Math.min(100, Math.max(0, (elapsed / total) * 100));
                setReservationProgress(progress);
                setTimeLeft(Math.floor(remaining / 1000));
            }
        }, 1000);

        return () => clearInterval(interval);
    }, [reservation]);

    const formatTime = (seconds: number) => {
        const hrs = Math.floor(seconds / 3600);
        const mins = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        return `${hrs.toString().padStart(2, '0')}h${mins.toString().padStart(2, '0')}m${secs.toString().padStart(2, '0')}s`;
    };

    const handleStartLab = async () => {
        if (!confirm('Are you sure you want to start this lab?')) {
            return;
        }

        try {
            const csrfToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content;
            if (!csrfToken) {
                toast.error('Token CSRF introuvable');
                return;
            }

            const response = await fetch(`/api/labs/${lab.id}/start`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                // Extraire le message d'erreur de manière plus intelligente
                let errorMessage = errorData?.error ?? errorData?.message ?? `Erreur ${response.status}`;

                // Si c'est un objet avec une description, l'utiliser
                if (typeof errorData?.body === 'string') {
                    errorMessage = errorData.body;
                } else if (errorData?.detail?.description) {
                    errorMessage = errorData.detail.description;
                } else if (errorData?.body?.description) {
                    errorMessage = errorData.body.description;
                }

                console.error('Erreur démarrage lab:', {
                    status: response.status,
                    error: errorMessage,
                    fullBody: errorData,
                });

                throw new Error(errorMessage);
            }

            await response.json();
            toast.success('Lab démarré avec succès. Chargement de la topologie...');

            // Attendre un peu pour que le lab démarre complètement avant de recharger
            await new Promise(resolve => setTimeout(resolve, 2000));

            // Recharger la page pour obtenir l'état mis à jour du lab
            // Utiliser router.reload() au lieu de window.location.reload() pour éviter les problèmes Inertia
            router.reload({ only: ['lab', 'nodes', 'links', 'topology'] });
        } catch (error) {
            console.error('Error starting lab:', error);
            toast.error(error instanceof Error ? error.message : 'Erreur lors du démarrage du lab');
        }
    };

    const handleStopLab = async () => {
        if (!confirm('Are you sure you want to stop this lab? This will disconnect all active sessions.')) {
            return;
        }

        try {
            const csrfToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content;
            if (!csrfToken) {
                toast.error('Token CSRF introuvable');
                return;
            }

            const response = await fetch(`/api/labs/${lab.id}/stop`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData?.error || `Erreur ${response.status}`);
            }

            await response.json();
            toast.success('Lab arrêté avec succès');

            // Recharger la page pour obtenir l'état mis à jour du lab
            // Utiliser router.reload() au lieu de window.location.reload() pour éviter les problèmes Inertia
            router.reload({ only: ['lab', 'nodes', 'links', 'topology'] });
        } catch (error) {
            console.error('Error stopping lab:', error);
            toast.error(error instanceof Error ? error.message : 'Erreur lors de l\'arrêt du lab');
        }
    };

    const openInCML = () => {
        window.open(`https://54.38.146.213/lab/${lab.cml_id}`, '_blank');
    };

    // Icon component to use in place of Activity (lucide-react doesn't have Activity icon here)
    const LogsIcon = (props: React.SVGProps<SVGSVGElement>) => (
        <svg
            width={props.width || 20}
            height={props.height || 20}
            fill="none"
            viewBox="0 0 20 20"
            stroke="currentColor"
            strokeWidth={1.8}
            className={props.className}
        >
            {/* Simple Pulse/Heartbeat Style, reminiscent of an "activity" icon */}
            <polyline points="3 13 7 13 10 7 13 17 16 13 19 13" stroke="currentColor" strokeWidth="2" fill="none" />
            <circle cx="10" cy="10" r="8" stroke="currentColor" strokeWidth="1.8" fill="none" />
        </svg>
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${lab.lab_title} - Workspace`} />

            <div className="flex h-full flex-col gap-4 overflow-hidden p-4">
                {/* Header Section */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => router.visit('/labs')}
                            className="flex items-center gap-2"
                        >
                            <ArrowLeft className="h-4 w-4" />
                            Back to Labs
                        </Button>

                        <div className="flex items-center gap-3">
                            <div className="flex-shrink-0">
                                <CardTitle className="text-2xl font-bold">
                                    {lab.lab_title}
                                </CardTitle>
                            </div>

                            {/* Status Badge */}
                            <Badge
                                variant={(() => {
                                    const normalizedState = typeof lab.state === 'string'
                                        ? lab.state.toUpperCase()
                                        : (typeof lab.state === 'object' && lab.state !== null
                                            ? ((lab.state as any).data?.toUpperCase() || (lab.state as any).state?.toUpperCase() || 'UNKNOWN')
                                            : 'UNKNOWN');
                                    return normalizedState === 'RUNNING' || normalizedState === 'STARTED' ? 'default' :
                                        normalizedState === 'STOPPED' ? 'destructive' :
                                            normalizedState === 'STARTING' || normalizedState === 'STOPPING' ? 'secondary' :
                                                'outline';
                                })()}
                                className={`flex items-center gap-1.5 px-3 py-1 text-sm ${(() => {
                                    const normalizedState = typeof lab.state === 'string'
                                        ? lab.state.toUpperCase()
                                        : (typeof lab.state === 'object' && lab.state !== null
                                            ? ((lab.state as any).data?.toUpperCase() || (lab.state as any).state?.toUpperCase() || 'UNKNOWN')
                                            : 'UNKNOWN');
                                    if (normalizedState === 'RUNNING' || normalizedState === 'STARTED') {
                                        return 'bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800';
                                    } else if (normalizedState === 'STOPPED' || normalizedState === 'DEFINED_ON_CORE') {
                                        return normalizedState === 'DEFINED_ON_CORE'
                                            ? 'bg-yellow-100 text-yellow-800 border-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-300 dark:border-yellow-800'
                                            : 'bg-red-100 text-red-800 border-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-800';
                                    } else if (normalizedState === 'STARTING' || normalizedState === 'STOPPING') {
                                        return 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-800';
                                    } else {
                                        return 'bg-gray-100 text-gray-800 border-gray-200 dark:bg-gray-900/30 dark:text-gray-300 dark:border-gray-800';
                                    }
                                })()}`}
                            >
                                {(() => {
                                    // Normaliser l'état pour l'affichage
                                    let normalizedState: string;
                                    const stateObj = lab.state as Record<string, unknown> | string | null;
                                    if (typeof stateObj === 'string') {
                                        normalizedState = stateObj;
                                    } else if (typeof stateObj === 'object' && stateObj !== null) {
                                        normalizedState = (stateObj.data as string) || (stateObj.state as string) || JSON.stringify(stateObj);
                                    } else {
                                        normalizedState = String(stateObj || 'UNKNOWN');
                                    }

                                    const upperState = normalizedState.toUpperCase();

                                    if (upperState === 'RUNNING' || upperState === 'STARTED') {
                                        return <><CheckCircle className="h-4 w-4" /> Running</>;
                                    } else if (upperState === 'STOPPED') {
                                        return <><AlertTriangle className="h-4 w-4" /> Stopped</>;
                                    } else if (upperState === 'STARTING' || upperState === 'STOPPING') {
                                        return <><Clock className="h-4 w-4" /> {normalizedState.charAt(0).toUpperCase() + normalizedState.slice(1).toLowerCase()}</>;
                                    } else if (upperState === 'DEFINED_ON_CORE') {
                                        return <><Info className="h-4 w-4" /> Defined on Core</>;
                                    } else {
                                        return <><Info className="h-4 w-4" /> {typeof normalizedState === 'string' ? (normalizedState.charAt(0).toUpperCase() + normalizedState.slice(1).toLowerCase()) : 'Unknown'}</>;
                                    }
                                })()}
                            </Badge>
                        </div>
                    </div>

                    {/* Action Buttons */}
                    <div className="flex items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => setEditMode(!editMode)}
                            className={`flex items-center gap-2 ${editMode ? 'border-blue-500 bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300' : ''
                                }`}
                        >
                            {editMode ? <Eye className="h-4 w-4" /> : <Edit className="h-4 w-4" />}
                            {editMode ? 'View Mode' : 'Edit Annotations'}
                        </Button>

                        {(() => {
                            // Normaliser l'état pour vérifier les conditions
                            const stateObj = lab.state as Record<string, unknown> | string | null;
                            const normalizedState = typeof stateObj === 'string'
                                ? stateObj.toUpperCase()
                                : (typeof stateObj === 'object' && stateObj !== null
                                    ? ((stateObj.data as string)?.toUpperCase() || (stateObj.state as string)?.toUpperCase() || 'UNKNOWN')
                                    : 'UNKNOWN');

                            if (normalizedState === 'STOPPED' || normalizedState === 'DEFINED_ON_CORE') {
                                return (
                                    <Button
                                        onClick={handleStartLab}
                                        size="sm"
                                        className="flex items-center gap-2 bg-green-600 hover:bg-green-700"
                                    >
                                        <Play className="h-4 w-4" />
                                        Start Lab
                                    </Button>
                                );
                            }

                            if (normalizedState === 'RUNNING' || normalizedState === 'STARTED') {
                                return (
                                    <Button
                                        onClick={handleStopLab}
                                        variant="destructive"
                                        size="sm"
                                        className="flex items-center gap-2"
                                    >
                                        <Square className="h-4 w-4" />
                                        Stop Lab
                                    </Button>
                                );
                            }

                            return null;
                        })()}

                        <Button
                            onClick={openInCML}
                            size="sm"
                            className="flex items-center gap-2"
                        >
                            <ExternalLink className="h-4 w-4" />
                            Open in CML
                        </Button>
                    </div>
                </div>

                {/* Lab Info Card */}
                <Card className="border-0 shadow-sm">
                    <CardContent className="p-4">
                        <div className={`grid grid-cols-1 ${reservation ? 'md:grid-cols-4' : 'md:grid-cols-3'} gap-4`}>
                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/20 flex items-center justify-center">
                                    <Network className="w-5 h-5 text-blue-600 dark:text-blue-400" />
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-gray-900 dark:text-white">{lab.node_count}</p>
                                    <p className="text-xs text-gray-600 dark:text-gray-400">devices</p>
                                </div>
                            </div>

                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/20 flex items-center justify-center">
                                    <Clock className="w-5 h-5 text-purple-600 dark:text-purple-400" />
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-gray-900 dark:text-white">
                                        {new Date(lab.modified).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}
                                    </p>
                                    <p className="text-xs text-gray-600 dark:text-gray-400">last modified</p>
                                </div>
                            </div>

                            <div className="flex items-center gap-3">
                                <div className="w-10 h-10 rounded-lg bg-green-100 dark:bg-green-900/20 flex items-center justify-center">
                                    <Share2 className="w-5 h-5 text-green-600 dark:text-green-400" />
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-gray-900 dark:text-white">
                                        {lab.owner_fullname || lab.owner_username || lab.owner}
                                    </p>
                                    <p className="text-xs text-gray-600 dark:text-gray-400">owner</p>
                                </div>
                            </div>

                            {reservation && (
                                <div className="flex flex-col gap-2 w-full">
                                    <div className="flex items-center gap-3">
                                        <div className={`w-10 h-10 rounded-lg bg-orange-100 dark:bg-orange-900/20 flex items-center justify-center ${timeLeft < 300 ? 'animate-pulse bg-red-100 dark:bg-red-900/20' : ''}`}>
                                            <Timer className={`w-5 h-5 ${timeLeft < 300 ? 'text-red-600 dark:text-red-400' : 'text-orange-600 dark:text-orange-400'}`} />
                                        </div>
                                        <div className="flex-1">
                                            <p className="text-sm font-medium text-gray-900 dark:text-white">
                                                {formatTime(timeLeft)}
                                            </p>
                                            <p className="text-xs text-gray-600 dark:text-gray-400">
                                                {timeLeft < 3600 ? 'remaining (min:sec)' : 'remaining (hr:min:sec)'}
                                            </p>
                                        </div>
                                    </div>
                                    {/* Progressbar animée */}
                                    <div className="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden relative">
                                        <div
                                            className={`h-full rounded-full transition-all duration-1000 ease-linear relative ${reservationProgress < 50
                                                ? 'bg-gradient-to-r from-green-500 to-emerald-400'
                                                : reservationProgress < 80
                                                    ? 'bg-gradient-to-r from-yellow-500 to-amber-400'
                                                    : 'bg-gradient-to-r from-red-500 to-rose-400'
                                                } ${timeLeft < 300 ? 'animate-pulse' : ''}`}
                                            style={{
                                                width: `${reservationProgress}%`,
                                                boxShadow: reservationProgress > 0
                                                    ? reservationProgress < 50
                                                        ? '0 0 8px rgba(16, 185, 129, 0.6)'
                                                        : reservationProgress < 80
                                                            ? '0 0 8px rgba(234, 179, 8, 0.6)'
                                                            : '0 0 8px rgba(239, 68, 68, 0.6)'
                                                    : 'none',
                                            }}
                                        >
                                            {/* Effet shimmer animé */}
                                            <div
                                                className="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent"
                                                style={{
                                                    animation: 'shimmer 2s infinite linear',
                                                }}
                                            />
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>

                        {labDescription && (
                            <>
                                <Separator className="my-4" />
                                <p className="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                                    {labDescription}
                                </p>
                            </>
                        )}
                    </CardContent>
                </Card>

                {descriptionParagraphs.length > 0 && (
                    <Card className="border border-dashed border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm">
                        <CardHeader>
                            <div className="flex items-center gap-2">
                                <Info className="h-5 w-5 text-blue-500" />
                                <h3 className="text-lg font-semibold">Description du lab</h3>
                            </div>
                            <p className="text-sm text-muted-foreground">
                                Comprenez le contexte, les objectifs et les ressources avant de vous lancer.
                            </p>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {descriptionParagraphs.slice(0, 4).map((paragraph: string, index: number) => (
                                <p key={index} className="text-sm leading-relaxed text-gray-700 dark:text-gray-300">
                                    {paragraph}
                                </p>
                            ))}
                            {descriptionParagraphs.length > 4 && (
                                <details className="text-sm text-blue-600 dark:text-blue-300">
                                    <summary className="cursor-pointer hover:underline">
                                        Voir plus de détails
                                    </summary>
                                    <div className="mt-2 space-y-3 text-gray-700 dark:text-gray-300">
                                        {descriptionParagraphs.slice(4).map((paragraph: string, index: number) => (
                                            <p key={`extra-${index}`} className="text-sm leading-relaxed">
                                                {paragraph}
                                            </p>
                                        ))}
                                    </div>
                                </details>
                            )}
                        </CardContent>
                    </Card>
                )}

                {/* Workspace Area */}
                <div className="flex flex-1 flex-col gap-4 overflow-hidden">
                    <Tabs value={activeTab} onValueChange={(v: string) => setActiveTab(v as typeof activeTab)} className="flex-1 flex flex-col">
                        <TabsList className="grid w-full grid-cols-4">
                            <TabsTrigger value="console">Console</TabsTrigger>
                            <TabsTrigger value="details">Détails</TabsTrigger>
                            <TabsTrigger value="events">Événements</TabsTrigger>
                            <TabsTrigger value="config">Configuration</TabsTrigger>
                        </TabsList>

                        <TabsContent value="console" className="flex-1 flex flex-col gap-4 mt-4 overflow-hidden">
                            <div className="flex flex-1 flex-col gap-4 overflow-hidden lg:flex-row">
                                {/* Console - Priorité principale */}
                                <div className="min-h-[28rem] w-full flex-[2] overflow-hidden">
                                    <LabConsolePanel
                                        cmlLabId={lab.cml_id}
                                        nodes={nodeList}
                                    />
                                </div>

                                {/* Logs & Monitoring */}
                                <div className="relative flex-[1] min-w-[400px] overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                                    <div className="h-full flex flex-col">
                                        <div className="border-b border-border p-4">
                                            <h3 className="text-lg font-semibold flex items-center gap-2">
                                                <LogsIcon className="h-5 w-5" />
                                                Logs & Monitoring
                                                {actionLogs.length > 0 && (
                                                    <Badge variant="secondary" className="ml-2 h-5 px-1.5 text-xs">
                                                        {actionLogs.length}
                                                    </Badge>
                                                )}
                                            </h3>
                                            <p className="text-xs text-muted-foreground mt-1">
                                                Suivi de toutes les actions effectuées
                                            </p>
                                        </div>
                                        <div className="flex-1 overflow-y-auto p-4">
                                            {actionLogs.length === 0 ? (
                                                <div className="flex flex-col items-center justify-center h-full py-8 text-center">
                                                    <LogsIcon className="h-12 w-12 text-muted-foreground mb-4 opacity-50" />
                                                    <p className="text-sm text-muted-foreground">
                                                        Aucune action enregistrée
                                                    </p>
                                                    <p className="text-xs text-muted-foreground mt-1">
                                                        Les actions effectuées apparaîtront ici
                                                    </p>
                                                </div>
                                            ) : (
                                                <div className="space-y-2">
                                                    {actionLogs.map((log) => {
                                                        const statusIcon =
                                                            log.status === 'success' ? (
                                                                <CheckCircle2 className="h-4 w-4 text-green-500" />
                                                            ) : log.status === 'error' ? (
                                                                <XCircle className="h-4 w-4 text-red-500" />
                                                            ) : log.status === 'sent' ? (
                                                                <Send className="h-4 w-4 text-blue-500" />
                                                            ) : (
                                                                <Clock className="h-4 w-4 text-yellow-500 animate-pulse" />
                                                            );

                                                        const statusColor =
                                                            log.status === 'success' ? 'text-green-600 dark:text-green-400' :
                                                                log.status === 'error' ? 'text-red-600 dark:text-red-400' :
                                                                    log.status === 'sent' ? 'text-blue-600 dark:text-blue-400' :
                                                                        'text-yellow-600 dark:text-yellow-400';

                                                        const typeBadgeColor =
                                                            log.type === 'command' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' :
                                                                log.type === 'session' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300' :
                                                                    log.type === 'error' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' :
                                                                        'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300';

                                                        return (
                                                            <div
                                                                key={log.id}
                                                                className="flex items-start gap-2 p-2 rounded-lg border border-border bg-card hover:bg-accent/50 transition-colors"
                                                            >
                                                                <div className="mt-0.5">{statusIcon}</div>
                                                                <div className="flex-1 min-w-0 space-y-1">
                                                                    <div className="flex items-center gap-2 flex-wrap">
                                                                        <span className={`text-xs font-medium ${statusColor}`}>
                                                                            {log.action}
                                                                        </span>
                                                                        <Badge variant="secondary" className={`text-xs ${typeBadgeColor}`}>
                                                                            {log.type}
                                                                        </Badge>
                                                                    </div>
                                                                    {log.command && (
                                                                        <div className="font-mono text-xs bg-muted p-1.5 rounded border text-xs">
                                                                            <span className="text-muted-foreground">&gt; </span>
                                                                            {log.command}
                                                                        </div>
                                                                    )}
                                                                    {log.details && (
                                                                        <p className="text-xs text-muted-foreground line-clamp-2">
                                                                            {log.details}
                                                                        </p>
                                                                    )}
                                                                    <p className="text-xs text-muted-foreground">
                                                                        {log.timestamp.toLocaleTimeString('fr-FR', {
                                                                            hour: '2-digit',
                                                                            minute: '2-digit',
                                                                            second: '2-digit',
                                                                        })}
                                                                    </p>
                                                                </div>
                                                            </div>
                                                        );
                                                    })}
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </TabsContent>

                        <TabsContent value="details" className="flex-1 mt-4 overflow-hidden">
                            <LabDetailsPanel labId={lab.cml_id} className="h-full" />
                        </TabsContent>

                        <TabsContent value="events" className="flex-1 mt-4 overflow-hidden">
                            <LabEventsPanel labId={lab.cml_id} className="h-full" />
                        </TabsContent>

                        <TabsContent value="config" className="flex-1 mt-4 overflow-hidden min-h-[700px]">
                            <LabConfigEditor labId={lab.cml_id} className="h-full min-h-[700px]" />
                        </TabsContent>
                    </Tabs>
                </div>

                {/* Topology Graph - En bas avec largeur maximale */}
                <div className="relative w-full h-[400px] overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900 mt-4">
                    {/* Topology Graph - Show when lab is running - Must be on top */}
                    {(() => {
                        const stateObj = lab.state as Record<string, unknown> | string | null;
                        const normalizedState = typeof stateObj === 'string'
                            ? stateObj.toUpperCase()
                            : (typeof stateObj === 'object' && stateObj !== null
                                ? ((stateObj.data as string)?.toUpperCase() || (stateObj.state as string)?.toUpperCase() || 'UNKNOWN')
                                : 'UNKNOWN');

                        const shouldShow = normalizedState === 'RUNNING' || normalizedState === 'STARTED';

                        // Log uniquement en mode développement et seulement si les valeurs changent
                        if (import.meta.env.DEV) {
                            const logKey = `${normalizedState}-${shouldShow}-${nodeList.length}-${Array.isArray(links) ? links.length : 0}`;
                            const windowObj = window as Record<string, unknown>;
                            if (!windowObj.__lastTopologyLog || windowObj.__lastTopologyLog !== logKey) {
                                console.log('Workspace: Rendu topologie', {
                                    normalizedState,
                                    shouldShow,
                                    nodeListCount: nodeList.length,
                                    linksCount: Array.isArray(links) ? links.length : 0,
                                    topologyType: typeof topology,
                                    hasTopologyNodes: !!(topology && typeof topology === 'object' && 'nodes' in topology),
                                });
                                windowObj.__lastTopologyLog = logKey;
                            }
                        }

                        return shouldShow;
                    })() ? (
                        <div className="absolute inset-0 z-20" style={{ minHeight: '400px', minWidth: '400px' }}>
                            <LabTopology
                                nodes={nodeList}
                                links={Array.isArray(links) ? links : []}
                                topology={topology as { nodes?: LabNode[]; links?: LabLink[] } | null | undefined}
                                labId={lab.cml_id}
                                realtimeUpdate={true}
                                updateInterval={5000}
                                className="h-full w-full"
                            />
                        </div>
                    ) : (
                        <div className="absolute inset-0 z-20 flex items-center justify-center bg-gray-900 text-white">
                            <div className="text-center">
                                <p>Lab state: {typeof lab.state === 'string' ? lab.state : JSON.stringify(lab.state)}</p>
                                <p>Topology will appear when lab is RUNNING or STARTED</p>
                            </div>
                        </div>
                    )}

                    {/* Annotations Canvas - Show only when lab is NOT running to avoid grid overlap */}
                    {(() => {
                        const stateObj = lab.state as Record<string, unknown> | string | null;
                        const normalizedState = typeof stateObj === 'string'
                            ? stateObj.toUpperCase()
                            : (typeof stateObj === 'object' && stateObj !== null
                                ? ((stateObj.data as string)?.toUpperCase() || (stateObj.state as string)?.toUpperCase() || 'UNKNOWN')
                                : 'UNKNOWN');
                        return normalizedState !== 'RUNNING' && normalizedState !== 'STARTED';
                    })() && (
                            <div className="absolute inset-0 z-10">
                                <AnnotationLab
                                    labId={lab.id}
                                    editMode={editMode}
                                    onEditModeChange={setEditMode}
                                    className="h-full w-full"
                                />
                            </div>
                        )}

                    {/* Edit Mode Overlay - Positioned to avoid overlap with topology info panel */}
                    {editMode && (
                        <div className="absolute right-4 top-16 z-40 rounded-lg border border-gray-200 bg-white p-3 shadow-lg dark:border-gray-700 dark:bg-gray-800">
                            <div className="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <Edit className="h-4 w-4 text-blue-600" />
                                <span>Edit Mode Active</span>
                            </div>
                            <p className="mt-1 text-xs text-gray-600 dark:text-gray-400">
                                Drag annotations to reposition • Changes are auto-saved
                            </p>
                        </div>
                    )}

                    {/* Lab State Overlay */}
                    {(() => {
                        // Normaliser l'état pour vérifier les conditions
                        const stateObj = lab.state as Record<string, unknown> | string | null;
                        const normalizedState = typeof stateObj === 'string'
                            ? stateObj.toUpperCase()
                            : (typeof stateObj === 'object' && stateObj !== null
                                ? ((stateObj.data as string)?.toUpperCase() || (stateObj.state as string)?.toUpperCase() || 'UNKNOWN')
                                : 'UNKNOWN');

                        if (!editMode && normalizedState !== 'RUNNING' && normalizedState !== 'STARTED') {
                            const stateDisplay = normalizedState === 'DEFINED_ON_CORE'
                                ? 'Defined on Core'
                                : normalizedState === 'STOPPED'
                                    ? 'Stopped'
                                    : normalizedState.toLowerCase();

                            return (
                                <div className="absolute inset-0 z-30 flex items-center justify-center bg-black bg-opacity-50">
                                    <div className="mx-4 max-w-md rounded-lg bg-white p-6 text-center shadow-xl dark:bg-gray-800">
                                        <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700">
                                            {normalizedState === 'STOPPED' || normalizedState === 'DEFINED_ON_CORE' ? (
                                                <AlertTriangle className="h-8 w-8 text-gray-600 dark:text-gray-400" />
                                            ) : normalizedState === 'STARTING' || normalizedState === 'STOPPING' ? (
                                                <div className="h-8 w-8 animate-spin rounded-full border-4 border-blue-600 border-t-transparent"></div>
                                            ) : (
                                                <Info className="h-8 w-8 text-gray-600 dark:text-gray-400" />
                                            )}
                                        </div>
                                        <h3 className="mb-2 text-lg font-semibold text-gray-900 dark:text-white">
                                            Lab {stateDisplay}
                                        </h3>
                                        <p className="mb-4 text-sm text-gray-600 dark:text-gray-400">
                                            {(normalizedState === 'STOPPED' || normalizedState === 'DEFINED_ON_CORE') &&
                                                'Le lab est actuellement arrêté ou défini sur le core. Démarrez-le pour visualiser et interagir avec les annotations.'}
                                            {normalizedState === 'STARTING' && 'Le lab démarre. Cela peut prendre quelques minutes...'}
                                            {normalizedState === 'STOPPING' && 'Le lab s\'arrête. Veuillez patienter...'}
                                        </p>
                                        {(normalizedState === 'STOPPED' || normalizedState === 'DEFINED_ON_CORE') && (
                                            <Button onClick={handleStartLab} className="bg-green-600 hover:bg-green-700">
                                                <Play className="mr-2 h-4 w-4" />
                                                Démarrer le Lab
                                            </Button>
                                        )}
                                    </div>
                                </div>
                            );
                        }
                        return null;
                    })()}
                </div>
            </div>
        </AppLayout>
    );
}

export default function Workspace() {
    return (
        <ActionLogsProvider>
            <WorkspaceContent />
        </ActionLogsProvider>
    );
}
